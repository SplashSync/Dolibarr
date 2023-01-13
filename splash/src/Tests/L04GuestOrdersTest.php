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
use Splash\Client\Splash;
use Splash\Models\Helpers\ObjectsHelper;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify Guest Orders & Invoices Writing
 */
class L04GuestOrdersTest extends ObjectsCase
{
    /**
     * @var array<int|string, array>
     */
    protected array $fields;

    /**
     * @var array
     */
    private array $field;

    /**
     * Test Objects Have Corrects Fields Definitions
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @throws Exception
     *
     * @return void
     */
    public function testFieldsDefinitions(string $sequence, string $objectType): void
    {
        global $conf;

        //====================================================================//
        //   Only For Orders & Invoices
        if (!in_array($objectType, array("Order", "Invoice"), true)) {
            $this->assertTrue(true);

            return;
        }

        $this->loadLocalTestSequence($sequence);

        //====================================================================//
        //   Load Object Fields
        $fields = Splash::object($objectType)->fields();
        $this->assertNotEmpty($fields);

        //====================================================================//
        //   If Guest Mode not Active
        if (!$conf->global->SPLASH_GUEST_ORDERS_ALLOW) {
            //====================================================================//
            //   Verify SocId Field
            $socId = $this->findField($fields, array("socid"));
            $this->assertNotEmpty($socId);
            $this->assertIsArray($socId);
            $this->assertTrue($socId['required']);
            //====================================================================//
            //   Verify Email Field
            $this->assertEmpty($this->findField($fields, array("email")));

            return;
        }

        //====================================================================//
        //   If Guest Mode is Active
        //====================================================================//

        //====================================================================//
        //   Verify SocId Field
        $socId = $this->findField($fields, array("socid"));
        $this->assertNotEmpty($socId);
        $this->assertIsArray($socId);
        $this->assertFalse($socId['required']);
        //====================================================================//
        //   Verify Email Field
        $email = $this->findField($fields, array("email"));
        $this->assertNotEmpty($email);
        $this->assertIsArray($email);
        $this->assertEquals(SPL_T_EMAIL, $email['type']);
        $this->assertFalse($email['required']);
        $this->assertFalse($email['read']);
        $this->assertTrue($email['write']);
    }

    /**
     * Test Create & Update Without Customer Email
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @throws Exception
     *
     * @return void
     */
    public function testGuestWithoutEmailDetection(string $sequence, string $objectType)
    {
        global $db, $conf;

        //====================================================================//
        //   Safety Checks
        if (!$this->isAllowedGuestSequence($sequence, $objectType)) {
            return;
        }
        //====================================================================//
        //   Disable Email Detection
        dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_EMAIL", '0', 'chaine', 0, '', $conf->entity);

        //====================================================================//
        //   Create Fake Order/Invoice Data
        $this->fields = $this->fakeFieldsList($objectType);
        $this->field = array($this->findField($this->fields, array("socid")));
        $fakeData = $this->fakeObjectData($this->fields);

        //====================================================================//
        //   Setup Given
        $givenData = $fakeData;
        unset($givenData["socid"]);

        //====================================================================//
        //   Setup Expected Guest Customer Id
        $expectedData = ObjectsHelper::encode("ThirdParty", $conf->global->SPLASH_GUEST_ORDERS_CUSTOMER);

        //====================================================================//
        //   Verify On Create Operation
        $objectId = $this->verifyCreate($objectType, $givenData, array("socid" => $expectedData));

        //====================================================================//
        //   Verify On Update Operation => With Provided SocId
        $this->verifyUpdate($objectType, $objectId, $fakeData, array("socid" => $fakeData["socid"]));
        //====================================================================//
        //   Verify On Update Operation => Without SocId
        $this->verifyUpdate($objectType, $objectId, $givenData, array("socid" => $fakeData["socid"]));
        //====================================================================//
        //   Verify On Update Operation => With Empty SocId
        $givenData["socid"] = null;
        $this->verifyUpdate($objectType, $objectId, $givenData, array("socid" => $expectedData));
    }

    /**
     * Test Create & Update With Customer Email
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @return void
     */
    public function testGuestWithEmailDetection(string $sequence, string $objectType)
    {
        global $db, $conf;

        //====================================================================//
        //   Safety Checks
        if (!$this->isAllowedGuestSequence($sequence, $objectType)) {
            return;
        }
        //====================================================================//
        //   Disable Email Detection
        dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_EMAIL", '1', 'chaine', 0, '', $conf->entity);

        //====================================================================//
        //   Create Fake Order/Invoice Data
        $this->fields = $this->fakeFieldsList($objectType);
        $this->field = array($this->findField($this->fields, array("socid")));
        $fakeData = $this->fakeObjectData($this->fields);

        //====================================================================//
        //   Create Fake Customer with Email
        $customerData = $this->createThirdPartyWithEmail();

        //====================================================================//
        //   Setup Expected Guest Customer Id
        $expectedData = ObjectsHelper::encode("ThirdParty", $conf->global->SPLASH_GUEST_ORDERS_CUSTOMER);

        //====================================================================//
        //   Setup Given
        $givenData = $fakeData;
        unset($givenData["socid"]);
        $givenData["email"] = $customerData["Email"];

        //====================================================================//
        //   Verify On Create Operation => Without SocId (but Email)
        $objectId = $this->verifyCreate($objectType, $givenData, array("socid" => $customerData["ObjectId"]));
        //====================================================================//
        //   Verify On Update Operation => With Provided SocId
        $this->verifyUpdate($objectType, $objectId, $fakeData, array("socid" => $fakeData["socid"]));
        //====================================================================//
        //   Verify On Update Operation => Without SocId
        $this->verifyUpdate($objectType, $objectId, $givenData, array("socid" => $customerData["ObjectId"]));
        //====================================================================//
        //   Verify On Update Operation => With Provided SocId
        $this->verifyUpdate($objectType, $objectId, $fakeData, array("socid" => $fakeData["socid"]));
        //====================================================================//
        //   Verify On Update Operation => With Empty SocId  (but Email)
        $givenData["socid"] = null;
        $this->verifyUpdate($objectType, $objectId, $givenData, array("socid" => $customerData["ObjectId"]));
        //====================================================================//
        //   Verify On Update Operation => With Empty SocId and Empty Email
        $givenData["email"] = null;
        $this->verifyUpdate($objectType, $objectId, $givenData, array("socid" => $expectedData));
    }

    /**
     * Ensure We are in Correct ObjectType & Guest Mode is Allowed
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isAllowedGuestSequence(string $sequence, string $objectType)
    {
        global $conf;

        //====================================================================//
        //   Only For Orders & Invoices
        if (!in_array($objectType, array("Order", "Invoice"), true)) {
            $this->assertTrue(true);

            return false;
        }
        //====================================================================//
        //   Init Test Sequence
        $this->loadLocalTestSequence($sequence);
        //====================================================================//
        //   Only If Guest Mode is Active
        if (!$conf->global->SPLASH_GUEST_ORDERS_ALLOW) {
            $this->assertTrue(true);

            return false;
        }

        return true;
    }

    /**
     * Verify Creation of a Guest Order/Invoice
     *
     * @param string $objectType
     * @param array  $givenData
     * @param array  $expectedData
     *
     * @throws Exception
     *
     * @return string
     */
    private function verifyCreate(string $objectType, array $givenData, array $expectedData): string
    {
        //====================================================================//
        //   Create Object on Module
        Splash::object($objectType)->lock();
        $objectId = Splash::object($objectType)->set(null, $givenData);
        $this->assertNotEmpty($objectId);
        $this->assertIsString($objectId);

        //====================================================================//
        //   Verify Object Id Is Not Empty
        $this->assertNotEmpty($objectId, "Returned New Object Id is Empty");

        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($objectType, $objectId);

        //====================================================================//
        //   Read Object Data
        $objectData = Splash::object($objectType)
            ->get($objectId, array("socid"));

        //====================================================================//
        //   Verify Object Data are Ok
        $this->assertIsArray($objectData);
        $this->compareDataBlocks($this->field, $expectedData, $objectData, $objectType);

        return $objectId;
    }

    /**
     * Verify Creation of a Guest Order/Invoice
     *
     * @param string $objectType
     * @param string $objectId
     * @param array  $givenData
     * @param array  $expectedData
     *
     * @throws Exception
     *
     * @return string
     */
    private function verifyUpdate(string $objectType, string $objectId, array $givenData, array $expectedData): string
    {
        //====================================================================//
        //   Create Object on Module
        Splash::object($objectType)->lock();
        $writeObjectId = Splash::object($objectType)->set($objectId, $givenData);
        $this->assertNotEmpty($objectId);

        //====================================================================//
        //   Verify Object Id Is Not Empty
        $this->assertNotEmpty($writeObjectId, "Returned New Object Id is Empty");
        $this->assertIsString($writeObjectId);

        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($objectType, $objectId);

        //====================================================================//
        //   Read Object Data
        $objectData = Splash::object($objectType)
            ->get($objectId, array("socid"));

        //====================================================================//
        //   Verify Object Data are Ok
        $this->assertIsArray($objectData);
        $this->compareDataBlocks($this->field, $expectedData, $objectData, $objectType);

        return $writeObjectId;
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    private function createThirdPartyWithEmail(): array
    {
        //====================================================================//
        //   Create Fake ThirdParty Data
        $fields = $this->fakeFieldsList("ThirdParty");
        $fakeData = $this->fakeObjectData($fields);
        $this->assertNotEmpty($fakeData['email']);

        //====================================================================//
        //   Create ThirdParty on Module
        Splash::object("ThirdParty")->lock();
        $objectId = Splash::object("ThirdParty")->set(null, $fakeData);
        $this->assertNotEmpty($objectId);
        $this->assertIsString($objectId);

        return array(
            "ObjectId" => ObjectsHelper::encode("ThirdParty", $objectId),
            "Email" => $fakeData['email']
        );
    }
}
