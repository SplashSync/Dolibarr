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
use Societe;
use Splash\Client\Splash;
use Splash\Local\Local;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify Duplicated Customer Accountancy Codes are Made Unique on Update
 */
class L14ThirdPartyAccountancyCodeTest extends ObjectsCase
{
    /**
     * Test a Duplicated Accountancy Code gets the First Free Numeric Suffix
     *
     * @throws Exception
     *
     * @return void
     */
    public function testDuplicatedCodeIsSuffixed(): void
    {
        $this->loadLocalTestSequence("Monolangual");
        $this->skipIfUniquenessNotSupported();
        $this->setUniqueAccountancyCode(true);

        try {
            $code = "SPL".strtoupper(substr(md5(uniqid()), 0, 8));
            $ownerId = $this->createThirdParty();
            $suffixedId = $this->createThirdParty();
            $duplicateId = $this->createThirdParty();
            //====================================================================//
            // First Suffix is Already Used by Another ThirdParty
            $this->setAccountancyCode($ownerId, $code);
            $this->setAccountancyCode($suffixedId, $code."1");
            $this->setAccountancyCode($duplicateId, $code);
            //====================================================================//
            // Update of the Duplicate Succeed & Uses the Next Free Suffix
            $this->updateThirdParty($duplicateId);
            Assert::assertSame($code."2", $this->readAccountancyCode($duplicateId));
            Assert::assertSame($code, $this->readAccountancyCode($ownerId));
            Assert::assertSame($code."1", $this->readAccountancyCode($suffixedId));
        } finally {
            $this->setUniqueAccountancyCode(false);
        }
    }

    /**
     * Test a Suffixed Accountancy Code Never Exceeds Dolibarr Max Length
     *
     * @throws Exception
     *
     * @return void
     */
    public function testSuffixedCodeKeepsMaxLength(): void
    {
        $this->loadLocalTestSequence("Monolangual");
        $this->skipIfUniquenessNotSupported();
        $this->setUniqueAccountancyCode(true);

        try {
            //====================================================================//
            // Code Never Ends with a Digit: a Suffix Must Never Match the Code Itself
            $code = "SPL".strtoupper(substr(md5(uniqid()), 0, 20))."X";
            Assert::assertSame(24, strlen($code));
            $ownerId = $this->createThirdParty();
            $duplicateId = $this->createThirdParty();
            $this->setAccountancyCode($ownerId, $code);
            $this->setAccountancyCode($duplicateId, $code);
            //====================================================================//
            // Last Char is Replaced by the Suffix
            $this->updateThirdParty($duplicateId);
            Assert::assertSame(substr($code, 0, 23)."1", $this->readAccountancyCode($duplicateId));
            Assert::assertSame($code, $this->readAccountancyCode($ownerId));
        } finally {
            $this->setUniqueAccountancyCode(false);
        }
    }

    /**
     * Customer Accountancy Code Uniqueness Option was Introduced in Dolibarr V20
     *
     * @return void
     */
    private function skipIfUniquenessNotSupported(): void
    {
        if (Local::dolVersionCmp("20.0.0") < 0) {
            $this->markTestSkipped("Customer accountancy code uniqueness requires Dolibarr V20+");
        }
    }

    /**
     * Enable / Disable Customer Accountancy Code Uniqueness
     *
     * @param bool $state
     *
     * @return void
     */
    private function setUniqueAccountancyCode(bool $state): void
    {
        global $db, $conf;

        dolibarr_set_const(
            $db,
            "SOCIETE_ACCOUNTANCY_CODE_CUSTOMER_UNIQUE",
            $state ? '1' : '0',
            'chaine',
            0,
            '',
            $conf->entity
        );
    }

    /**
     * Force Accountancy Code of a Customer, as Done by a Faulty Code Generator
     *
     * @param string $thirdPartyId
     * @param string $code
     *
     * @return void
     */
    private function setAccountancyCode(string $thirdPartyId, string $code): void
    {
        global $db;

        $sql = "UPDATE ".MAIN_DB_PREFIX."societe";
        $sql .= " SET client = 1, code_compta = '".$db->escape($code)."'";
        $sql .= " WHERE rowid = ".((int) $thirdPartyId);
        Assert::assertNotEmpty($db->query($sql));
    }

    /**
     * Read Raw Dolibarr Accountancy Code of a ThirdParty
     *
     * @param string $thirdPartyId
     *
     * @return string
     */
    private function readAccountancyCode(string $thirdPartyId): string
    {
        global $db;

        $thirdParty = new Societe($db);
        Assert::assertGreaterThan(0, $thirdParty->fetch((int) $thirdPartyId));

        return (string) $thirdParty->code_compta_client;
    }

    /**
     * Create a ThirdParty
     *
     * @throws Exception
     *
     * @return string
     */
    private function createThirdParty(): string
    {
        $fields = $this->fakeFieldsList("ThirdParty", array(), true);
        $fakeData = $this->fakeObjectData($fields);

        Splash::object("ThirdParty")->lock();
        $objectId = Splash::object("ThirdParty")->set(null, $fakeData);
        Assert::assertIsString($objectId);
        $this->addTestedObject("ThirdParty", $objectId);

        return $objectId;
    }

    /**
     * Update a ThirdParty Name
     *
     * @param string $thirdPartyId
     *
     * @throws Exception
     *
     * @return void
     */
    private function updateThirdParty(string $thirdPartyId): void
    {
        Splash::object("ThirdParty")->lock($thirdPartyId);
        Assert::assertSame(
            $thirdPartyId,
            Splash::object("ThirdParty")->set($thirdPartyId, array("name" => "Updated ".uniqid()))
        );
    }
}
