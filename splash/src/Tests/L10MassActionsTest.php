<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
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
    
    const CONFIG = array(
        "Address" => array(
            'max'       =>  100,
            'fields'    =>  array(),
            'verify'    =>  false,
            'update'    =>  true,
            'delete'    =>  true,
        ),
        "ThirdParty" => array(
            'max'       =>  100,
            'fields'    =>  array(),
            'verify'    =>  false,
            'update'    =>  false,
            'delete'    =>  true,
        ),
        "Product" => array(
            'max'       =>  100,
            'fields'    =>  array(
                "images"    => array(),
            ),
            'verify'    =>  false,
            'update'    =>  false,
            'delete'    =>  true,
        ),
        "Order" => array(
            'max'       =>  100,
            'fields'    =>  array(
                "images"    => array(),
                //                "status"    => "OrderCanceled"
                //                "status"    => "OrderInTransit"
                "status"    => "OrderDelivered",
            ),
            'verify'    =>  false,
            'update'    =>  true,
            'delete'    =>  true,
        ),
        "Invoice" => array(
            'max'       =>  3,
            'fields'    =>  array(
                "status" => "PaymentComplete",
            ),
            'verify'    =>  true,
            'update'    =>  false,
            'delete'    =>  false,
        ),
    );

    /**
     * Test Loading of Object that are not on Selected Entity
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
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
     * Test Loading of Object that are not on Selected Entity
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
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
     * Test Loading of Object that are not on Selected Entity
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     */
    public function baseMassCrudActions($sequence, $objectType)
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
