<?php
namespace Splash\Local\Tests;

use Splash\Tests\Tools\ObjectsCase;
use Splash\Client\Splash;

use Commande;
use Facture;

/**
 * @abstract    Local Test Suite - Verify Writing of Orders & Invoices Status
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L05OrderInvoicesStatusTest extends ObjectsCase
{

    private static $Ids     = array();
    
    public function testCreateObjects()
    {
        $this->loadLocalTestSequence("Monolangual");
        
        $this->createObject("Order", "OrderDraft");
        $this->createObject("Invoice", "PaymentDraft");
    }
    
    private function createObject($ObjectType, $Status)
    {
        //====================================================================//
        //   Create Fake Order Data
        $Fields         =   $this->fakeFieldsList($ObjectType, ["status" => $Status], true);
        $FakeData       =   $this->fakeObjectData($Fields);
        
        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($ObjectType)->lock();
        $Id = Splash::object($ObjectType)->set(null, $FakeData);
        $this->assertNotEmpty($Id);

        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($ObjectType, $Id);
        self::$Ids[$ObjectType] = $Id;
        
        //====================================================================//
        //   Load Object
        $Object  =   Splash::object($ObjectType)->Load($Id);
        $this->assertNotEmpty($Object);
        $this->assertEquals(Commande::STATUS_DRAFT, $Object->statut);
        
        return $Object;
    }
    
    /**
     * @dataProvider statusProvider
     */
    public function testStatusChanges($ObjectType, $SplashStatus, $DolibarrStatus, $ExpectedRef = null)
    {
        //====================================================================//
        //   Update Status Directly on Module
        Splash::object($ObjectType)->lock();
        $Id = Splash::object($ObjectType)->set(self::$Ids[$ObjectType], ["status" => $SplashStatus]);
        $this->assertNotEmpty($Id);
        $this->assertEquals(self::$Ids[$ObjectType], $Id);

        //====================================================================//
        //   Load Object
        $Object  =   Splash::object($ObjectType)->Load($Id);
        $this->assertNotEmpty($Object);
        $this->assertEquals($DolibarrStatus, $Object->statut);

        //====================================================================//
        //   Verify Reference
        $this->assertContains($ExpectedRef, $Object->ref, "Splash Status: " . $SplashStatus);
        if ($ExpectedRef != "PROV") {
            $this->assertContains(dol_print_date($Object->date, '%y%m'), $Object->ref);
        }
        
        return;
    }
    
    public function statusProvider()
    {
        //====================================================================//
        // Init Dolibarr
        Splash::local();
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

        return array(
            //====================================================================//
            //   Tests For Order Objects
            array("Order",      "OrderDraft",       Commande::STATUS_DRAFT,     "PROV"),
            array("Order",      "OrderCanceled",    Commande::STATUS_CANCELED,  "CO"),
            array("Order",      "OrderDraft",       Commande::STATUS_DRAFT,     "CO"),
            array("Order",      "OrderProcessing",  Commande::STATUS_VALIDATED, "CO"),
//            array("Order",      "OrderInTransit",   Commande::STATUS_ACCEPTED),
            array("Order",      "OrderDelivered",   Commande::STATUS_CLOSED,    "CO"),
            
            //====================================================================//
            //   Tests For Invoices Objects
            array("Invoice",    "PaymentDraft",     Facture::STATUS_DRAFT,      "PROV"),
            array("Invoice",    "PaymentDue",       Facture::STATUS_VALIDATED,  "FA"),
            array("Invoice",    "PaymentComplete",  Facture::STATUS_CLOSED,     "FA"),
            array("Invoice",    "PaymentCanceled",  Facture::STATUS_ABANDONED,  "FA"),
        );
    }
    
    /**
     * @dataProvider statusOnCreateProvider
     */
    public function testStatusOnCreate($ObjectType, $SplashStatus, $DolibarrStatus, $ExpectedRef = null)
    {
        //====================================================================//
        //   Create Fake Order Data
        $Fields             =   $this->fakeFieldsList($ObjectType, [], true);
        $FakeData           =   $this->fakeObjectData($Fields);
        $FakeData["status"] =   $SplashStatus;
        
        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($ObjectType)->lock();
        $Id = Splash::object($ObjectType)->set(null, $FakeData);
        $this->assertNotEmpty($Id);
        
        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($ObjectType, $Id);
        
        //====================================================================//
        //   Load Object
        $Object  =   Splash::object($ObjectType)->Load($Id);
        $this->assertNotEmpty($Object);
        $this->assertEquals($DolibarrStatus, $Object->statut);

        //====================================================================//
        //   Verify Reference
        $this->assertContains($ExpectedRef, $Object->ref, "Splash Status: " . $SplashStatus);
        if ($ExpectedRef != "PROV") {
            $this->assertContains(dol_print_date($Object->date, '%y%m'), $Object->ref);
            $this->assertContains(dol_print_date($FakeData["date"], '%y%m'), $Object->ref);
        }
        
        return;
    }
    
    public function statusOnCreateProvider()
    {
        //====================================================================//
        // Init Dolibarr
        Splash::local();
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

        return array(
            //====================================================================//
            //   Tests For Order Objects
            array("Order",      "OrderDraft",       Commande::STATUS_DRAFT,     "PROV"),
            array("Order",      "OrderProcessing",  Commande::STATUS_VALIDATED, "CO"),
//            array("Order",      "OrderInTransit",   Commande::STATUS_ACCEPTED,  "CO"),
            array("Order",      "OrderDelivered",   Commande::STATUS_CLOSED,    "CO"),
            
            //====================================================================//
            //   Tests For Invoices Objects
            array("Invoice",    "PaymentDraft",     Facture::STATUS_DRAFT,      "PROV"),
            array("Invoice",    "PaymentDue",       Facture::STATUS_VALIDATED,  "FA"),
            array("Invoice",    "PaymentComplete",  Facture::STATUS_CLOSED,     "FA"),
        );
    }
}
