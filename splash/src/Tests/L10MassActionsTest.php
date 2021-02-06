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

use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Test for Massive Objects actions
 */
class L10MassActionsTest extends ObjectsCase
{
    use \Splash\Tests\Tools\Traits\ObjectsMassActionsTrait;

    /**
     * @var array
     */
    const CONFIG = array(
        "Address" => array(
            'max' => 50,
            'batch' => 7,
            'fields' => array(),
            'verify' => true,
            'update' => true,
            'delete' => true,
        ),
        "ThirdParty" => array(
            'max' => 50,
            'batch' => 5,
            'fields' => array(),
            'verify' => true,
            'update' => true,
            'delete' => true,
        ),
        "Product" => array(
            'max' => 50,
            'batch' => 5,
            'fields' => array(
                "images" => array(),
            ),
            'verify' => true,
            'update' => true,
            'delete' => true,
        ),
        "Order" => array(
            'max' => 50,
            'batch' => 5,
            'fields' => array(
                "images" => array(),
                "status" => "OrderDelivered",
            ),
            'verify' => true,
            'update' => false,
            'delete' => true,
        ),
        "Invoice" => array(
            'max' => 50,
            'batch' => 5,
            'fields' => array(
                "status" => "PaymentComplete",
            ),
            'verify' => true,
            'update' => false,
            'delete' => false,
        ),
        "CreditNote" => array(
            'max' => 10,
            'batch' => 5,
            'fields' => array(
                "status" => "PaymentComplete",
            ),
            'verify' => true,
            'update' => false,
            'delete' => false,
        ),
    );

    /**
     * Execute a Complete Mass Create/Update/Delete Test From Module
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @return void
     */
    public function testMassCrudActionsFromModule($sequence, $objectType)
    {
        //====================================================================//
        // Setup Test Mode
        $this->fromModule = true;
        //====================================================================//
        // Execute Mass Create / Update / Delete Test
        $this->baseMassCrudActions($sequence, $objectType);
    }

    /**
     * Execute a Complete Mass Create/Update/Delete Test From Service
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @return void
     */
    public function testMassCrudActionsFromService($sequence, $objectType)
    {
        //====================================================================//
        // Setup Test Mode
        $this->fromModule = false;
        //====================================================================//
        // Execute Mass Create / Update / Delete Test
        $this->baseMassCrudActions($sequence, $objectType);
    }

    /**
     * Execute a Complete Batch Create/Update/Delete Test From Service
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @return void
     */
    public function testBatchCrudActions($sequence, $objectType)
    {
        //====================================================================//
        // Ensure Object Config Exists & Is Valid
        $this->assertArrayHasKey($objectType, self::CONFIG);
        $cfg = self::CONFIG[$objectType];
        $this->assertArrayHasKey("max", $cfg);
        $this->assertArrayHasKey("batch", $cfg);
        $this->assertArrayHasKey("fields", $cfg);
        $this->assertArrayHasKey("verify", $cfg);
        $this->assertArrayHasKey("update", $cfg);
        $this->assertArrayHasKey("delete", $cfg);

        //====================================================================//
        // Setup Custom Objects Fields
        $this->customFieldsData = $cfg["fields"];

        if ($cfg["update"]) {
            //====================================================================//
            // Execute Mass Create / Update / Delete Test without Verifications
            $this->coreTestBatchCreateUpdateDelete(
                $sequence,
                $objectType,
                $cfg["max"],
                $cfg["batch"],
                $cfg["verify"],
                $cfg["delete"]
            );

            return;
        }

        //====================================================================//
        // Execute Mass Create / Delete Test without Verifications
        $this->coreTestBatchCreateDelete(
            $sequence,
            $objectType,
            $cfg["max"],
            $cfg["batch"],
            $cfg["verify"],
            $cfg["delete"]
        );
    }

    /**
     * Test Loading of Object that are not on Selected Entity
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @return void
     */
    private function baseMassCrudActions($sequence, $objectType)
    {
        //====================================================================//
        // Ensure Object Config Exists & Is Valid
        $this->assertArrayHasKey($objectType, self::CONFIG);
        $cfg = self::CONFIG[$objectType];
        $this->assertArrayHasKey("max", $cfg);
        $this->assertArrayHasKey("fields", $cfg);
        $this->assertArrayHasKey("verify", $cfg);
        $this->assertArrayHasKey("update", $cfg);
        $this->assertArrayHasKey("delete", $cfg);

        //====================================================================//
        // Setup Custom Objects Fields
        $this->customFieldsData = $cfg["fields"];

        if ($cfg["update"]) {
            //====================================================================//
            // Execute Mass Create / Update / Delete Test without Verifications
            $this->coreTestMassCreateUpdateDelete($sequence, $objectType, $cfg["max"], $cfg["verify"], $cfg["delete"]);

            return;
        }

        //====================================================================//
        // Execute Mass Create / Delete Test without Verifications
        $this->coreTestMassCreateDelete($sequence, $objectType, $cfg["max"], $cfg["verify"], $cfg["delete"]);
    }
}
