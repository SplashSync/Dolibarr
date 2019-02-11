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

use Splash\Local\Local;

/**
 * Product Variant Core Function & Data Access
 */
trait CoreTrait
{
    //====================================================================//
    // General Variants Functions
    //====================================================================//
    
    /**
     * Check if Variants Module is Active
     *
     * @return bool
     */
    public static function isVariantEnabled()
    {
        return (bool) Local::getParameter("MAIN_MODULE_VARIANTS", false);
    }    
    
    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

//    /**
//     * Build Fields using FieldFactory
//     */
//    protected function buildVariantsCoreFields()
//    {
//        //====================================================================//
//        // Product Variation Parent Link
//        $this->fieldsFactory()->create(SPL_T_VARCHAR)
//            ->Identifier("id")
//            ->Name("Parent Product")
//            ->Group("Meta")
//            ->MicroData("http://schema.org/Product", "isVariationOf")
//            ->isReadOnly();
//        
//        //====================================================================//
//        // CHILD PRODUCTS INFORMATIONS
//        //====================================================================//
//        
//        //====================================================================//
//        // Product Variation List - Product Link
//        $this->fieldsFactory()->Create((string) self::objects()->Encode("Product", SPL_T_ID))
//            ->Identifier("id")
//            ->Name("Variants")
//            ->InList("variants")
//            ->MicroData("http://schema.org/Product", "Variants")
//            ->isNotTested();
//        
//        //====================================================================//
//        // Product Variation List - Product SKU
//        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
//            ->Identifier("sku")
//            ->Name("SKU")
//            ->InList("variants")
//            ->MicroData("http://schema.org/Product", "VariationName")
//            ->isReadOnly();
//        
//        //====================================================================//
//        // Product Variation List - Variation Attribute
//        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
//            ->Identifier("options")
//            ->Name("Options")
//            ->InList("variants")
//            ->MicroData("http://schema.org/Product", "VariationAttribute")
//            ->isReadOnly();
//    }
//    
//    /**
//     * Read requested Field
//     *
//     * @param string $key       Input List Key
//     * @param string $fieldName Field Identifier / Name
//     *
//     * @return void
//     */
//    protected function getVariantsCoreFields($key, $fieldName)
//    {
//        //====================================================================//
//        // READ Fields
//        switch ($fieldName) {
//            case 'id':
//                $this->getSimple($fieldName);
//
//                break;
//            default:
//                return;
//        }
//        
//        unset($this->in[$key]);
//    }
//    
//    /**
//     * Read requested Field
//     *
//     * @param string $key       Input List Key
//     * @param string $fieldName Field Identifier / Name
//     *
//     * @return void
//     */
//    protected function getVariantsListFields($key, $fieldName)
//    {
//        //====================================================================//
//        // Check if List field & Init List Array
//        $fieldId = self::lists()->initOutput($this->out, "variants", $fieldName);
//        if (!$fieldId) {
//            return;
//        }
//
//        //====================================================================//
//        // READ Fields
//        foreach ($this->object->variants as $index => $variant) {
//            //====================================================================//
//            // Get Variant Infos
//            switch ($fieldId) {
//                case 'id':
//                    $value  =   self::objects()
//                        ->encode(
//                            "Product",
//                            $this->getObjectId((string) $this->productId, $variant['id'])
//                        );
//
//                    break;
//                case 'sku':
//                    $value  =   $variant["sku"];
//
//                    break;
//                case 'options':
//                    $variantOptions = array($variant["option1"], $variant["option2"], $variant["option3"]);
//                    $value  =   implode(" | ", array_filter($variantOptions));
//
//                    break;
//                default:
//                    return;
//            }
//            
//            self::lists()->insert($this->out, "variants", $fieldId, $index, $value);
//        }
//        
//        unset($this->in[$key]);
//        //====================================================================//
//        // Sort Attributes by Code
//        ksort($this->out["variants"]);
//    }
//    
//    /**
//     * Write Given Fields
//     *
//     * @param string $fieldName Field Identifier / Name
//     * @param mixed  $fieldData Field Data
//     *
//     * @return void
//     */
//    protected function setVariantsListFields($fieldName, $fieldData)
//    {
//        //====================================================================//
//        // Safety Check
//        if ("variants" !== $fieldName) {
//            return;
//        }
//        unset($this->in[$fieldName]);
//    }
//        
//    /**
//     * Identify Default Variant Product Id
//     *
//     * @return null|string
//     */
//    private function getParentProductId()
//    {
//        //====================================================================//
//        // Not a Variable Product => No default
//        if (!isset($this->in["variants"]) || !is_iterable($this->in["variants"])) {
//            return null;
//        }
//        //====================================================================//
//        // Identify Parent in Parent Products Ids
//        foreach ($this->in["variants"] as $variant) {
//            //====================================================================//
//            // Safety Check => Id is Here
//            if (!isset($variant['id']) || !is_scalar($variant['id'])) {
//                continue;
//            }
//            //====================================================================//
//            // Safety Check => Is Product Object Id
//            if ("Product" == self::objects()->type((string) $variant['id'])) {
//                continue;
//            }
//            //====================================================================//
//            // Extract Object Id
//            $productId = self::getProductId((string) self::objects()->id((string) $variant['id']));
//            //====================================================================//
//            // Safety Check => Is Product Object Id is Here
//            if (empty($productId) || !is_scalar($productId)) {
//                continue;
//            }
//            
//            return $productId;
//        }
//
//        return null;
//    }
}
