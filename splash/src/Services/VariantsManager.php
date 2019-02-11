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

use Splash\Local\Services\AttributesManager;

/**
 * Products Variants Manager
 */
class VariantsManager
{
    /**
     * Products Combinations Static Instance
     * 
     * @var ProductCombination
     */
    private static $combinations;

    /**
     * Products Combinations Values Pairs Static Instance
     * 
     * @var ProductCombination
     */
    private static $valuePair;

    /**
     * Array Of Products Conbinations Arrays
     * 
     * @var ProductCombination[]
     */
    private static $combinationsCache = array();

    /**
     * Array Of Products Attributes to Value Arrays
     * 
     * @var array
     */
    private static $attr2ValuesCache = array();

    /**
     * Service Constructor
     * 
     * @return void
     */
    public static function init() 
    {
        global $db;
        
        if(isset(static::$combinations)) {
            return;
        }
        
        //====================================================================//
        // Load Required Dolibarr Product Variants Classes
        dol_include_once("/variants/class/ProductCombination.class.php");
        static::$combinations = new ProductCombination($db);
        
        dol_include_once("/variants/class/ProductCombination2ValuePair.class.php");
        static::$valuePair = new ProductCombination2ValuePair($db);
    }
    
    /**
     * Fetch Product Combinations Array
     *
	 * @param int $fkParent Rowid of parent product
     *
     * @return ProductCombination[]
     */
    public static function getProductVariants($fkParent)
    {
        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Load from cache
        if(isset(static::$combinationsCache[$fkParent])) {
            return static::$combinationsCache[$fkParent];
        }
        
        //====================================================================//
        // Load from Db
        $variants = static::$combinations->fetchAllByFkProductParent($fkParent);
        static::$combinationsCache[$fkParent] = is_array($variants) ? $variants : array();
        
        
		foreach (static::$combinationsCache[$fkParent] as $prc) {
Splash::log()->www("Variants", static::$valuePair->fetchByFkCombination($prc->id));
		}        
        
        
        return static::$combinationsCache[$fkParent];
    }     
    
    /**
     * Check if Product has Combinations
     *
	 * @param int $fkParent Rowid of parent product
     *
     * @return bool
     */
    public static function hasProductVariants($fkParent)
    {
        return !empty(self::getProductVariants($fkParent));
    }    
    
    /**
     * Fetch Product Combinations Attribute
     *
	 * @param int $attributeId Product Attribute Id
     *
     * @return null|ProductAttribute
     */
    public static function getAttribute($attributeId)
    {
        global $db;
        //====================================================================//
        // Ensure Service Init
        self::init();
        
        //====================================================================//
        // Load from cache
        if(isset(static::$attributesCache[$attributeId])) {
            return static::$attributesCache[$attributeId];
        }
        
        //====================================================================//
        // Load from Db
        $attribute = new ProductAttribute($db);
        $attribute->fetch($attributeId);
        if($attribute->fetch($attributeId) < 0) {
            return null;
        }
        static::$attributesCache[$attributeId] = $attribute;
        
        //====================================================================//
        // Load Attribute Values from Db
        $attributeValue = new ProductAttributeValue($db);
        static::$attributesValuesCache[$attributeId] = $attributeValue->fetchAllByProductAttribute($attribute->id);
        
        return $attribute;
    }    

    /**
     * Fetch Product Combinations Attribute Value
     *
	 * @param int $attributeId  Product Attribute Id
	 * @param int $valueId      Product Attribute Value Id
     *
     * @return null|ProductAttributeValue
     */
    public static function getAttributeValue($attributeId, $valueId)
    {
        global $db;
        //====================================================================//
        // Ensure Service Init
        self::init();
        
        //====================================================================//
        // Load Attribute
        if(null === self::getAttribute($attributeId)) {
            return null;
        }
        
        //====================================================================//
        // Load from Db
        $attribute = new ProductAttribute($db);
        $attribute->fetch($attributeId);
        if($attribute->fetch($attributeId) < 0) {
            return null;
        }
        static::$attributesCache[$attributeId] = $attribute;
        
        //====================================================================//
        // Load Attribute Values from Db
        $attributeValue = new ProductAttributeValue($db);
        static::$attributesValuesCache[$attributeId] = $attributeValue->fetchAllByProductAttribute($attribute->id);
        
        return $attribute;
    }    
    
    
    /**
     * Fetch Product Combination Object
     *
	 * @param int $productId Rowid of Product
     *
     * @return null|ProductCombination
     */
    public static function getProductCombination($productId)
    {
        global $db;
        
        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Load Product Combination Class
        $combination = new ProductCombination($db);        
        if($combination->fetchByFkProductChild($productId) < 0) {
            return null;
        }
        
        //====================================================================//
        // Load Combination Attributes from Db if First Loading
        if(!isset(static::$attr2ValuesCache[$productId])) {
            $attr2Values = static::$valuePair->fetchByFkCombination($combination->id);
            static::$attr2ValuesCache[$productId] = is_array($attr2Values) ? $attr2Values : array();
        }        
        
        return $combination;
    }    

    /**
     * Get Fetch Product Combination Attributes Array
     *
	 * @param int $productId Rowid of Product
     *
     * @return array
     */
    public static function getProductAttributes($productId)
    {
        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Load Product Combination Class
        $combination = self::getProductCombination($productId);
        if(null === $combination) {
            return array();
        }
        //====================================================================//
        // Safety Check
        if(!isset(static::$attr2ValuesCache[$productId])) {
            return array();
        }  
        
        //====================================================================//
        // Parse Combination Attributes to Details Array
        $result = array();
        foreach (static::$attr2ValuesCache[$productId] as $valuePair) 
        {
            //====================================================================//
            // Load Attribute Class
            $attribute = AttributesManager::getAttributeById($valuePair->fk_prod_attr);
            if (null === $attribute) {
                continue;
            }
            //====================================================================//
            // Load Attribute Value Class
            $attributeValue = AttributesManager::getAttributeValueById($attribute, $valuePair->fk_prod_attr_val);
            //====================================================================//
            // Push to Details Array
            $result[] = array(
                'combination'   =>  $combination,
                'valuePair'     =>  $valuePair,
                'attribute'     =>  $attribute,
                'value'         =>  $attributeValue,
            );
        }
        
        return $result;
    }  
    
}
