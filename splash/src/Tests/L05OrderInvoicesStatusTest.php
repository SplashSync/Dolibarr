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
     * @return void
     */
    public function testCreateObjects()
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
     * @param string      $splashStatus
     * @param string      $dolibarrStatus
     * @param null|string $expectedRef
     *
     * @return void
     */
    public function testStatusChanges($objectType, $splashStatus, $dolibarrStatus, $expectedRef = null)
    {
        //====================================================================//
        //   Update Status Directly on Module
        Splash::object($objectType)->lock();
        $objectId = Splash::object($objectType)->set(self::$objectsIds[$objectType], array("status" => $splashStatus));
        $this->assertTrue(false !== $objectId);
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
        $this->assertEquals($dolibarrStatus, $object->statut);

        //====================================================================//
        //   Verify Reference
        $this->assertStringContainsString((string) $expectedRef, $object->ref, "Splash Status: ".$splashStatus);
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
