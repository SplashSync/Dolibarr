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

use ProductAttribute;
use ProductAttributeValue;
use Splash\Local\Services\AttributesManager as AttrManager;
use Splash\Tests\Tools\ObjectsCase;

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
     * @param array  $attrValues
     *
     * @return void
     */
    public function testAttributesManager($attrCode, $attrName, $attrValues)
    {
        //====================================================================//
        //   Ensure Attribute Doesn't Already Exists
        $this->assertNull(AttrManager::getAttributeByCode($attrCode));

        //====================================================================//
        //   Create New Attribute
        $attribute = AttrManager::addAttribute($attrCode, $attrName);
        $this->assertInstanceOf(ProductAttribute::class, $attribute);
        $this->assertNotEmpty($attribute->id);
        $this->assertEquals($attrCode, $attribute->ref);
        $this->assertEquals($attrName, $attribute->label);

        //====================================================================//
        //   Test Loading Attribute
        $this->assertInstanceOf(ProductAttribute::class, AttrManager::getAttributeById($attribute->id));
        $this->assertInstanceOf(ProductAttribute::class, AttrManager::getAttributeByCode($attrCode));

        //====================================================================//
        //   Create New Attribute Values
        $values = array();
        foreach ($attrValues as $valueCode => $valueName) {
            //====================================================================//
            //   Create Attribute Value
            $value = AttrManager::addAttributeValue($attribute, $valueCode, $valueName);
            $this->assertInstanceOf(ProductAttributeValue::class, $value);
            $this->assertNotEmpty($value->id);
            $this->assertEquals($valueCode, $value->ref);
            $this->assertEquals($valueName, $value->value);

            //====================================================================//
            //   Test Loading Attribute Value
            $this->assertInstanceOf(
                ProductAttributeValue::class,
                AttrManager::getAttributeValueById($attribute, $value->id)
            );
            $this->assertInstanceOf(
                ProductAttributeValue::class,
                AttrManager::getAttributeValueByName($attribute, $valueName)
            );

            //====================================================================//
            // Store for Further Usage
            $values[$valueCode] = $value;
        }

        //====================================================================//
        //   Test Delete Attribute Rejected when Values Exists
        $this->assertFalse(AttrManager::removeAttribute($attribute));

        //====================================================================//
        //   Delete All Attribute Values
        foreach ($values as $valueCode => $value) {
            //====================================================================//
            //   Delete Attribute Value
            $this->assertTrue($value instanceof ProductAttributeValue);
            $this->assertTrue(AttrManager::removeAttributeValue($value));

            //====================================================================//
            //   Test Loading Attribute Value now Fail
            $this->assertNull(AttrManager::getAttributeValueById($attribute, $value->id));
            $this->assertNull(AttrManager::getAttributeValueByName($attribute, (string) $valueCode));
        }

        //====================================================================//
        //   Test Delete Attribute
        $this->assertTrue(AttrManager::removeAttribute($attribute));
    }

    /**
     * Generate a Combination of Dumy Attributes
     *
     * @return array
     */
    public function attributesProvider()
    {
        $index = rand(100, 1000);

        return array(
            //====================================================================//
            //   Dummy Attributes List
            array("CODE".$index, "Attr".$index,  array("S" => "Small", "M" => "Medium", "L" => "Large")),
            array("CODE".$index++, "Attr".$index,  array("S" => "Small", "M" => "Medium", "L" => "Large")),
            array("CODE".$index++, "Attr".$index,  array("S" => "Small", "M" => "Medium", "L" => "Large")),
        );
    }
}
