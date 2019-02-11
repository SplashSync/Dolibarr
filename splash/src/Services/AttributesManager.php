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

namespace Splash\Local\Services;

use ProductCombination;
use ProductCombination2ValuePair;
use ProductAttribute;
use ProductAttributeValue;
use Splash\Core\SplashCore as Splash;

/**
 * Products Variants Attributes Manager
 */
class AttributesManager
{
    /**
     * Array Of Products Attributes
     * 
     * @var array
     */
    private static $attributesCache;

    /**
     * Array Of Products Attributes Values
     * 
     * @var array
     */
    private static $attributesValuesCache = array();
    
    /**
     * Service Constructor
     * 
     * @return void
     */
    public static function init() 
    {
        global $db;
        
        if(isset(static::$attributesCache)) {
            return;
        }
        
        //====================================================================//
        // Load Attributes Cache 
        dol_include_once("/variants/class/ProductAttribute.class.php");
        dol_include_once("/variants/class/ProductAttributeValue.class.php");
        static::$attributesCache = (new ProductAttribute($db))->fetchAll();
        
    }

    /**
     * Load All Attribute Values from Database
     * 
	 * @param int $attributeId Product Attribute Id
     * 
     * @return void
     */
    public static function loadAttributeValues($attributeId) 
    {
        global $db;
        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Load Attributes Values Cache
        $attributeValue = new ProductAttributeValue($db);
        foreach (static::$attributesCache as $attribute) {
            static::$attributesCache[$attribute->id] = $attributeValue->fetchAllByProductAttribute($attributeId);
        }
    }

    /**
     * Fetch Product Combinations Attribute by Id
     *
	 * @param int $attributeId Product Attribute Id
     *
     * @return null|ProductAttribute
     */
    public static function getAttributeById($attributeId)
    {
        //====================================================================//
        // Ensure Service Init
        self::init();
        
        //====================================================================//
        // Walk on Attributes Cache
        foreach (static::$attributesCache as $attribute) {
            if($attributeId == $attribute->id) {
                return $attribute;
            }
        }
        
        return null;
    }    

    /**
     * Fetch Product Combinations Attribute by Code
     *
	 * @param string $attributeCode Product Attribute Code
     *
     * @return null|ProductAttribute
     */
    public static function getAttributeByCode($attributeCode)
    {
        //====================================================================//
        // Ensure Service Init
        self::init();
        
        //====================================================================//
        // Walk on Attributes Cache
        foreach (static::$attributesCache as $attribute) {
            if($attributeCode == $attribute->ref) {
                return $attribute;
            }
        }
        
        return null;
    }    
    
    /**
     * Fetch Product Combinations Attribute Value
     *
	 * @param ProductAttribute $attribute  Product Attribute
	 * @param int $valueId      Product Attribute Value Id
     *
     * @return null|ProductAttributeValue
     */
    public static function getAttributeValueById($attribute, $valueId)
    {
        //====================================================================//
        // Ensure Service Init & Attribute Values are Loaded
        self::loadAttributeValues($attribute->id);
        //====================================================================//
        // Safety Check 
        if(!is_array(static::$attributesCache[$attribute->id])) {
            return null;
        }
        //====================================================================//
        // Walk on Attributes Cache
        foreach (static::$attributesCache[$attribute->id] as $value) {
            if($valueId == $value->id) {
                return $value;
            }
        }
       
        return null;
    }    
    
    /**
     * Fetch Product Combinations Attribute Value
     *
	 * @param ProductAttribute $attribute  Product Attribute
	 * @param int $valueName      Product Attribute Value Name
     *
     * @return null|ProductAttributeValue
     */
    public static function getAttributeValueByName($attribute, $valueName)
    {
        //====================================================================//
        // Ensure Service Init & Attribute Values are Loaded
        self::loadAttributeValues($attribute->id);
        //====================================================================//
        // Safety Check 
        if(!is_array(static::$attributesCache[$attribute->id])) {
            return null;
        }
        //====================================================================//
        // Walk on Attributes Cache
        foreach (static::$attributesCache[$attribute->id] as $value) {
            if($valueName == $value->ref) {
                return $value;
            }
        }
       
        return null;
    }    
    
}
