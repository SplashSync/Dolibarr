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

namespace Splash\Local\Services;

use ArrayObject;
use Product;
use ProductCombination;
use ProductCombination2ValuePair;
use Splash\Core\SplashCore as Splash;
use Splash\Models\Objects\ObjectsTrait;

/**
 * Products Variants Manager
 */
class VariantsManager
{
    use ObjectsTrait;

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
     *
     * @return void
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
        if ($combination->fetchByFkProductChild($productId) <= 0) {
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
        global $db, $user, $conf;

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
        // Since DOL V13 => Populate Variant Prices levels
        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
            if (property_exists($combination, "combination_price_levels")
                && method_exists($combination, "fetchCombinationPriceLevels")) {
                $combination->fetchCombinationPriceLevels();
            }
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
        $updated = false;
        foreach ($attributes as $attributeId => $valueId) {
            //====================================================================//
            // Update Combination Attribute
            $updated |= (bool) self::setProductAttribute(
                $combination,
                array_shift(static::$attr2ValuesCache[$productId]),
                $attributeId,
                $valueId
            );
        }
        //====================================================================//
        // Delete Others Combination Attributes
        if (!empty(static::$attr2ValuesCache[$productId])) {
            foreach (static::$attr2ValuesCache[$productId] as $index => $attr2Value) {
                //====================================================================//
                // Delete Combination Attribute
                $updated |= (bool) self::deleteProductAttribute($attr2Value);
                unset(static::$attr2ValuesCache[$productId][$index]);
            }
        }
        //====================================================================//
        // Reload Load Product Combination Class
        $attr2Values = static::$valuePair->fetchByFkCombination($combination->id);
        static::$attr2ValuesCache[$productId] = is_array($attr2Values) ? $attr2Values : array();

        return (bool) $updated;
    }

    /**
     * Check if Product is Locked by Splash
     * - If has Combinations
     * - If is Combination & is On Update
     *
     * @param int $productId Rowid of the upated product
     *
     * @return bool
     */
    public static function isProductLocked($productId)
    {
        global $db;
        //====================================================================//
        // If has Combinations => Is a Base Product
        if (!empty(self::getProductVariants($productId))) {
            return true;
        }
        //====================================================================//
        // PhpUnit/Travis Mode => Do Not Filter Commits
        if (!empty(Splash::input('SPLASH_TRAVIS'))) {
            return false;
        }
        //====================================================================//
        // Prepare SQL request for Listing Combinations
        $sql = "SELECT  c.fk_product_child as id";
        $sql .= " FROM ".MAIN_DB_PREFIX."product_attribute_combination as src ";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination as c ";
        $sql .= " ON src.fk_product_parent = c.fk_product_parent ";
        $sql .= " WHERE src.fk_product_child = ".$productId;
        //====================================================================//
        // Execute request
        $resql = $db->query($sql);
        if (empty($resql) || (0 == $db->num_rows($resql))) {
            return false;
        }
        //====================================================================//
        // Walk on List of Combinations to Check Lock Flag
        $index = 0;
        while ($index < $db->num_rows($resql)) {
            $row = $db->fetch_object($resql);
            if (Splash::object("Product")->isLocked($row->id)) {
                return true;
            }
            $index++;
        }

        return false;
    }

    /**
     * Check if All Given Product Variants Exists on this System
     *
     * @param int               $parentId Rowid of Parent Product
     * @param array|ArrayObject $variants Array of Variants Ids
     *
     * @return bool
     */
    public static function hasAdditionnalVariants($parentId, $variants)
    {
        //====================================================================//
        // Extract All Variants Product Ids from Given Inputs
        $productIds = self::extractVariantsProductIds($variants);
        if (null == $productIds) {
            return false;
        }
        //====================================================================//
        // Load Parent Product Variants
        $parentVariants = VariantsManager::getProductVariants($parentId);
        //====================================================================//
        // Compare Variants Lists
        return (count($productIds) < count($parentVariants));
    }

    /**
     * Check if All Given Product Variants Exists on this System
     *
     * @param int               $parentId    Rowid of Current Parent Product
     * @param array|ArrayObject $variants    Array of Variants Ids
     * @param int               $newParentId Rowid of New Parent Product
     *
     * @return bool
     */
    public static function moveAdditionnalVariants($parentId, $variants, $newParentId)
    {
        //====================================================================//
        // Extract All Variants Product Ids from Given Inputs
        $productIds = self::extractVariantsProductIds($variants);
        if (null == $productIds) {
            return false;
        }
        //====================================================================//
        // Load Parent Product Variants
        $parentVariants = VariantsManager::getProductVariants($parentId);
        foreach ($parentVariants as $combination) {
            //====================================================================//
            // Check if Variants Need to Be Moved
            if (!in_array($combination->fk_product_child, $productIds, true)) {
                continue;
            }
            //====================================================================//
            // Delete Potential Caches
            if (isset(static::$combinationsCache[$combination->fk_product_parent])) {
                unset(static::$combinationsCache[$combination->fk_product_parent]);
            }
            if (isset(static::$attr2ValuesCache[$combination->fk_product_child])) {
                unset(static::$attr2ValuesCache[$combination->fk_product_child]);
            }
            //====================================================================//
            // Update Product Parent
            self::updateCombinationParent($combination, $newParentId);
        }

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
                return Splash::log()->errTrace("Unable to Create Product Combination ValuePair.");
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
                return Splash::log()->errTrace("Unable to Update Product Combination ValuePair.");
            }

            return true;
        }

        return false;
    }

    /**
     * Delete Product Combination Attributes 2 Value Pair from Ids Array
     *
     * @param ProductCombination2ValuePair $attr2Value Combination Attribute 2 Value Pair if Existing
     *
     * @return bool
     */
    private static function deleteProductAttribute($attr2Value)
    {
        global $db;

        //====================================================================//
        // Delete Attribute Value from Db
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_attribute_combination2val WHERE rowid = ".(int) $attr2Value->id;
        if ($db->query($sql)) {
            return true;
        }

        return Splash::log()->errTrace("Unable to Delete Product Combination ValuePair.");
    }

    /**
     * Check if All Given Product Variants Exists on this System
     *
     * @param array|ArrayObject $variants Array of Variants Ids
     *
     * @return null|array
     */
    private static function extractVariantsProductIds($variants)
    {
        $productIds = array();
        //====================================================================//
        // Walk on All Given Variants Items
        foreach ($variants as $index => $variant) {
            //====================================================================//
            // Check if Variant Item has Variant Product Id
            if (!isset($variant["id"]) || empty($variant["id"])) {
                return null;
            }
            //====================================================================//
            // Extract Product Id
            $productId = self::objects()->id($variant["id"]);
            if (empty($productId) || !is_scalar($productId)) {
                return null;
            }
            $productIds[$index] = $productId;
        }
        //====================================================================//
        // Check if Product Ids Identified
        if (empty($productIds)) {
            return null;
        }

        return $productIds;
    }

    /**
     * Update Product Combination Parent Product Id
     *
     * @param ProductCombination $combination Product Combination
     * @param int                $parentId    New Parent Product
     *
     * @return bool
     */
    private static function updateCombinationParent($combination, $parentId)
    {
        global $db;

        //====================================================================//
        // Update Combination Product Id
        $sql = "UPDATE ".MAIN_DB_PREFIX."product_attribute_combination "
            ." SET fk_product_parent = ".(int) $parentId." WHERE rowid = ".(int) $combination->id;
        if (!$db->query($sql)) {
            return false;
        }

        return true;
    }
}
