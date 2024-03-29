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
use Splash\Tests\Tools\Traits\MethodInvokerTrait;

/**
 * Local Test Suite - Verify Product detection by SKU in Orders & Invoices Writing
 */
class L07SkuItemsDetectionTest extends ObjectsCase
{
    use MethodInvokerTrait;

    /**
     * Test Product detection Without The Option
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @return void
     */
    public function testDetectionWithoutTheOption(string $sequence, string $objectType): void
    {
        global $db, $conf;

        //====================================================================//
        //   Safety Checks
        if (!$this->isAllowedTestSequence($sequence, $objectType)) {
            return;
        }
        //====================================================================//
        //   Disable Sku Detection
        dolibarr_set_const($db, "SPLASH_DECTECT_ITEMS_BY_SKU", '0', 'chaine', 0, '', $conf->entity);
        //====================================================================//
        //   Create a New Product
        $infos = $this->createProduct();
        //====================================================================//
        //   Test Detection with Product Link
        $this->verifyProductDetection($objectType, "WhatEver", $infos["SplashId"], $infos["ObjectId"]);
        //====================================================================//
        //   Test Detection without Product Link
        $this->verifyProductDetection($objectType, "WhatEver", null, 0);
        //====================================================================//
        //   Test Detection with Wrong Product Link
        $this->verifyProductDetection($objectType, "WhatEver", "ThisIsNoAnObjectId", 0);
        //====================================================================//
        //   Test Detection with Product SKU
        $this->verifyProductDetection($objectType, $infos["Ref"], null, 0);
        $this->verifyProductDetection($objectType, $infos["Ref"], "ThisIsNoAnObjectId", 0);
    }

    /**
     * Test Product detection Without The Option
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @return void
     */
    public function testDetectionWithTheOption(string $sequence, string $objectType): void
    {
        global $db, $conf;

        //====================================================================//
        //   Safety Checks
        if (!$this->isAllowedTestSequence($sequence, $objectType)) {
            return;
        }
        //====================================================================//
        //   Disable Sku Detection
        dolibarr_set_const($db, "SPLASH_DECTECT_ITEMS_BY_SKU", '1', 'chaine', 0, '', $conf->entity);
        //====================================================================//
        //   Create a New Product
        $infos = $this->createProduct();
        //====================================================================//
        //   Test Detection with Product Link
        $this->verifyProductDetection($objectType, "WhatEver", $infos["SplashId"], $infos["ObjectId"]);
        //====================================================================//
        //   Test Detection without Product Link
        $this->verifyProductDetection($objectType, "WhatEver", null, 0);
        //====================================================================//
        //   Test Detection with Wrong Product Link
        $this->verifyProductDetection($objectType, "WhatEver", "ThisIsNoAnObjectId", 0);
        //====================================================================//
        //   Test Detection with Product SKU
        $this->verifyProductDetection($objectType, $infos["Ref"], null, $infos["ObjectId"]);
        $this->verifyProductDetection($objectType, $infos["Ref"], "ThisIsNoAnObjectId", $infos["ObjectId"]);
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
    public function isAllowedTestSequence(string $sequence, string $objectType)
    {
        //====================================================================//
        //   Only For Orders & Invoices
        if (!in_array($objectType, array("Order", "Invoice"), true)) {
            $this->assertTrue(true);

            return false;
        }
        //====================================================================//
        //   Init Test Sequence
        $this->loadLocalTestSequence($sequence);

        return true;
    }

    /**
     * Create a Product for Testing
     *
     * @throws Exception
     *
     * @return array
     */
    private function createProduct(): array
    {
        //====================================================================//
        //   Create Fake Product Data
        $fields = $this->fakeFieldsList("Product");
        $fakeData = $this->fakeObjectData($fields);
        $this->assertNotEmpty($fakeData['ref']);

        //====================================================================//
        //   Create Product on Module
        Splash::object("Product")->lock();
        $objectId = Splash::object("Product")->set(null, $fakeData);
        $this->assertNotEmpty($objectId);
        $this->assertIsString($objectId);

        return array(
            "SplashId" => ObjectsHelper::encode("Product", $objectId),
            "ObjectId" => $objectId,
            "Ref" => $fakeData['ref']
        );
    }

    /**
     * Test of Product Detection
     *
     * @param string      $objectType
     * @param string      $desc
     * @param null|string $fkProduct
     * @param int         $result
     *
     * @throws Exception
     *
     * @return void
     */
    private function verifyProductDetection(string $objectType, string $desc, ?string $fkProduct, int $result): void
    {
        //====================================================================//
        //   Load Tested Object
        $object = Splash::object($objectType);
        //====================================================================//
        //   Execute Test Detection
        $itemData = array(
            "desc" => $desc,
            "fk_product" => $fkProduct
        );
        $this->assertEquals(
            $result,
            $this->invokeMethod($object, "detectProductId", array($itemData))
        );
    }
}
