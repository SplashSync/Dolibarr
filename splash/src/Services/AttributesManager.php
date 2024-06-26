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

use ProductAttribute;
use ProductAttributeValue;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Core\ErrorParserTrait;

/**
 * Products Variants Attributes Manager
 */
class AttributesManager
{
    use ErrorParserTrait;

    /**
     * Array Of Products Attributes
     *
     * @var null|array
     */
    private static ?array $attributesCache;

    /**
     * Array Of Products Attributes Values
     *
     * @var array
     */
    private static array $attributesValuesCache = array();

    /**
     * Service Constructor
     *
     * @return ProductAttribute[]
     */
    public static function init(): array
    {
        global $db;

        if (!isset(self::$attributesCache)) {
            //====================================================================//
            // Load Attributes Cache
            dol_include_once("/variants/class/ProductAttribute.class.php");
            dol_include_once("/variants/class/ProductAttributeValue.class.php");
            self::$attributesCache = (new ProductAttribute($db))->fetchAll();
        }

        return self::$attributesCache;
    }

    /**
     * Load All Attribute Values from Database
     */
    public static function loadAttributeValues(int $attributeId, bool $reload = false): void
    {
        global $db;
        //====================================================================//
        // Ensure Service Init
        $attributes = self::init();
        //====================================================================//
        // Load Attributes Values Cache
        $attributeValue = new ProductAttributeValue($db);
        foreach ($attributes as $attribute) {
            //====================================================================//
            // This is Searched Attribute
            if ($attributeId != $attribute->id) {
                continue;
            }
            //====================================================================//
            // Load Attribute Values with Local Caching
            if ($reload) {
                self::$attributesValuesCache[$attributeId] = $attributeValue
                    ->fetchAllByProductAttribute($attributeId)
                ;
            } else {
                self::$attributesValuesCache[$attributeId] ??= $attributeValue
                    ->fetchAllByProductAttribute($attributeId)
                ;
            }
        }
    }

    /**
     * Fetch Product Combinations Attribute by ID
     *
     * @param int $attributeId Product Attribute ID
     *
     * @return null|ProductAttribute
     */
    public static function getAttributeById(int $attributeId): ?ProductAttribute
    {
        //====================================================================//
        // Ensure Service Init
        $attributes = self::init();
        //====================================================================//
        // Walk on Attributes Cache
        foreach ($attributes as $attribute) {
            if ($attributeId == $attribute->id) {
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
    public static function getAttributeByCode(string $attributeCode): ?ProductAttribute
    {
        //====================================================================//
        // Ensure Service Init
        $attributes = self::init();
        //====================================================================//
        // Walk on Attributes Cache
        foreach ($attributes as $attribute) {
            if (self::sanitizeName($attributeCode) == self::sanitizeName($attribute->ref)) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * Add Product Combinations Attribute
     *
     * @param string      $attributeCode Product Attribute Code
     * @param null|string $attributeName Product Attribute Name
     *
     * @return false|ProductAttribute
     */
    public static function addAttribute(string $attributeCode, string $attributeName = null)
    {
        global $db, $user;

        //====================================================================//
        // Ensure Attribute Code Doesnt' Already Exists
        $existingAttribute = self::getAttributeByCode($attributeCode);
        if (null !== $existingAttribute) {
            return $existingAttribute;
        }

        //====================================================================//
        // Create New Attribute
        $attribute = new ProductAttribute($db);
        $attribute->ref = self::sanitizeName($attributeCode);
        $attribute->label = is_string($attributeName) ? $attributeName : $attributeCode;

        if ($attribute->create($user) < 0) {
            return Splash::log()->errTrace("Unable to Create Product Attribute (".$attributeCode.").");
        }

        //====================================================================//
        // Reload Load Attributes Cache
        self::$attributesCache = $attribute->fetchAll();

        return $attribute;
    }

    /**
     * Update Product Combinations Attribute Name
     *
     * @param false|ProductAttribute $attribute     Product Attribute Object
     * @param string                 $attributeName Product Attribute Name
     *
     * @return bool
     */
    public static function updateAttribute($attribute, string $attributeName): bool
    {
        global $user;
        //====================================================================//
        // Ensure Attribute Is Here
        if (!($attribute instanceof ProductAttribute)) {
            return false;
        }
        //====================================================================//
        // Ensure Attribute Update is Required
        if ($attributeName == $attribute->label) {
            return true;
        }
        //====================================================================//
        // Update Attribute
        $attribute->label = $attributeName;
        if ($attribute->update($user) < 0) {
            return Splash::log()->errTrace("Unable to Create Product Attribute (".$attribute->ref.").");
        }

        return true;
    }

    /**
     * Load or Create Product Attribute Group
     *
     * @param string $code Attribute Group Code
     * @param string $name Attribute Group Name
     *
     * @return false|ProductAttribute
     */
    public static function touchAttributeGroup(string $code, string $name)
    {
        //====================================================================//
        // Load Product Attribute Group
        $attribute = self::getAttributeByCode($code);
        if (!$attribute) {
            //====================================================================//
            // Add Product Attribute Group
            $attribute = self::addAttribute($code, $name);
        }
        //====================================================================//
        // DEBUG MODE => Update Attributes Names
        if (Splash::isDebugMode()) {
            self::updateAttribute($attribute, $name);
        }

        return $attribute;
    }

    /**
     * Remove Product Combinations Attribute
     *
     * @param ProductAttribute $attribute Product Attribute Class
     *
     * @return bool
     */
    public static function removeAttribute(ProductAttribute $attribute): bool
    {
        global $user;
        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Ensure Attribute has No Child Values or Product
        if (($attribute->countChildValues() > 0) || ($attribute->countChildProducts() > 0)) {
            return Splash::log()->errTrace(
                "Unable to Delete Product Attribute (".$attribute->ref."): Has Child Values or Product"
            );
        }
        //====================================================================//
        // Delete Attribute
        if ($attribute->delete($user) < 0) {
            return Splash::log()->errTrace(
                " Unable to Delete Product Attribute (".$attribute->ref.")."
            );
        }
        //====================================================================//
        // Reload Load Attributes Cache
        self::$attributesCache = $attribute->fetchAll();

        return true;
    }

    /**
     * Fetch Product Combinations Attribute Value
     *
     * @param ProductAttribute $attribute Product Attribute
     * @param int              $valueId   Product Attribute Value Id
     *
     * @return null|ProductAttributeValue
     */
    public static function getAttributeValueById(ProductAttribute $attribute, int $valueId)
    {
        //====================================================================//
        // Ensure Service Init & Attribute Values are Loaded
        self::loadAttributeValues($attribute->id);
        //====================================================================//
        // Safety Check
        if (!is_array(self::$attributesValuesCache[$attribute->id])) {
            return null;
        }
        //====================================================================//
        // Walk on Attributes Cache
        foreach (self::$attributesValuesCache[$attribute->id] as $value) {
            if ($valueId == $value->id) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Fetch Product Combinations Attribute Value
     *
     * @param ProductAttribute $attribute Product Attribute
     * @param string           $valueName Product Attribute Value Name
     *
     * @return null|ProductAttributeValue
     */
    public static function getAttributeValueByName(ProductAttribute $attribute, string $valueName)
    {
        //====================================================================//
        // Ensure Service Init & Attribute Values are Loaded
        self::loadAttributeValues($attribute->id);
        //====================================================================//
        // Safety Check
        if (!is_array(self::$attributesValuesCache[$attribute->id])) {
            return null;
        }
        //====================================================================//
        // Walk on Attributes Cache
        foreach (self::$attributesValuesCache[$attribute->id] as $value) {
            if (self::sanitizeName($valueName) == self::sanitizeName($value->value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Add Product Combinations Attribute VAlue
     *
     * @param ProductAttribute $attribute Product Attribute Class
     * @param string           $valueCode Product Attribute Value Code
     * @param null|string      $valueName Product Attribute Value Name
     *
     * @return false|ProductAttributeValue
     */
    public static function addAttributeValue(ProductAttribute $attribute, string $valueCode, string $valueName = null)
    {
        global $db, $user;

        //====================================================================//
        // Ensure Service Init
        self::init();

        //====================================================================//
        // Create New Attribute
        $value = new ProductAttributeValue($db);
        $value->fk_product_attribute = $attribute->id;
        $value->ref = self::sanitizeName($valueCode);
        $value->value = is_string($valueName) ? $valueName : $valueCode;

        if ($value->create($user) < 0) {
            return Splash::log()->errTrace(
                "Unable to Create Product Attribute Value (".$valueCode."@".$attribute->ref.")."
            );
        }

        //====================================================================//
        // Reload Load Attributes Values Cache
        self::loadAttributeValues($attribute->id, true);

        return $value;
    }

    /**
     * Load or Create Product Attribute Value
     *
     * @param ProductAttribute $attribute Product Attribute Object
     * @param string           $value     Attribute Value
     *
     * @return false|ProductAttributeValue
     */
    public static function touchAttributeValue(ProductAttribute $attribute, string $value)
    {
        //====================================================================//
        // Load Product Attribute Value
        $attrValue = self::getAttributeValueByName($attribute, $value);
        if (!$attrValue) {
            //====================================================================//
            // Add Product Attribute Value
            $attrValue = self::addAttributeValue($attribute, $value);
        }

        return $attrValue;
    }

    /**
     * Remove Product Combinations Attribute Value
     *
     * @param ProductAttributeValue $value Product Attribute Value Class
     *
     * @return bool
     */
    public static function removeAttributeValue(ProductAttributeValue $value): bool
    {
        global $user;
        //====================================================================//
        // Ensure Service Init
        self::init();
        //====================================================================//
        // Delete Attribute Value
        if ($value->delete($user) < 0) {
            return Splash::log()->errTrace(
                "Unable to Delete Product Attribute Value (".$value->ref.")."
            );
        }
        //====================================================================//
        // Reload Load Attributes Values Cache
        self::loadAttributeValues($value->fk_product_attribute, true);

        return true;
    }

    private static function sanitizeName(string $name): string
    {
        return strtoupper(dol_sanitizeFileName(dol_string_nospecial(trim($name))));
    }
}
