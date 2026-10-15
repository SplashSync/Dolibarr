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
use Splash\Local\Dictionary\StaticContactTypes;
use Splash\Local\Services\ShippingMethods;
use Splash\Models\Helpers\ObjectsHelper;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify Order Delivery Company Matches the Delivery Contact
 */
class L13OrderDeliveryCompanyTest extends ObjectsCase
{
    /**
     * Shipping Methods Codes Detected as Store Pickup
     *
     * @var string[]
     */
    const MY_SOC_METHODS = array("CATCH", "SHOP", "DIRECT");

    /**
     * Test Delivery to a Contact of Another ThirdParty
     *
     * @throws Exception
     *
     * @return void
     */
    public function testContactOfAnotherThirdParty(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $buyerId = $this->createThirdParty();
        $recipientId = $this->createThirdParty();
        $orderId = $this->createOrder(
            $buyerId,
            $this->createAddress($recipientId),
            $this->getShippingMethod(false)
        );
        //====================================================================//
        // Company is the Delivery Contact ThirdParty
        Assert::assertSame($this->readName($recipientId), $this->readCompany($orderId));
        Assert::assertNotSame($this->readName($buyerId), $this->readCompany($orderId));
    }

    /**
     * Test Delivery to a Contact of the Order ThirdParty
     *
     * @throws Exception
     *
     * @return void
     */
    public function testContactOfOrderThirdParty(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $buyerId = $this->createThirdParty();
        $orderId = $this->createOrder(
            $buyerId,
            $this->createAddress($buyerId),
            $this->getShippingMethod(false)
        );
        Assert::assertSame($this->readName($buyerId), $this->readCompany($orderId));
    }

    /**
     * Test Order without Delivery Contact
     *
     * @throws Exception
     *
     * @return void
     */
    public function testWithoutDeliveryContact(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $buyerId = $this->createThirdParty();
        $orderId = $this->createOrder($buyerId, null, $this->getShippingMethod(false));
        //====================================================================//
        // Fallback on Order ThirdParty
        Assert::assertSame($this->readName($buyerId), $this->readCompany($orderId));
    }

    /**
     * Test Store Pickup: Delivery Address is MySoc
     *
     * @throws Exception
     *
     * @return void
     */
    public function testStorePickup(): void
    {
        global $mysoc;

        $this->loadLocalTestSequence("Monolangual");

        $methodCode = $this->getShippingMethod(true);
        if (!$methodCode) {
            $this->markTestSkipped("No Store Pickup Shipping Method Active.");
        }
        $buyerId = $this->createThirdParty();
        $orderId = $this->createOrder($buyerId, null, $methodCode);
        Assert::assertNotEmpty($mysoc->name);
        Assert::assertSame($mysoc->name, $this->readCompany($orderId));
    }

    /**
     * Read Delivery Company of an Order
     *
     * @param string $orderId
     *
     * @throws Exception
     *
     * @return null|string
     */
    private function readCompany(string $orderId): ?string
    {
        $data = Splash::object("Order")->get($orderId, array("company"));
        Assert::assertIsArray($data);

        return $data["company"] ?? null;
    }

    /**
     * Read Raw Dolibarr Name of a ThirdParty
     *
     * @param string $thirdPartyId
     *
     * @return string
     */
    private function readName(string $thirdPartyId): string
    {
        global $db;

        $thirdParty = new Societe($db);
        Assert::assertGreaterThan(0, $thirdParty->fetch((int) $thirdPartyId));
        Assert::assertNotEmpty($thirdParty->name);

        return (string) $thirdParty->name;
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
     * Create a Contact Address Attached to a ThirdParty
     *
     * @param string $thirdPartyId
     *
     * @throws Exception
     *
     * @return string
     */
    private function createAddress(string $thirdPartyId): string
    {
        $fields = $this->fakeFieldsList("Address", array(), true);
        $fakeData = $this->fakeObjectData($fields);
        $fakeData["socid"] = ObjectsHelper::encode("ThirdParty", $thirdPartyId);

        Splash::object("Address")->lock();
        $objectId = Splash::object("Address")->set(null, $fakeData);
        Assert::assertIsString($objectId);
        $this->addTestedObject("Address", $objectId);

        return $objectId;
    }

    /**
     * Create a Draft Order
     *
     * @param string      $thirdPartyId
     * @param null|string $addressId
     * @param null|string $methodCode
     *
     * @throws Exception
     *
     * @return string
     */
    private function createOrder(string $thirdPartyId, ?string $addressId, ?string $methodCode): string
    {
        $fields = $this->fakeFieldsList("Order", array(), true);
        $fakeData = $this->fakeObjectData($fields);
        $fakeData["status"] = "OrderDraft";
        $fakeData["socid"] = ObjectsHelper::encode("ThirdParty", $thirdPartyId);
        $fakeData[StaticContactTypes::SHIPPING] = $addressId
            ? ObjectsHelper::encode("Address", $addressId)
            : null
        ;
        $fakeData["shipping_method_id"] = $methodCode;

        Splash::object("Order")->lock();
        $orderId = Splash::object("Order")->set(null, $fakeData);
        Assert::assertIsString($orderId);
        $this->addTestedObject("Order", $orderId);

        return $orderId;
    }

    /**
     * Get Code of an Active Shipping Method
     *
     * @param bool $mySoc Store Pickup Method or Not
     *
     * @return null|string
     */
    private function getShippingMethod(bool $mySoc): ?string
    {
        foreach (ShippingMethods::getMethods() as $method) {
            if ($mySoc === in_array($method["code"], self::MY_SOC_METHODS, true)) {
                return $method["code"];
            }
        }
        if (!$mySoc) {
            $this->markTestSkipped("No Active Shipping Method.");
        }

        return null;
    }
}
