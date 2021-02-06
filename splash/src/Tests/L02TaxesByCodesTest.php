<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Tests;

use Splash\Client\Splash;
use Splash\Local\Local;
use Splash\Local\Objects\Invoice;
use Splash\Local\Objects\Order;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify Writing of Orders & Invoices Lines with VAT Taxe Names Options
 */
class L02TaxesByCodesTest extends ObjectsCase
{
    /**
     * @return void
     */
    public function testEnableFeature()
    {
        global $db, $conf;
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

        //====================================================================//
        //   Enable feature
        dolibarr_set_const($db, "SPLASH_DETECT_TAX_NAME", '1', 'chaine', 0, '', $conf->entity);
        $this->assertEquals(1, $conf->global->SPLASH_DETECT_TAX_NAME);

        dolibarr_set_const($db, "FACTURE_TVAOPTION", '1', 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, "FACTURE_LOCAL_TAX1_OPTION", 'localtax1on', 'chaine', 0, '', $conf->entity);

        //====================================================================//
        //   Configure Tax Classes
        $this->setTaxeCode(1, 5.5, "TVAFR05");     // French VAT  5%
        $this->setTaxeCode(1, 10, "TVAFR10");      // French VAT 10%
        $this->setTaxeCode(1, 20, "TVAFR20");      // French VAT 20%

        $this->setTaxeCode(14, 5, "CA-QC", 9.975);  // Canadian Quebec VAT 5%
    }

    /**
     * @dataProvider taxTypesProvider
     *
     * @param string $objectType
     * @param string $taxCode
     * @param string $vatRate1
     * @param string $vatRate2
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testCreateWithTaxCode($objectType, $taxCode, $vatRate1, $vatRate2)
    {
        if (Local::dolVersionCmp("5.0.0") < 0) {
            $this->markTestSkipped('Feature Not Available in This Version.');
        }

        //====================================================================//
        //   Create Fake Order Data
        $fields = $this->fakeFieldsList($objectType, array("desc@lines"), true);
        $fakeData = $this->fakeObjectData($fields);

        //====================================================================//
        //   Setup Tax Names
        foreach ($fakeData["lines"] as $index => $data) {
            $fakeData["lines"][$index]["vat_src_code"] = $taxCode;
        }

        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($objectType)->lock();
        $objectId = Splash::object($objectType)->set(null, $fakeData);
        $this->assertNotEmpty($objectId);
        $this->assertIsString($objectId);

        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($objectType, $objectId);

        //====================================================================//
        //   Read Order Data
        $objectData = Splash::object($objectType)
            ->get($objectId, array("desc@lines", "price@lines", "vat_src_code@lines"));
        $this->assertNotEmpty($objectData);
        $this->assertIsArray($objectData);

        //====================================================================//
        //   verify Tax Values
        foreach ($objectData["lines"] as $data) {
            $this->assertEquals($taxCode, $data["vat_src_code"]);
            $this->assertEquals($vatRate1, $data["price"]["vat"]);
        }

        //====================================================================//
        //   Load Order Object
        $splashObject = Splash::object($objectType);
        $object = false;
        if (($splashObject instanceof Order) || ($splashObject instanceof Invoice)) {
            $object = $splashObject->load($objectId);
        }
        $this->assertTrue(false !== $object);
        $this->assertNotEmpty($object);

        //====================================================================//
        //   Verify Tax Values
        foreach ($object->lines as $line) {
            $this->assertEquals($taxCode, isset($line->vat_src_code) ? $line->vat_src_code : 0);
            $this->assertEquals($vatRate1, $line->tva_tx);
            $this->assertEquals($vatRate2, $line->localtax1_tx);
        }

        //====================================================================//
        //   Return Basic Tax Names
        foreach ($fakeData["lines"] as $index => $data) {
            $fakeData["lines"][$index]["vat_src_code"] = "";
        }

        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($objectType)->Lock($objectId);
        $writeId = Splash::object($objectType)->set($objectId, $fakeData);
        $this->assertNotEmpty($writeId);

        //====================================================================//
        //   Read Order Data
        $objectData2 = Splash::object($objectType)
            ->get($objectId, array("desc@lines", "price@lines", "vat_src_code@lines"));
        $this->assertNotEmpty($objectData2);
        $this->assertIsArray($objectData2);

        //====================================================================//
        //   verify Tax Values
        foreach ($objectData2["lines"] as $data) {
            $this->assertEquals("", $data["vat_src_code"]);
            $this->assertEquals(20, $data["price"]["vat"]);
        }
    }

    /**
     * @return void
     */
    public function testDisableFeature()
    {
        global $db, $conf;
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

        dolibarr_set_const($db, "SPLASH_DETECT_TAX_NAME", '0', 'chaine', 0, '', $conf->entity);
        $this->assertEquals(0, $conf->global->SPLASH_DETECT_TAX_NAME);

        dolibarr_set_const($db, "FACTURE_TVAOPTION", '0', 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, "FACTURE_LOCAL_TAX1_OPTION", '', 'chaine', 0, '', $conf->entity);
    }

    /**
     * @return array
     */
    public function taxTypesProvider()
    {
        return array(
            //====================================================================//
            //   Tests For Order Objects
            array("Order",      "TVAFR05",    5.5, 0),
            array("Order",      "TVAFR10",    10, 0),
            array("Order",      "TVAFR20",    20, 0),
            array("Order",      "CA-QC",      5, 9.975),
            //====================================================================//
            //   Tests For Invoices Objects
            array("Invoice",    "TVAFR05",    5.5, 0),
            array("Invoice",    "TVAFR10",    10, 0),
            array("Invoice",    "TVAFR20",    20, 0),
            array("Invoice",    "CA-QC",      5, 9.975),
        );
    }

    /**
     * @param int    $countryId
     * @param float  $vatRate
     * @param string $code
     * @param float  $vatRate2
     *
     * @return void
     */
    private function setTaxeCode($countryId, $vatRate, $code, $vatRate2 = 0)
    {
        global $db;

        //====================================================================//
        //   Ensure Not Already Defined
        if ($this->isTaxeCode($countryId, $vatRate, $code)) {
            return;
        }
        //====================================================================//
        //   Count Tax Code
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_tva as t";
        $sql .= " WHERE t.fk_pays = ".$countryId." AND t.taux = ".$vatRate;
        $result = $db->query($sql);
        $this->assertNotEmpty($result);
        //====================================================================//
        //   Add Tax Code
        if (!$db->num_rows($result)) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_tva ";
            $sql .= " (`fk_pays`, `code`, `taux`, `localtax1`, `localtax1_type`,";
            $sql .= " `localtax2`, `localtax2_type`, `recuperableonly`, `note`, `active`)";
            $sql .= " VALUES ('".$countryId."', '".$code."', '".$vatRate."', '".$vatRate2."', ";
            $sql .= " '".($vatRate2 ? "1" : "0")."', '0', '0', '0', '".$code."', '1')";
            $result = $db->query($sql);
            if (!$result) {
                dol_print_error($db);
            }
            $db->free($result);

            return;
        }
        //====================================================================//
        //   Update Tax Code
        $sql = "UPDATE ".MAIN_DB_PREFIX."c_tva as t SET code = '".$code;
        $sql .= "' WHERE t.fk_pays = ".$countryId." AND t.taux = ".$vatRate;
        $result = $db->query($sql);
        if (!$result) {
            dol_print_error($db);
        }
        $db->free($result);
    }

    /**
     * @param int    $countryId
     * @param float  $vatRate
     * @param string $code
     *
     * @return bool
     */
    private function isTaxeCode($countryId, $vatRate, $code)
    {
        global $db;

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_tva as t";
        $sql .= " WHERE t.fk_pays = ".$countryId." AND t.taux = ".$vatRate;
        $sql .= " AND t.code = '".$code."'";

        $resql = $db->query($sql);
        if (!$resql) {
            dol_print_error($db);
        }

        return (bool) ($db->num_rows($resql) > 0);
    }
}
