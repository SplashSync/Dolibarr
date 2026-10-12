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
     * @return array<int, ProductCombination>
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
        self::$combinationsCache[$fkParent] = array();
        foreach (is_array($variants) ? $variants : array() as $combination) {
            self::$combinationsCache[$fkParent][$combination->fk_product_child] = $combination;
        }

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
     * Build Variant Product Label the Way Dolibarr Does: Parent Label followed by Attributes Values
     *
     * @param Product $parent    Parent Product
     * @param int     $productId RowId of Variant Product
     *
     * @return string
     */
    public static function getVariantLabel(Product $parent, int $productId): string
    {
        //====================================================================//
        // Ensure Service Init
        self::init();

        return trim($parent->label.self::$combinations->getCombinationLabel($productId));
    }

    /**
     * Check if a Variant Label was Generated by Dolibarr
     *
     * A generated label is a parent label followed by values of the variant
     * attributes, current or replaced ones. Any other label was customized
     * by a user and must be kept.
     *
     * @param string   $label        Variant Product Label
     * @param string[] $parentLabels Parent Product Labels (current and previous)
     * @param int      $productId    RowId of Variant Product
     *
     * @return bool
     */
    public static function isGeneratedLabel(string $label, array $parentLabels, int $productId): bool
    {
        //====================================================================//
        // Collect Parent Labels
        $prefixes = array();
        foreach ($parentLabels as $parentLabel) {
            if (is_string($parentLabel) && ("" !== trim($parentLabel))) {
                $prefixes[] = preg_quote(trim($parentLabel), "/");
            }
        }
        if (empty($prefixes)) {
            return false;
        }
        //====================================================================//
        // Collect All Values of Variant Attributes, Used or Not
        $values = array();
        foreach (self::getProductAttributes($productId) as $details) {
            foreach (AttributesManager::getAttributeValues($details['attribute']) as $value) {
                if (is_string($value->value) && ("" !== trim($value->value))) {
                    $values[] = preg_quote(trim($value->value), "/");
                }
            }
        }
        //====================================================================//
        // Parent Label, followed by Attributes Values Only
        $pattern = "/^(?:".implode("|", array_unique($prefixes)).")";
        if (!empty($values)) {
            $pattern .= "(?:\\s+(?:".implode("|", array_unique($values))."))*";
        }

        return (bool) preg_match($pattern."$/u", trim($label));
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
     * Check if Current Product has unexpected Variants on this System
     *
     * @param int   $parentId RowId of Parent Product
     * @param array $variants Array of Variants Ids
     *
     * @return bool
     */
    public static function hasAdditionalVariants(int $parentId, array $variants): bool
    {
        return !empty(self::getAdditionalVariants($parentId, $variants));
    }

    /**
     * Identify Parent Product by Walking a Received Variants List
     * Returns the Parent Product of the First Sibling that Already Has a Combination
     *
     * @param array $variants Array of Variants Items (each with an "id" key)
     *
     * @return null|Product
     */
    public static function findParentFromVariantsList(array $variants): ?Product
    {
        global $db;

        foreach ($variants as $item) {
            //====================================================================//
            // Extract Sibling Product Id from Splash Encoded Reference
            if (!isset($item["id"]) || !is_string($item["id"])) {
                continue;
            }
            $childId = self::objects()->id($item["id"]);
            if (empty($childId)) {
                continue;
            }
            //====================================================================//
            // If Sibling Has a Combination, Return Its Parent Product
            $combination = self::getProductCombination((int) $childId);
            if (!$combination) {
                continue;
            }
            $parent = new Product($db);
            if ($parent->fetch($combination->fk_product_parent) > 0) {
                return $parent;
            }
        }

        return null;
    }

    /**
     * Get List of Product unexpected Variants on this System
     *
     * @param int   $parentId RowId of Parent Product
     * @param array $variants Array of Variants Ids
     *
     * @return array<int, ProductCombination>
     */
    public static function getAdditionalVariants(int $parentId, array $variants): array
    {
        //====================================================================//
        // Extract All Variants Product Ids from Given Inputs
        $productIds = self::extractVariantsProductIds($variants);
        if (null == $productIds) {
            return array();
        }
        //====================================================================//
        // Load Parent Product Variants
        $parentVariants = VariantsManager::getProductVariants($parentId);

        //====================================================================//
        // Compare Variants Lists
        // Key-Based Diff: Parent Combinations whose Child Id is NOT in the Received List
        return array_diff_key($parentVariants, array_flip($productIds));
    }

    /**
     * Move a List of Product Variants from one Parent to another
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
        global $db, $user;

        //====================================================================//
        // Combination Attribute Do Not Exists
        if (empty($attr2Value)) {
            $attr2Value = new ProductCombination2ValuePair($db);
            $attr2Value->fk_prod_combination = $combination->id;
            $attr2Value->fk_prod_attr = $attributeId;
            $attr2Value->fk_prod_attr_val = $valueId;
            if ($attr2Value->create($user) < 0) {
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
            if ($attr2Value->create($user) < 0) {
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
