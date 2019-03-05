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

use Product;
use ProductCombination;
use ProductCombination2ValuePair;
use Splash\Core\SplashCore as Splash;

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
     * @var ProductCombination2ValuePair
     */
    private static $valuePair;

    /**
     * Array Of Products Conbinations Arrays
     *
     * @var array
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
     */
    public static function init()
    {
        global $db;

        if (isset(static::$combinations)) {
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
        if (isset(static::$combinationsCache[$fkParent])) {
            return static::$combinationsCache[$fkParent];
        }

        //====================================================================//
        // Load from Db
        $variants = static::$combinations->fetchAllByFkProductParent($fkParent);
        static::$combinationsCache[$fkParent] = is_array($variants) ? $variants : array();

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
        if ($combination->fetchByFkProductChild($productId) < 0) {
            return null;
        }

        //====================================================================//
        // Load Combination Attributes from Db if First Loading
        if (!isset(static::$attr2ValuesCache[$productId])) {
            $attr2Values = static::$valuePair->fetchByFkCombination($combination->id);
            static::$attr2ValuesCache[$productId] = is_array($attr2Values) ? $attr2Values : array();
        }

        return $combination;
    }

    /**
     * Add Variant Product
     *
     * @param Product $parentProduct Parent Product
     * @param Product $childProduct  Child Product
     *
     * @return null|ProductCombination
     */
    public static function addProductCombination(Product $parentProduct, Product $childProduct)
    {
        global $db, $user;

        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Create New Product Combination Class
        $combination = new ProductCombination($db);
        $combination->fk_product_parent = $parentProduct->id;
        $combination->fk_product_child = $childProduct->id;
        $combination->variation_price = 0;
        $combination->variation_price_percentage = false;
        $combination->variation_weight = 0;
        if ($combination->create($user) < 0) {
            return null;
        }

        //====================================================================//
        // Setup Combination Attributes
        static::$attr2ValuesCache[$childProduct->id] = array();

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
        if (null === $combination) {
            return array();
        }
        //====================================================================//
        // Safety Check
        if (!isset(static::$attr2ValuesCache[$productId])) {
            return array();
        }

        //====================================================================//
        // Parse Combination Attributes to Details Array
        $result = array();
        foreach (static::$attr2ValuesCache[$productId] as $valuePair) {
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
                'combination' => $combination,
                'valuePair' => $valuePair,
                'attribute' => $attribute,
                'value' => $attributeValue,
            );
        }

        return $result;
    }

    /**
     * Update Product Combination Attributes from Ids Array
     *
     * @param int   $productId  Rowid of Product
     * @param array $attributes Array of Attributes->id => Value->id
     *
     * @return bool
     */
    public static function setProductAttributes($productId, $attributes)
    {
        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Load Product Combination Class
        $combination = self::getProductCombination($productId);
        //====================================================================//
        // Safety Check
        if ((null === $combination) || !isset(static::$attr2ValuesCache[$productId])) {
            return false;
        }
        //====================================================================//
        // Update Combination Attributes
        foreach ($attributes as $attributeId => $valueId) {
            //====================================================================//
            // Update Combination Attribute
            self::setProductAttribute(
                $combination,
                array_shift(static::$attr2ValuesCache[$productId]),
                $attributeId,
                $valueId
            );
        }
        //====================================================================//
        // Reload Load Product Combination Class
        $attr2Values = static::$valuePair->fetchByFkCombination($combination->id);
        static::$attr2ValuesCache[$productId] = is_array($attr2Values) ? $attr2Values : array();

        return true;
    }

    /**
     * Update Product Combination Attributes 2 Value Pair from Ids Array
     *
     * @param ProductCombination                $combination Product Combination
     * @param null|ProductCombination2ValuePair $attr2Value  Combination Attribute 2 Value Pair if Existing
     * @param int                               $attributeId Product Attribute Id
     * @param int                               $valueId     Product Attribute Id
     *
     * @return bool
     */
    private static function setProductAttribute($combination, $attr2Value, $attributeId, $valueId)
    {
        global $db;

        //====================================================================//
        // Combination Attribute Do Not Exists
        if (empty($attr2Value)) {
            $attr2Value = new ProductCombination2ValuePair($db);
            $attr2Value->fk_prod_combination = $combination->id;
            $attr2Value->fk_prod_attr = $attributeId;
            $attr2Value->fk_prod_attr_val = $valueId;
            if ($attr2Value->create() < 0) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    " Unable to Create Product Combination ValuePair."
                );
            }

            return true;
        }

        //====================================================================//
        // Combination Attribute Already Exists
        if (($attr2Value->fk_prod_attr != $attributeId)
            || ($attr2Value->fk_prod_attr_val != $valueId)) {
            //====================================================================//
            // Delete Attribute Value from Db
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_attribute_combination2val";
            $sql .= " WHERE fk_prod_combination = ".(int) $attr2Value->fk_prod_combination;
            $sql .= " AND fk_prod_attr = ".(int) $attr2Value->fk_prod_attr;
            $db->query($sql);
            //====================================================================//
            // Update Attribute Value Parameters
            $attr2Value->fk_prod_combination = $combination->id;
            $attr2Value->fk_prod_attr = $attributeId;
            $attr2Value->fk_prod_attr_val = $valueId;
            //====================================================================//
            // Re-Create Attribute Value Parameters
            if ($attr2Value->create() < 0) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    " Unable to Update Product Combination ValuePair."
                );
            }
        }

        return true;
    }
}
