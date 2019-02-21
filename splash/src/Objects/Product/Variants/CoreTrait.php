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

use Product;
use ProductCombination;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Local\Services\VariantsManager;

/**
 * Product Variant Core Function & Data Access
 */
trait CoreTrait
{
    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
     * Build Fields using FieldFactory
     */
    protected function buildVariantsCoreFields()
    {
        //====================================================================//
        // Ensure Product Variation Module is Active
        if (!self::isVariantEnabled()) {
            return;
        }
        
        //====================================================================//
        // Product Variation Parent Link
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("fk_product_parent")
            ->Name("Parent Product Id")
            ->Group("Meta")
            ->MicroData("http://schema.org/Product", "isVariationOf")
            ->isReadOnly();

        //====================================================================//
        // CHILD PRODUCTS INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Product Variation List - Product Link
        $this->fieldsFactory()->Create((string) self::objects()->Encode("Product", SPL_T_ID))
            ->Identifier("id")
            ->Name("Variants")
            ->InList("variants")
            ->MicroData("http://schema.org/Product", "Variants")
            ->isNotTested();
        
        //====================================================================//
        // Product Variation List - Product SKU
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->Identifier("sku")
            ->Name("SKU")
            ->InList("variants")
            ->MicroData("http://schema.org/Product", "VariationName")
            ->isReadOnly();
    }
    
    //====================================================================//
    // Fields Getter Functions
    //====================================================================//
    
    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getVariantsCoreFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'fk_product_parent':
                $this->getSimple($fieldName, "combination");

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getVariantsListFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "variants", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Check if Product is Variant
        if (null === $this->combination) {
            unset($this->in[$key]);
            
            return;
        }
        //====================================================================//
        // Load Product Variants
        $variants = VariantsManager::getProductVariants($this->combination->fk_product_parent);

        /** @var ProductCombination $combinaition */
        foreach ($variants as $index => $combination) {
            //====================================================================//
            // SKIP Current Variant When in PhpUnit/Travis Mode
            // Only Existing Variant will be Returned
            if (!empty(Splash::input('SPLASH_TRAVIS')) && ($combination->fk_product_child == $this->object->id)) {
                continue;
            }
            
            //====================================================================//
            // Get Variant Infos
            switch ($fieldId) {
                case 'id':
                    $value  =   self::objects()
                        ->encode("Product", (string) $combination->fk_product_child);

                    break;
                case 'sku':
                    $value = $this->object->getValueFrom(
                        $this->object->table_element,
                        $combination->fk_product_child,
                        "ref"
                    );

                    break;
                default:
                    return;
            }

            self::lists()->insert($this->out, "variants", $fieldId, $index, $value);
        }
        
        unset($this->in[$key]);
        //====================================================================//
        // Sort Attributes by Code
        ksort($this->out["variants"]);
    }
    
    //====================================================================//
    // Fields Setter Functions
    //====================================================================//
    
    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setVariantsListFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Safety Check
        if ("variants" !== $fieldName) {
            return;
        }
        unset($this->in[$fieldName]);
    }
}
