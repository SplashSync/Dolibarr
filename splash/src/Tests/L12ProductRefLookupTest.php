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
use Splash\Local\Services\ProductIdentifier;
use Splash\Local\Tests\Tools\ProductRefsTrait;
use Splash\Tests\Tools\TestCase;

/**
 * Local Test Suite - Verify Products are Found by their Normalized References
 */
class L12ProductRefLookupTest extends TestCase
{
    use ProductRefsTrait;

    /**
     * Test a Product Created with a Raw Reference is Found Again by Primary Key
     *
     * Regression test for the endless creation loop: "A/B" is stored as "A_B",
     * and a search on "A/B" must still find it.
     *
     * @dataProvider storageProvider
     *
     * @param string $ref Product reference, as received from the source
     *
     * @throws Exception
     *
     * @return void
     */
    public function testGetByPrimaryFindsNormalizedRef(string $ref): void
    {
        $productId = $this->createProduct($ref);

        Assert::assertSame(
            $productId,
            $this->getPrimaryAware()->getByPrimary(array("ref" => $ref))
        );

        $this->deleteProduct($productId);
    }

    /**
     * Test a Product Stored with an Unsanitized Reference is still Found
     *
     * References written without Dolibarr business objects (SQL imports,
     * older data) may be stored as is: raw search must keep finding them.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testGetByPrimaryFindsRawRef(): void
    {
        global $db;

        $rawRef = "PRIMRAW/".substr(uniqid(), -6);
        //====================================================================//
        // Create Product, then Force its Unsanitized Reference in Database
        $productId = $this->createProduct(ProductIdentifier::normalizeRef($rawRef));
        $sql = "UPDATE ".MAIN_DB_PREFIX."product SET ref = '".$db->escape($rawRef)."'";
        $sql .= " WHERE rowid = ".((int) $productId);
        Assert::assertNotFalse($db->query($sql));
        //====================================================================//
        // Raw Reference is Found
        Assert::assertSame(
            $productId,
            $this->getPrimaryAware()->getByPrimary(array("ref" => $rawRef))
        );

        $this->deleteProduct($productId);
    }

    /**
     * Test an Unknown Reference is not Found
     *
     * @return void
     */
    public function testGetByPrimaryUnknownRef(): void
    {
        Assert::assertNull(
            $this->getPrimaryAware()->getByPrimary(array("ref" => "UNKNOWN/".uniqid()))
        );
    }

    /**
     * Test Order Line SKU Detection Finds a Product Stored with a Normalized Reference
     *
     * The legacy SKU cleanup strips "/" ("A/B" => "AB") while Dolibarr stores
     * "A_B": the normalized form must also be tried.
     *
     * @dataProvider storageProvider
     *
     * @param string $ref Product reference, as received from the source
     *
     * @throws Exception
     *
     * @return void
     */
    public function testFindBySkuFindsNormalizedRef(string $ref): void
    {
        $productId = $this->createProduct($ref);

        $product = ProductIdentifier::findBySku($ref);
        Assert::assertInstanceOf(Product::class, $product);
        Assert::assertSame($productId, (string) $product->id);

        $this->deleteProduct($productId);
    }

    /**
     * Test Legacy SKU Cleanup is Preserved
     *
     * @throws Exception
     *
     * @return void
     */
    public function testFindBySkuKeepsLegacyCleanup(): void
    {
        $uniq = substr(uniqid(), -6);
        $productId = $this->createProduct("LEGACY".$uniq);
        //====================================================================//
        // Spaces & Brackets are Stripped, as before
        $product = ProductIdentifier::findBySku("[LEGACY ".$uniq."]");
        Assert::assertInstanceOf(Product::class, $product);
        Assert::assertSame($productId, (string) $product->id);

        $this->deleteProduct($productId);
    }
}
