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

namespace Splash\Local\Objects\Product\Variants;

use ArrayObject;
use Product;
use ProductCombination;
use Splash\Core\SplashCore      as Splash;
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
     *
     * @return void
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
        // Product Variation Parent Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("parent_ref")
            ->Name("Parent SKU")
            ->Group("Meta")
            ->MicroData("http://schema.org/Product", "isVariationOfName")
            ->isNotTested();

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
            ->Name("Variant SKU")
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
            case 'parent_ref':
                if (!$this->isVariant()) {
                    $this->out[$fieldName] = "";
                }
                $this->out[$fieldName] = $this->baseProduct->ref;

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

        /** @var ProductCombination $combination */
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
                    $value = self::objects()
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
     */
    protected function setVariantsCoreFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writtings
            case 'parent_ref':
                if ($this->isVariant() && !empty($fieldData)) {
                    $this->setSimple("ref", $fieldData, "baseProduct");
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     *
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
        //====================================================================//
        // Check Product is a Variant
        if (empty($this->combination->fk_product_parent)) {
            return;
        }
        //====================================================================//
        // Ensure Variants Field data is Iterable
        if (!is_array($fieldData) && !is_a($fieldData, ArrayObject::class)) {
            $fieldData = array();
        }
        //====================================================================//
        // Check if Product has Additionnal Variants
        if (!VariantsManager::hasAdditionnalVariants($this->combination->fk_product_parent, $fieldData)) {
            return;
        }
        Splash::log()->war("Additionnal Variants Detected! Ref:".$this->object->ref);
        //====================================================================//
        // Check System Uses Strict Variants Mode
        if (empty(Splash::configuration()->StrictVariantsMode)) {
            return;
        }

        $this->updateVariantsParent($fieldData);
    }

    /**
     * Create a New Product Parent and Move All Variants to this New One
     *
     * @param array|ArrayObject $variants Product Variants List
     *
     * @return void
     */
    protected function updateVariantsParent($variants)
    {
        global $db;
        //====================================================================//
        // Check System Uses Strict Variants Mode
        if (empty(Splash::configuration()->StrictVariantsMode) || empty($this->combination)) {
            return;
        }
        //====================================================================//
        // Create a New Parent Product
        $newParentProduct = $this->createSimpleProduct($this->object->ref."_base", $this->in["base_label"], false);
        //====================================================================//
        // Create New Parent Product Failed
        if (!$newParentProduct) {
            return;
        }
        //====================================================================//
        // Update All Product Combinations Parents
        VariantsManager::moveAdditionnalVariants(
            $this->combination->fk_product_parent,
            $variants,
            $newParentProduct->id
        );
        //====================================================================//
        // Update Current Product Parent
        $this->setSimple("fk_parent", $newParentProduct->id);
        //====================================================================//
        // Reload Product Combinations
        $this->combination = VariantsManager::getProductCombination((int) $this->object->id);
        if ($this->combination) {
            $this->baseProduct = new Product($db);
            $this->baseProduct->fetch($newParentProduct->id);
        }
    }
}
