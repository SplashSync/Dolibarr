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

namespace Splash\Local\Objects\Product\Variants;

use Splash\Core\SplashCore as Splash;
use Splash\Local\Services\AttributesManager;
use Splash\Local\Services\VariantsManager;

/**
 * Product Variants Attributes Data Access
 */
trait AttributesTrait
{
    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
     * Build Attributes Fields using FieldFactory
     *
     * @return void
     */
    protected function buildVariantsAttributesFields(): void
    {
        global $langs;

        //====================================================================//
        // Ensure Product Variation Module is Active
        if (!self::isVariantEnabled()) {
            return;
        }

        $groupName = $langs->trans("ProductCombinations");

        //====================================================================//
        // Product Variation List - Variation Attribute Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("code")
            ->name("Code")
            ->inList("attributes")
            ->group($groupName)
            ->microData("http://schema.org/Product", "VariantAttributeCode")
            ->addOption("isUpperCase")
            ->isNotTested()
        ;
        //====================================================================//
        // Product Variation List - Variation Attribute Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("name")
            ->name("Name")
            ->inList("attributes")
            ->group($groupName)
            ->microData("http://schema.org/Product", "VariantAttributeName")
            ->isNotTested()
        ;
        //====================================================================//
        // Product Variation List - Variation Attribute Value
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("value")
            ->name("Value")
            ->inList("attributes")
            ->group($groupName)
            ->microData("http://schema.org/Product", "VariantAttributeValue")
            ->isNotTested()
        ;
    }

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getVariantsAttributesFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "attributes", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Load Product Attributes List
        $attributes = VariantsManager::getProductAttributes($this->object->id);
        //====================================================================//
        // READ Fields
        foreach ($attributes as $index => $details) {
            //====================================================================//
            // Get Variant Infos
            switch ($fieldId) {
                case 'code':
                    $value = $details['attribute']->ref;

                    break;
                case 'name':
                    $value = $details['attribute']->label;

                    break;
                case 'value':
                    $value = $details['value']->value;

                    break;
                default:
                    return;
            }

            self::lists()->insert($this->out, "attributes", $fieldId, $index, $value);
        }
        unset($this->in[$key]);
        //====================================================================//
        // Sort Attributes by Code
        if (is_array($this->out["attributes"])) {
            ksort($this->out["attributes"]);
        }
    }

    //====================================================================//
    // Fields Writing Functions
    //====================================================================//

    /**
     * Write Given Fields
     *
     * @param string     $fieldName Field Identifier / Name
     * @param null|array $fieldData Field Data
     *
     * @return void
     */
    protected function setVariantsAttributesFields(string $fieldName, ?array $fieldData): void
    {
        //====================================================================//
        // Safety Check
        if ("attributes" !== $fieldName) {
            return;
        }
        //====================================================================//
        // Update Products Attributes Ids
        $attributes = array();
        foreach ($fieldData ?? array() as $attrItem) {
            //====================================================================//
            // Check Product Attributes is Valid & Not More than 3 Options!
            if (!$this->isValidAttributeDefinition($attrItem)) {
                continue;
            }
            //====================================================================//
            // Load or Create Attribute by Name
            $attribute = AttributesManager::touchAttributeGroup($attrItem["code"], $attrItem["name"]);
            if (!$attribute) {
                return;
            }
            //====================================================================//
            // Load or Create Attribute Value by Name
            $attributeValue = AttributesManager::touchAttributeValue($attribute, $attrItem["value"]);
            if (!$attributeValue) {
                return;
            }
            $attributes[$attribute->id] = $attributeValue->id;
        }

        if (VariantsManager::setProductAttributes($this->object->id, $attributes)) {
            $this->needUpdate();
            $this->needUpdate("combination");
        }

        unset($this->in[$fieldName]);
    }

    //====================================================================//
    // CRUD Functions
    //====================================================================//

    /**
     * Check if Attribute Array is Valid for Writing
     *
     * @param array $attrData Attribute Array
     *
     * @return bool
     */
    private function isValidAttributeDefinition(array $attrData): bool
    {
        //====================================================================//
        // Check Attributes Code is Given
        if (empty($attrData["code"]) || !is_string($attrData["code"])) {
            return Splash::log()->err(" Product Attribute Code is Not Valid.");
        }
        //====================================================================//
        // Check Attributes Names are Given
        if (!$this->isValidScalarData($attrData, "name", "Public Name")) {
            return false;
        }
        //====================================================================//
        // Check Attributes Values are Given
        if (!$this->isValidScalarData($attrData, "value", "Value Name")) {
            return false;
        }

        return true;
    }

    /**
     * Check if Attribute Array is Valid for Writing
     *
     * @param array  $attrData Attribute Array
     * @param string $key      Data Key on Array
     * @param string $name     Data Name
     *
     * @return bool
     */
    private function isValidScalarData(array $attrData, string $key, string $name): bool
    {
        //====================================================================//
        // Check Attributes Values are Given
        if (empty($attrData[$key]) || !is_scalar($attrData[$key])) {
            return Splash::log()->err("Product Attribute ".$name." is Not Valid.");
        }

        return true;
    }
}
