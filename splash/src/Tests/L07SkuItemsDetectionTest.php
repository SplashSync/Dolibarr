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
use Splash\Local\Services\ProductIdentifier;
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
     * @throws Exception
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
        $this->verifyProductDetection("WhatEver", $infos["SplashId"], $infos["ObjectId"]);
        //====================================================================//
        //   Test Detection without Product Link
        $this->verifyProductDetection("WhatEver", null, null);
        //====================================================================//
        //   Test Detection with Wrong Product Link
        $this->verifyProductDetection("WhatEver", "ThisIsNoAnObjectId", null);
        //====================================================================//
        //   Test Detection with Product SKU
        $this->verifyProductDetection($infos["Ref"], null, null);
        $this->verifyProductDetection($infos["Ref"], "ThisIsNoAnObjectId", null);
    }

    /**
     * Test Product detection Without The Option
     *
     * @dataProvider ObjectTypesProvider
     *
     * @throws Exception
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
        $this->verifyProductDetection("WhatEver", $infos["SplashId"], $infos["ObjectId"]);
        //====================================================================//
        //   Test Detection without Product Link
        $this->verifyProductDetection("WhatEver", null, null);
        //====================================================================//
        //   Test Detection with Wrong Product Link
        $this->verifyProductDetection("WhatEver", "ThisIsNoAnObjectId", null);
        //====================================================================//
        //   Test Detection with Product SKU
        $this->verifyProductDetection($infos["Ref"], null, $infos["ObjectId"]);
        $this->verifyProductDetection($infos["Ref"], "ThisIsNoAnObjectId", $infos["ObjectId"]);
    }

    /**
     * Ensure We are in Correct ObjectType & Guest Mode is Allowed
     *
     * @throws Exception
     */
    public function isAllowedTestSequence(string $sequence, string $objectType): bool
    {
        //====================================================================//
        // Only For Orders & Invoices
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
     */
    private function verifyProductDetection(string $desc, ?string $fkProduct, ?int $result): void
    {
        //====================================================================//
        // Prepare Item Data
        $itemData = array(
            "desc" => $desc,
            "fk_product" => $fkProduct
        );
        //====================================================================//
        // Execute Product Detection
        $product = ProductIdentifier::findIdByLineItem($itemData);
        if ($result) {
            $this->assertInstanceOf(\Product::class, $product);
            $this->assertEquals($result, $product->id);
        } else {
            $this->assertNull($product);
        }
    }
}
