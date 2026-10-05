<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Tests;

use Exception;
use PHPUnit\Framework\Assert;
use Product;
use Splash\Client\Splash;
use Splash\Local\Local;
use Splash\Local\Services\ProductIdentifier;
use Splash\Local\Tests\Tools\ProductRefsTrait;
use Splash\Tests\Tools\TestCase;

/**
 * Local Test Suite - Verify Product References Normalization
 */
class L11ProductRefNormalizationTest extends TestCase
{
    use ProductRefsTrait;

    /**
     * Test Normalization of Product References
     *
     * @dataProvider normalizeProvider
     *
     * @param string $ref      Product reference, as received from the source
     * @param string $expected Product reference, as Dolibarr stores it
     *
     * @return void
     */
    public function testNormalizeRef(string $ref, string $expected): void
    {
        Assert::assertSame($expected, ProductIdentifier::normalizeRef($ref));
    }

    /**
     * Test Normalization when References Sanitization is Disabled
     *
     * @return void
     */
    public function testUnsecuredRefsSetting(): void
    {
        global $conf;

        $ref = "  A/B C  ";
        $conf->global->{ProductIdentifier::ALLOW_UNSECURED_REFS} = 1;

        try {
            //====================================================================//
            // Since Dolibarr V18 => Setting is Honored, Reference is only Trimmed
            // Before Dolibarr V18 => Setting does not Exist, Reference is Sanitized
            $expected = (Local::dolVersionCmp("18.0.0") >= 0) ? "A/B C" : "A_B_C";
            Assert::assertSame($expected, ProductIdentifier::normalizeRef($ref));
        } finally {
            unset($conf->global->{ProductIdentifier::ALLOW_UNSECURED_REFS});
        }

        Assert::assertSame("A_B_C", ProductIdentifier::normalizeRef($ref));
    }

    /**
     * Test Normalization matches what Dolibarr really Stores
     *
     * Creates a product through Splash with a raw reference, then compares
     * the reference Dolibarr stored with the normalized one.
     *
     * @dataProvider storageProvider
     *
     * @param string $ref Product reference, as received from the source
     *
     * @throws Exception
     *
     * @return void
     */
    public function testMatchesDolibarrStorage(string $ref): void
    {
        global $db;

        //====================================================================//
        // Create Product with Raw Reference
        $productId = $this->createProduct($ref);
        //====================================================================//
        // Load Stored Reference
        $product = new Product($db);
        Assert::assertGreaterThan(0, $product->fetch((int) $productId));
        //====================================================================//
        // Stored Reference is the Normalized One
        Assert::assertSame(ProductIdentifier::normalizeRef($ref), $product->ref);
        //====================================================================//
        // Cleanup
        $this->deleteProduct($productId);
    }

    /**
     * Test a Reference Written on an Existing Product is Normalized
     *
     * @throws Exception
     *
     * @return void
     */
    public function testWriteRefIsNormalized(): void
    {
        global $db;

        $uniq = substr(uniqid(), -6);
        $productId = $this->createProduct("WRITE_".$uniq);
        //====================================================================//
        // Write a Raw Reference
        $newRef = "WRITE/".$uniq."/B";
        Splash::object("Product")->lock($productId);
        Assert::assertSame($productId, Splash::object("Product")->set($productId, array("ref" => $newRef)));
        //====================================================================//
        // Stored Reference is the Normalized One
        $product = new Product($db);
        Assert::assertGreaterThan(0, $product->fetch((int) $productId));
        Assert::assertSame(ProductIdentifier::normalizeRef($newRef), $product->ref);

        $this->deleteProduct($productId);
    }

    /**
     * Test Writing the Same Raw Reference again Keeps the Documents Path
     *
     * Regression test for orphaned documents: writing "A/B" on a product
     * stored as "A_B" used to rename its documents path to "produit/A/B".
     *
     * @throws Exception
     *
     * @return void
     */
    public function testWriteSameRefKeepsDocumentsPath(): void
    {
        global $db;

        $ref = "DOCS/".substr(uniqid(), -6);
        $productId = $this->createProduct($ref);
        $docsPath = "produit/".ProductIdentifier::normalizeRef($ref);
        //====================================================================//
        // Register a Document for this Product
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_files (label, filepath, filename)";
        $sql .= " VALUES ('L11', '".$db->escape($docsPath)."', 'L11.png')";
        Assert::assertNotFalse($db->query($sql));
        //====================================================================//
        // Write the Same Raw Reference again
        Splash::object("Product")->lock($productId);
        Assert::assertSame($productId, Splash::object("Product")->set($productId, array("ref" => $ref)));
        //====================================================================//
        // Documents Path is Unchanged
        $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."ecm_files";
        $sql .= " WHERE label = 'L11' AND filepath = '".$db->escape($docsPath)."'";
        $result = $db->query($sql);
        Assert::assertNotFalse($result);
        Assert::assertEquals(1, $db->fetch_object($result)->nb);
        //====================================================================//
        // Cleanup
        $db->query("DELETE FROM ".MAIN_DB_PREFIX."ecm_files WHERE label = 'L11'");
        $this->deleteProduct($productId);
    }

    /**
     * Test Documents Path is Updated for a Reference Holding a Double Quote
     *
     * Regression test for unescaped SQL in updateFilesPath(): a double quote in
     * the reference used to break the query. Quotes only survive when references
     * sanitization is disabled, a setting that exists since Dolibarr V18.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testWriteQuotedRefUpdatesDocumentsPath(): void
    {
        global $db, $conf;

        if (Local::dolVersionCmp("18.0.0") < 0) {
            $this->markTestSkipped("Unsecured references require Dolibarr V18+");
        }
        $uniq = substr(uniqid(), -6);
        $productId = $this->createProduct("QUOTE_".$uniq);
        $conf->global->{ProductIdentifier::ALLOW_UNSECURED_REFS} = 1;

        try {
            //====================================================================//
            // Register a Document for this Product
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_files (label, filepath, filename)";
            $sql .= " VALUES ('L11Q', 'produit/QUOTE_".$uniq."', 'L11.png')";
            Assert::assertNotFalse($db->query($sql));
            //====================================================================//
            // Write a Reference Holding a Quote
            $newRef = "QUOTE\"".$uniq;
            Splash::object("Product")->lock($productId);
            Assert::assertSame($productId, Splash::object("Product")->set($productId, array("ref" => $newRef)));
            //====================================================================//
            // Documents Path Follows the New Reference
            $sql = "SELECT filepath FROM ".MAIN_DB_PREFIX."ecm_files WHERE label = 'L11Q'";
            $result = $db->query($sql);
            Assert::assertNotFalse($result);
            Assert::assertSame("produit/".$newRef, $db->fetch_object($result)->filepath);
        } finally {
            unset($conf->global->{ProductIdentifier::ALLOW_UNSECURED_REFS});
            $db->query("DELETE FROM ".MAIN_DB_PREFIX."ecm_files WHERE label = 'L11Q'");
        }

        $this->deleteProduct($productId);
    }

    /**
     * Product References Normalization Cases
     *
     * @return array<string, array{string, string}>
     */
    public function normalizeProvider(): array
    {
        return array(
            'plain' => array("SKU-001", "SKU-001"),
            'allowed chars' => array("A.B_C-D", "A.B_C-D"),
            'slash' => array("SAM-DIRT2/3-LAC", "SAM-DIRT2_3-LAC"),
            'trimmed' => array("  A/B  ", "A_B"),
            'inner space' => array("A B", "A_B"),
            'path chars' => array("A\\B:C*D?E", "A_B_C_D_E"),
            'html chars' => array("A\"B<C>D|E", "A_B_C_D_E"),
            'brackets' => array("A[B];C", "A_B__C"),
        );
    }
}
