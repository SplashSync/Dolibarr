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

namespace Splash\Local\Objects\Product\Variants;

use Splash\Core\SplashCore      as Splash;

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
    protected function buildVariantsAttributesFields()
    {
        global $langs;        
        
        //====================================================================//
        // EEnsure Product Variation Module is Active
        if (!self::isVariantEnabled()) {
            return;
        }
        
        
        $groupName  = $langs->trans("ProductCombinations");

        //====================================================================//
        // Product Variation List - Variation Attribute Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("code")
            ->Name("Code")
            ->InList("attributes")
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "VariantAttributeCode")
            ->isNotTested();

        //====================================================================//
        // Product Variation List - Variation Attribute Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("name")
            ->Name("Name")
            ->InList("attributes")
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "VariantAttributeName")
            ->isNotTested();

        //====================================================================//
        // Product Variation List - Variation Attribute Value
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("value")
            ->Name("Value")
            ->InList("attributes")
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "VariantAttributeValue")
            ->isNotTested();
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
    protected function getVariantsAttributesFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "attributes", $fieldName);
        if (!$fieldId) {
            return;
        }
        
        $attributes = VariantsManager::getProductAttributes($this->object->id);

//        Splash::log()->www("Attr", $attributes);

        
        //====================================================================//
        // READ Fields
        foreach ($attributes as $index => $details) {
//            //====================================================================//
//            // Ensure Variant Option Exists
//            if (!isset($this->variant->{$name})) {
//                continue;
//            }

            //====================================================================//
            // Get Variant Infos
            switch ($fieldId) {
                case 'code':
                    $value  =   $details['attribute']->ref;
                    break;
                
                case 'name':
                    $value  =   $details['attribute']->label;
                    break;
                
                case 'value':
                    $value  =   $details['value']->value;
                    break;
                
                default:
                    return;
            }
            
            self::lists()->insert($this->out, "attributes", $fieldId, $index, $value);
        }
        unset($this->in[$key]);
        //====================================================================//
        // Sort Attributes by Code
        ksort($this->out["attributes"]);
    }
    
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setVariantsAttributesFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Safety Check
        if ("attributes" !== $fieldName) {
            return;
        }
      
        //====================================================================//
        // Update Products Attributes Ids
        $index = 0;
        foreach ($fieldData as $item) {
            //====================================================================//
            // Check Product Attributes is Valid & Not More than 3 Options!
            if (!$this->isValidAttributeDefinition($item) && ($index < 3)) {
                continue;
            }

            //====================================================================//
            // Update Attribute Name
            if (!isset($this->object->options[$index])) {
                $this->object->options[$index] = array(
                    'name' => $item["code"],
                    'position' => $index + 1,
                );
            }
            $this->object->options[$index]["name"] = $item["code"];
            
            //====================================================================//
            // Update Attribute Value
            $this->setSimple("option" . ($index + 1), $item["value"], "variant");

            //====================================================================//
            // Inc. Attribute Index
            $index++;
        }
        
        unset($this->in[$fieldName]);
    }

    //====================================================================//
    // CRUD Functions
    //====================================================================//

    /**
     * Check if Attribute Array is Valid for Writing
     *
     * @param mixed $attrData Attribute Array
     *
     * @return bool
     */
    private function isValidAttributeDefinition($attrData)
    {
        //====================================================================//
        // Check Attribute is Array
        if (!is_iterable($attrData) || empty($attrData)) {
            return false;
        }
        //====================================================================//
        // Check Attributes Code is Given
        if (!isset($attrData["code"]) || !is_string($attrData["code"]) || empty($attrData["code"])) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Product Attribute Code is Not Valid."
            );
        }
//        //====================================================================//
//        // Check Attributes Names are Given
//        if (!isset($attrData["name"]) || !is_scalar($attrData["name"]) || empty($attrData["name"])) {
//            return Splash::log()->err(
//                "ErrLocalTpl",
//                __CLASS__,
//                __FUNCTION__,
//                " Product Attribute Public Name is Not Valid."
//            );
//        }
        //====================================================================//
        // Check Attributes Values are Given
        if (!isset($attrData["value"]) || !is_scalar($attrData["value"]) || empty($attrData["value"])) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Product Attribute Value Name is Not Valid."
            );
        }

        return true;
    }
}
