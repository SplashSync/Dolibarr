<?php
namespace Splash\Local\Tests;

use Splash\Tests\Tools\ObjectsCase;
use Splash\Client\Splash;

/**
 * @abstract    Local Test Suite - Verify Writing of Orders & Invoices Lines with VAT Taxe Names Options
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L02TaxesByCodesTest extends ObjectsCase
{
    
    public function testEnableFeature()
    {
        global $db, $conf;
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

        //====================================================================//
        //   Enable feature
        dolibarr_set_const($db, "SPLASH_DETECT_TAX_NAME", 1, 'chaine', 0, '', $conf->entity);
        $this->assertEquals(1, $conf->global->SPLASH_DETECT_TAX_NAME);

        dolibarr_set_const($db, "FACTURE_TVAOPTION", 1, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, "FACTURE_LOCAL_TAX1_OPTION", 'localtax1on', 'chaine', 0, '', $conf->entity);
        
        //====================================================================//
        //   Configure Tax Classes
        $this->setTaxeCode(1, 5.5, "TVAFR05");      // French VAT  5%
        $this->setTaxeCode(1, 10, "TVAFR10");      // French VAT 10%
        $this->setTaxeCode(1, 20, "TVAFR20");      // French VAT 20%

        $this->setTaxeCode(14, 5, "CA-QC");        // Canadian Quebec VAT 5%
    }
    

    private function setTaxeCode($CountryId, $VatRate, $Code)
    {
        
        global $db;
        
        //====================================================================//
        //   Ensure Not Already Defined
        if ($this->isTaxeCode($CountryId, $VatRate, $Code)) {
            return;
        }
        
        //====================================================================//
        //   Update Tax Code
        $sql = "UPDATE " . MAIN_DB_PREFIX . "c_tva as t SET code = '" . $Code;
        $sql.= "' WHERE t.fk_pays = " . $CountryId . " AND t.taux = " . $VatRate;
        $resql = $db->query($sql);
        if (!$resql) {
            dol_print_error($db);
        }
        $db->free($resql);
    }
    
    private function isTaxeCode($CountryId, $VatRate, $Code)
    {
        
        global $db;
        
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "c_tva as t";
        $sql.= " WHERE t.fk_pays = " . $CountryId . " AND t.taux = " . $VatRate;
        $sql.= " AND t.code = '" . $Code . "'";
        
        $resql = $db->query($sql);
        if (!$resql) {
            dol_print_error($db);
        }
        
        return (bool) ($db->num_rows($resql) > 0);
    }
    
    /**
     * @dataProvider taxTypesProvider
     */
    public function testCreateWithTaxCode($ObjectType, $TaxCode, $VatRate1, $VatRate2)
    {
        if (Splash::local()->dolVersionCmp("5.0.0") < 0) {
            $this->markTestSkipped('Feature Not Available in This Version.');
            return;
        }

                    
        //====================================================================//
        //   Create Fake Order Data
        $Fields         =   $this->fakeFieldsList($ObjectType, ["desc@lines"], true);
        $FakeData       =   $this->fakeObjectData($Fields);
        
        //====================================================================//
        //   Setup Tax Names
        foreach ($FakeData["lines"] as $Index => $Data) {
            $FakeData["lines"][$Index]["vat_src_code"]   =   $TaxCode;
        }

        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($ObjectType)->lock();
        $ObjectId = Splash::object($ObjectType)->set(null, $FakeData);
        $this->assertNotEmpty($ObjectId);

        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($ObjectType, $ObjectId);
        
        //====================================================================//
        //   Read Order Data
        $ObjectData  =   Splash::object($ObjectType)
                ->get($ObjectId, ["desc@lines", "price@lines", "vat_src_code@lines"]);
        
        //====================================================================//
        //   verify Tax Values
        foreach ($ObjectData["lines"] as $Data) {
            $this->assertEquals($TaxCode, $Data["vat_src_code"]);
            $this->assertEquals($VatRate1, $Data["price"]["vat"]);
        }

        //====================================================================//
        //   Load Order Object
        $Object  =   Splash::object($ObjectType)->Load($ObjectId);
        //====================================================================//
        //   Verify Tax Values
        foreach ($Object->lines as $Line) {
            $this->assertEquals($TaxCode, $Line->vat_src_code);
            $this->assertEquals($VatRate1, $Line->tva_tx);
            $this->assertEquals($VatRate2, $Line->localtax1_tx);
        }
        
        //====================================================================//
        //   Return Basic Tax Names
        foreach ($FakeData["lines"] as $Index => $Data) {
            $FakeData["lines"][$Index]["vat_src_code"]   =   "";
        }

        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($ObjectType)->Lock($ObjectId);
        $WriteId = Splash::object($ObjectType)->set($ObjectId, $FakeData);
        $this->assertNotEmpty($WriteId);

        //====================================================================//
        //   Read Order Data
        $ObjectData2  =   Splash::object($ObjectType)
                ->get($ObjectId, ["desc@lines", "price@lines", "vat_src_code@lines"]);
        
        //====================================================================//
        //   verify Tax Values
        foreach ($ObjectData2["lines"] as $Data) {
            $this->assertEquals("", $Data["vat_src_code"]);
            $this->assertEquals(20, $Data["price"]["vat"]);
        }
    }
    
    public function testDisableFeature()
    {
        global $db, $conf;
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

        dolibarr_set_const($db, "SPLASH_DETECT_TAX_NAME", 0, 'chaine', 0, '', $conf->entity);
        $this->assertEquals(0, $conf->global->SPLASH_DETECT_TAX_NAME);
        
        dolibarr_set_const($db, "FACTURE_TVAOPTION", 0, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, "FACTURE_LOCAL_TAX1_OPTION", '', 'chaine', 0, '', $conf->entity);
    }
    
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
}
