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

namespace Splash\Local\Services;

use Exception;
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
    private static ProductCombination $combinations;

    /**
     * Products Combinations Values Pairs Static Instance
     *
     * @var ProductCombination2ValuePair
     */
    private static ProductCombination2ValuePair $valuePair;

    /**
     * Array Of Products Combination Arrays
     *
     * @var array
     */
    private static array $combinationsCache = array();

    /**
     * Array Of Products Attributes to Value Arrays
     *
     * @var array
     */
    private static array $attr2ValuesCache = array();

    /**
     * Service Constructor
     *
     * @return void
     */
    public static function init()
    {
        global $db;

        if (isset(self::$combinations)) {
            return;
        }

        //====================================================================//
        // Load Required Dolibarr Product Variants Classes
        dol_include_once("/variants/class/ProductCombination.class.php");
        self::$combinations = new ProductCombination($db);

        dol_include_once("/variants/class/ProductCombination2ValuePair.class.php");
        self::$valuePair = new ProductCombination2ValuePair($db);
    }

    /**
     * Fetch Product Combinations Array
     *
     * @param int $fkParent RowId of parent product
     *
     * @return ProductCombination[]
     */
    public static function getProductVariants(int $fkParent): array
    {
        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Load from cache
        if (isset(self::$combinationsCache[$fkParent])) {
            return self::$combinationsCache[$fkParent];
        }

        //====================================================================//
        // Load from Db
        $variants = self::$combinations->fetchAllByFkProductParent($fkParent);
        self::$combinationsCache[$fkParent] = is_array($variants) ? $variants : array();

        return self::$combinationsCache[$fkParent];
    }

    /**
     * Check if Product has Combinations
     *
     * @param int $fkParent RowId of parent product
     *
     * @return bool
     */
    public static function hasProductVariants(int $fkParent): bool
    {
        return !empty(self::getProductVariants($fkParent));
    }

    /**
     * Fetch Product Combination Object
     *
     * @param int $productId RowId of Product
     *
     * @return null|ProductCombination
     */
    public static function getProductCombination(int $productId): ?ProductCombination
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
        if (!isset(self::$attr2ValuesCache[$productId])) {
            $attr2Values = self::$valuePair->fetchByFkCombination($combination->id);
            self::$attr2ValuesCache[$productId] = is_array($attr2Values) ? $attr2Values : array();
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
    public static function addProductCombination(Product $parentProduct, Product $childProduct): ?ProductCombination
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
        self::$attr2ValuesCache[$childProduct->id] = array();

        return $combination;
    }

    /**
     * Get Fetch Product Combination Attributes Array
     *
     * @param int $productId RowId of Product
     *
     * @return array
     */
    public static function getProductAttributes(int $productId): array
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
        if (!isset(self::$attr2ValuesCache[$productId])) {
            return array();
        }

        //====================================================================//
        // Parse Combination Attributes to Details Array
        $result = array();
        foreach (self::$attr2ValuesCache[$productId] as $valuePair) {
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
     * @param int   $productId  RowId of Product
     * @param array $attributes Array of Attributes->id => Value->id
     *
     * @return bool
     */
    public static function setProductAttributes(int $productId, array $attributes): bool
    {
        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Load Product Combination Class
        $combination = self::getProductCombination($productId);
        //====================================================================//
        // Safety Check
        if ((null === $combination) || !isset(self::$attr2ValuesCache[$productId])) {
            return false;
        }
        //====================================================================//
        // Update Combination Attributes
        $updated = false;
        foreach ($attributes as $attributeId => $valueId) {
            //====================================================================//
            // Update Combination Attribute
            $updated |= self::setProductAttribute(
                $combination,
                array_shift(self::$attr2ValuesCache[$productId]),
                $attributeId,
                $valueId
            );
        }
        //====================================================================//
        // Delete Others Combination Attributes
        if (!empty(self::$attr2ValuesCache[$productId])) {
            foreach (self::$attr2ValuesCache[$productId] as $index => $attr2Value) {
                //====================================================================//
                // Delete Combination Attribute
                $updated |= self::deleteProductAttribute($attr2Value);
                unset(self::$attr2ValuesCache[$productId][$index]);
            }
        }
        //====================================================================//
        // Reload Load Product Combination Class
        $attr2Values = self::$valuePair->fetchByFkCombination($combination->id);
        self::$attr2ValuesCache[$productId] = is_array($attr2Values) ? $attr2Values : array();

        return (bool) $updated;
    }

    /**
     * Check if Product is Locked by Splash
     * - Has Combinations
     * - If is Combination & is On Update
     *
     * @param int $productId RowId of the updated product
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function isProductLocked(int $productId): bool
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
     * @param int   $parentId RowId of Parent Product
     * @param array $variants Array of Variants Ids
     *
     * @return bool
     */
    public static function hasAdditionnalVariants(int $parentId, array $variants): bool
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
     * @param int   $parentId    RowId of Current Parent Product
     * @param array $variants    Array of Variants Ids
     * @param int   $newParentId RowId of New Parent Product
     *
     * @return bool
     */
    public static function moveAdditionalVariants(int $parentId, array $variants, int $newParentId): bool
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
            if (isset(self::$combinationsCache[$combination->fk_product_parent])) {
                unset(self::$combinationsCache[$combination->fk_product_parent]);
            }
            if (isset(self::$attr2ValuesCache[$combination->fk_product_child])) {
                unset(self::$attr2ValuesCache[$combination->fk_product_child]);
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
     * @param int                               $attributeId Product Attribute ID
     * @param int                               $valueId     Product Attribute ID
     *
     * @return bool
     */
    private static function setProductAttribute(
        ProductCombination            $combination,
        ?ProductCombination2ValuePair $attr2Value,
        int $attributeId,
        int $valueId
    ): bool {
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
            $sql .= " WHERE fk_prod_combination = ".$attr2Value->fk_prod_combination;
            $sql .= " AND fk_prod_attr = ".$attr2Value->fk_prod_attr;
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
    private static function deleteProductAttribute(ProductCombination2ValuePair $attr2Value): bool
    {
        global $db;

        //====================================================================//
        // Delete Attribute Value from Db
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_attribute_combination2val WHERE rowid = ".$attr2Value->id;
        if ($db->query($sql)) {
            return true;
        }

        return Splash::log()->errTrace("Unable to Delete Product Combination ValuePair.");
    }

    /**
     * Check if All Given Product Variants Exists on this System
     *
     * @param array $variants Array of Variants Ids
     *
     * @return null|array
     */
    private static function extractVariantsProductIds(array $variants): ?array
    {
        $productIds = array();
        //====================================================================//
        // Walk on All Given Variants Items
        foreach ($variants as $index => $variant) {
            //====================================================================//
            // Check if Variant Item has Variant Product Id
            if (empty($variant["id"])) {
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
     * Update Product Combination Parent Product ID
     *
     * @param ProductCombination $combination Product Combination
     * @param int                $parentId    New Parent Product
     *
     * @return bool
     */
    private static function updateCombinationParent(ProductCombination $combination, int $parentId): bool
    {
        global $db;

        //====================================================================//
        // Update Combination Product Id
        $sql = "UPDATE ".MAIN_DB_PREFIX."product_attribute_combination "
            ." SET fk_product_parent = ".$parentId." WHERE rowid = ".$combination->id;
        if (!$db->query($sql)) {
            return false;
        }

        return true;
    }
}
