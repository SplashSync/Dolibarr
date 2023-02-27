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

use Commande;
use Exception;
use Facture;
use Splash\Client\Splash;
use Splash\Local\Objects\Invoice;
use Splash\Local\Objects\Order;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify Writing of Orders & Invoices Status
 */
class L05OrderInvoicesStatusTest extends ObjectsCase
{
    /**
     * @var array
     */
    private static $objectsIds = array();

    /**
     * @throws Exception
     *
     * @return void
     */
    public function testCreateObjects(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $this->createObject("Order", "OrderDraft");
        $this->createObject("Invoice", "PaymentDraft");
    }

    /**
     * Test Order & Invoices Status
     *
     * @dataProvider statusProvider
     *
     * @param string      $objectType
     * @param string      $splStatus
     * @param int         $dolStatus
     * @param null|string $expectedRef
     *
     *@throws Exception
     *
     * @return void
     */
    public function testStatusChanges(
        string $objectType,
        string $splStatus,
        int $dolStatus,
        string $expectedRef = null
    ): void {
        //====================================================================//
        //   Update Status Directly on Module
        Splash::object($objectType)->lock();
        $objectId = Splash::object($objectType)->set(self::$objectsIds[$objectType], array("status" => $splStatus));
        $this->assertNotEmpty($objectId);
        $this->assertEquals(self::$objectsIds[$objectType], $objectId);

        //====================================================================//
        //   Load Object
        $splashObject = Splash::object($objectType);
        $object = false;
        if (($splashObject instanceof Order) || ($splashObject instanceof Invoice)) {
            $object = $splashObject->load($objectId);
        }
        $this->assertTrue(false !== $object);
        $this->assertNotEmpty($object);
        $this->assertEquals($dolStatus, $object->statut);

        //====================================================================//
        //   Verify Reference
        $this->assertStringContainsString((string) $expectedRef, $object->ref, "Splash Status: ".$splStatus);
        if ("PROV" != $expectedRef) {
            $this->assertStringContainsString(dol_print_date($object->date, '%y%m'), $object->ref);
        }
    }

    /**
     * @return array
     */
    public function statusProvider(): array
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
            "Order: Draft"  => array("Order",      "OrderDraft",       Commande::STATUS_DRAFT,     "PROV"),
            "Order: Cancel" => array("Order",      "OrderCanceled",    Commande::STATUS_CANCELED,  "CO"),
            "Order: ReOpen" => array("Order",      "OrderDraft",       Commande::STATUS_DRAFT,     "CO"),
            "Order: Valid"  => array("Order",      "OrderProcessing",  Commande::STATUS_VALIDATED, "CO"),
            "Order: Ship"   => array("Order",      "OrderInTransit",   Commande::STATUS_SHIPMENTONPROCESS, "CO"),
            "Order: Done"   => array("Order",      "OrderDelivered",   Commande::STATUS_CLOSED,    "CO"),

            //====================================================================//
            //   Tests For Invoices Objects
            "Inv: Draft"    => array("Invoice",    "PaymentDraft",     Facture::STATUS_DRAFT,      "PROV"),
            "Inv: Cancel"   => array("Invoice",    "PaymentCanceled",  Facture::STATUS_ABANDONED,  "PROV"),
            "Inv: Valid Ko" => array("Invoice",    "PaymentComplete",  Facture::STATUS_ABANDONED,  "PROV"),
            "Inv: Re Draft" => array("Invoice",    "PaymentDraft",     Facture::STATUS_DRAFT,      "PROV"),
            "Inv: Due"      => array("Invoice",    "PaymentDue",       Facture::STATUS_VALIDATED,  "FA"),
            "Inv: Closed"   => array("Invoice",    "PaymentComplete",  Facture::STATUS_CLOSED,     "FA"),
            "Inv: Cancel 2" => array("Invoice",    "PaymentCanceled",  Facture::STATUS_ABANDONED,  "FA"),
        );
    }

    /**
     * @dataProvider statusOnCreateProvider
     *
     * @param string      $objectType
     * @param string      $splashStatus
     * @param int         $dolibarrStatus
     * @param null|string $expectedRef
     *
     * @throws Exception
     *
     * @return void
     */
    public function testStatusOnCreate(
        string $objectType,
        string $splashStatus,
        int $dolibarrStatus,
        $expectedRef = null
    ) {
        //====================================================================//
        //   Create Fake Order Data
        $fields = $this->fakeFieldsList($objectType, array(), true);
        $fakeData = $this->fakeObjectData($fields);
        $fakeData["status"] = $splashStatus;

        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($objectType)->lock();
        $objectId = Splash::object($objectType)->set(null, $fakeData);
        $this->assertTrue(false !== $objectId);
        $this->assertNotEmpty($objectId);

        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($objectType, $objectId);

        //====================================================================//
        //   Load Object
        $splashObject = Splash::object($objectType);
        $object = false;
        if (($splashObject instanceof Order) || ($splashObject instanceof Invoice)) {
            $object = $splashObject->load($objectId);
        }
        $this->assertTrue(false !== $object);
        $this->assertNotEmpty($object);
        $this->assertEquals($dolibarrStatus, $object->statut);

        //====================================================================//
        //   Verify Reference
        $this->assertStringContainsStringIgnoringCase(
            (string) $expectedRef,
            $object->ref,
            "Splash Status: ".$splashStatus
        );
        if ("PROV" != $expectedRef) {
            $this->assertStringContainsStringIgnoringCase(dol_print_date($object->date, '%y%m'), $object->ref);
            // @phpstan-ignore-next-line
            $this->assertStringContainsStringIgnoringCase(dol_print_date($fakeData["date"], '%y%m'), $object->ref);
        }
    }

    /**
     * @return array
     */
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
            "Order: Draft"  => array("Order",      "OrderDraft",       Commande::STATUS_DRAFT,     "PROV"),
            "Order: Valid"  => array("Order",      "OrderProcessing",  Commande::STATUS_VALIDATED, "CO"),
            "Order: Ship"   => array("Order",      "OrderInTransit",   Commande::STATUS_ACCEPTED,  "CO"),
            "Order: Closed" => array("Order",      "OrderDelivered",   Commande::STATUS_CLOSED,    "CO"),

            //====================================================================//
            //   Tests For Invoices Objects
            "Inv: Cancel"   => array("Invoice",    "PaymentCanceled",  Facture::STATUS_ABANDONED,      "PROV"),
            "Inv: Draft"    => array("Invoice",    "PaymentDraft",     Facture::STATUS_DRAFT,      "PROV"),
            "Inv: Due"      => array("Invoice",    "PaymentDue",       Facture::STATUS_VALIDATED,  "FA"),
            "Inv: Paid"     => array("Invoice",    "PaymentComplete",  Facture::STATUS_CLOSED,     "FA"),
        );
    }

    /**
     * @param string $objectType
     * @param string $status
     *
     * @return Commande|Facture
     */
    private function createObject($objectType, $status)
    {
        //====================================================================//
        //   Create Fake Order Data
        $fields = $this->fakeFieldsList($objectType, array(), true);
        $fakeData = $this->fakeObjectData($fields);
        $fakeData["status"] = $status;

        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($objectType)->lock();
        $objectId = Splash::object($objectType)->set(null, $fakeData);
        $this->assertNotEmpty($objectId);
        $this->assertIsString($objectId);
        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($objectType, $objectId);
        self::$objectsIds[$objectType] = $objectId;

        //====================================================================//
        //   Load Object
        $splashObject = Splash::object($objectType);
        $object = false;
        if (($splashObject instanceof Order) || ($splashObject instanceof Invoice)) {
            $object = $splashObject->load($objectId);
        }

        $this->assertTrue(false !== $object);
        $this->assertNotEmpty($object);
        $this->assertEquals(Commande::STATUS_DRAFT, $object->statut);

        return $object;
    }
}
