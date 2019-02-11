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
    use \Splash\Local\Core\ErrorParserTrait;
    
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
            if(strtolower($attributeCode) == strtolower($attribute->ref)) {
                return $attribute;
            }
        }
        
        return null;
    }    
    
    /**
     * Add Product Combinations Attribute
     * 
     * @param string $attributeCode Product Attribute Code
     * @param string $attributeName Product Attribute Name
     *
     * @return false|ProductAttribute
     */
    public static function addAttribute($attributeCode, $attributeName = null)
    {
        global $db, $user;
        
        //====================================================================//
        // Ensure Service Init
        self::init();

        //====================================================================//
        // Ensure Attribute Code Doesnt' Already Exists
        $existingAttribute = self::getAttributeByCode($attributeCode);
        if(null !== $existingAttribute) {
            return $existingAttribute;
        }
        
        //====================================================================//
        // Create New Attribute
        $attribute = new ProductAttribute($db);
        $attribute->ref = strtoupper($attributeCode);
        $attribute->label = is_string($attributeName) ? $attributeName : $attributeCode;
        
        if($attribute->create($user) < 0 ) {
            $this->catchDolibarrErrors($attribute);
            
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Create Product Attribute (" . $attributeCode . ")."
            );            
        }
        
        //====================================================================//
        // Reload Load Attributes Cache 
        static::$attributesCache = $attribute->fetchAll();        
        
        return $attribute;
    }    
    
    
    /**
     * Remove Product Combinations Attribute
     * 
     * @param ProductAttribute $attribute Product Attribute Class
     *
     * @return bool
     */
    public static function removeAttribute($attribute)
    {
        global $user;
        
        //====================================================================//
        // Ensure Service Init
        self::init();

        //====================================================================//
        // Ensure Attribute has No Child Values or Product
        if( ($attribute->countChildValues() > 0) || ($attribute->countChildProducts() > 0) ) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "Unable to Delete Product Attribute (" . $attribute->ref . "): Has Child Values or Product"
            );            
            
        }

        //====================================================================//
        // Delete Attribute
        if($attribute->delete($user) < 0 ) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Delete Product Attribute (" . $attribute->ref . ")."
            );            
        }
        
        //====================================================================//
        // Reload Load Attributes Cache 
        static::$attributesCache = $attribute->fetchAll();        
        
        return true;
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
	 * @param string $valueName      Product Attribute Value Name
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
    
    /**
     * Add Product Combinations Attribute VAlue
     * 
     * @param ProductAttribute $attribute Product Attribute Class
     * @param string $valueCode Product Attribute Value Code
     * @param string $valueName Product Attribute Value Name
     * 
     * @return bool
     */
    public static function addAttributeValue($attribute, $valueCode, $valueName = null)
    {
        global $db, $user;
        
        //====================================================================//
        // Ensure Service Init
        self::init();
        
        //====================================================================//
        // Create New Attribute
        $value = new ProductAttributeValue($db);
        $value->fk_product_attribute = $attribute->id;
        $value->ref = strtoupper($valueCode);
        $value->label = is_string($valueName) ? $valueName : $valueCode;
        
        if($value->create($user) < 0 ) {
            $this->catchDolibarrErrors($value);
            
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Create Product Attribute Value (" . $valueCode . ")."
            );            
        }        
        
        //====================================================================//
        // Reload Load Attributes Values Cache 
        self::loadAttributeValues($attribute->id);
        
        return $value;
    }       
    
    /**
     * Remove Product Combinations Attribute Value
     * 
     * @param ProductAttributeValue $value Product Attribute Value Class
     *
     * @return bool
     */
    public static function removeAttributeValue($value)
    {
//        global $user;
        
        //====================================================================//
        // Ensure Service Init
        self::init();

        //====================================================================//
        // Delete Attribute Value
        if($value->delete() < 0 ) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Delete Product Attribute Value (" . $value->ref . ")."
            );            
        }
        
        //====================================================================//
        // Reload Load Attributes Values Cache 
        self::loadAttributeValues($value->fk_product_attribute);
        
        return true;
    }       
}
