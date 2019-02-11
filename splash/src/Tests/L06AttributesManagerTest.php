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

use Commande;
use Facture;
use Splash\Client\Splash;
use Splash\Tests\Tools\ObjectsCase;
use Splash\Local\Objects\Order;
use Splash\Local\Objects\Invoice;
use Splash\Local\Services\AttributesManager;
use ProductAttribute;
use ProductAttributeValue;

/**
 * Local Test Suite - Verify Read & Writing of Products Attributes
 */
class L06AttributesManagerTest extends ObjectsCase
{
    /**
     * Test Product Attributes CRUD
     *
     * @dataProvider attributesProvider
     *
     * @param string $attrCode
     * @param string $attrName
     * @param array $attrValues
     * 
     * @return void
     */
    public function testStatusChanges($attrCode, $attrName, $attrValues)
    {
        //====================================================================//
        //   Ensure Attribute Doesn't Already Exists
        $this->assertNull(AttributesManager::getAttributeByCode($attrCode));
        
        
        //====================================================================//
        //   Create New Attribute
        $attribute = AttributesManager::addAttribute($attrCode, $attrName);
        $this->assertInstanceOf(ProductAttribute::class, $attribute);
        $this->assertNotEmpty($attribute->id);
        $this->assertEquals($attrCode, $attribute->ref);
        $this->assertEquals($attrName, $attribute->label);
        
        //====================================================================//
        //   Test Loading Attribute 
        $this->assertInstanceOf(ProductAttribute::class, AttributesManager::getAttributeById($attribute->id));
        $this->assertInstanceOf(ProductAttribute::class, AttributesManager::getAttributeByCode($attrCode));

        //====================================================================//
        //   Create New Attribute Values
        $values = array();
        foreach ($attrValues as $valueCode => $valueName) 
        {
            //====================================================================//
            //   Create Attribute Value
            $value = AttributesManager::addAttributeValue($attribute, $valueCode, $valueName);
            $this->assertInstanceOf(ProductAttributeValue::class, $value);
            $this->assertNotEmpty($value->id);
            $this->assertEquals($valueCode, $value->ref);
            $this->assertEquals($valueName, $value->label);
            
            //====================================================================//
            //   Test Loading Attribute Value
            $this->assertInstanceOf(ProductAttributeValue::class, AttributesManager::getAttributeValueById($attribute, $value->id));
            $this->assertInstanceOf(ProductAttributeValue::class, AttributesManager::getAttributeValueByName($attribute, $valueCode));
            
            //====================================================================//
            // Store for Further Usage
            $values[$valueCode] = $value;
        }
        

        //====================================================================//
        //   Test Delete Attribute Rejected when Values Exists
        $this->assertFalse(AttributesManager::removeAttribute($attribute));
        
        //====================================================================//
        //   Delete All Attribute Values
        foreach ($values as $valueCode => $value) 
        {
            //====================================================================//
            //   Delete Attribute Value
            $this->assertTrue(AttributesManager::removeAttributeValue($value));
            
            //====================================================================//
            //   Test Loading Attribute Value now Fail
            $this->assertNull(AttributesManager::getAttributeValueById($attribute, $value->id));
            $this->assertNull(AttributesManager::getAttributeValueByName($attribute, $valueCode));
        }
        
        
        //====================================================================//
        //   Test Delete Attribute
        $this->assertTrue(AttributesManager::removeAttribute($attribute));
    }
    
    /**
     * Generate a Combination of Dumy Attributes
     * 
     * @return array
     */
    public function attributesProvider()
    {
        $randomIndex = random_int(100, 1000);
        
        return array(
            //====================================================================//
            //   Dummy Attributes List
            array("CODE" . $randomIndex, "Attr" . $randomIndex,  array("S" => "Small", "M" => "Medium", "L" => "Large")),
            array("CODE" . $randomIndex++, "Attr" . $randomIndex,  array("S" => "Small", "M" => "Medium", "L" => "Large")),
            array("CODE" . $randomIndex++, "Attr" . $randomIndex,  array("S" => "Small", "M" => "Medium", "L" => "Large")),
            
        );
    }
    
}
