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

use Product;
use Splash\Core\SplashCore as Splash;
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
            ->identifier("fk_product_parent")
            ->name("Parent Product Id")
            ->group("Meta")
            ->microData("http://schema.org/Product", "isVariationOf")
            ->isReadOnly()
        ;
        //====================================================================//
        // Product Variation Parent Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("parent_ref")
            ->name("Parent SKU")
            ->group("Meta")
            ->microData("http://schema.org/Product", "isVariationOfName")
            ->isNotTested()
        ;
        //====================================================================//
        // CHILD PRODUCTS INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Product Variation List - Product Link
        $this->fieldsFactory()->Create((string) self::objects()->Encode("Product", SPL_T_ID))
            ->identifier("id")
            ->name("Variants")
            ->inList("variants")
            ->microData("http://schema.org/Product", "Variants")
            ->isNotTested()
        ;
        //====================================================================//
        // Product Variation List - Product SKU
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
            ->identifier("sku")
            ->name("Variant SKU")
            ->inList("variants")
            ->microData("http://schema.org/Product", "VariationName")
            ->isReadOnly()
        ;
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
    protected function getVariantsCoreFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'fk_product_parent':
                $this->getSimple($fieldName, "combination");

                break;
            case 'parent_ref':
                $this->out[$fieldName] = isset($this->baseProduct)
                    ? $this->baseProduct->ref : ""
                ;

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
    protected function getVariantsListFields(string $key, string $fieldName): void
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

        foreach ($variants as $index => $combination) {
            //====================================================================//
            // SKIP Current Variant When in PhpUnit/Travis Mode
            // Only Existing Variant will be Returned
            if (Splash::isTravisMode() && ($combination->fk_product_child == $this->object->id)) {
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
        if (is_array($this->out["variants"])) {
            ksort($this->out["variants"]);
        }
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
    protected function setVariantsCoreFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
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
     * @param string     $fieldName Field Identifier / Name
     * @param null|array $fieldData Field Data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setVariantsListFields(string $fieldName, ?array $fieldData): void
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
        $fieldData = $fieldData ?? array();
        //====================================================================//
        // Check if Product has Additional Variants
        if (!VariantsManager::hasAdditionnalVariants($this->combination->fk_product_parent, $fieldData)) {
            return;
        }
        Splash::log()->war("Additional Variants Detected! Ref:".$this->object->ref);
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
     * @param array $variants Product Variants List
     *
     * @return void
     */
    protected function updateVariantsParent(array $variants)
    {
        global $db;
        //====================================================================//
        // Check System Uses Strict Variants Mode
        if (empty(Splash::configuration()->StrictVariantsMode) || empty($this->combination)) {
            return;
        }
        //====================================================================//
        // Create a New Parent Product
        $newParentProduct = null;
        if (!empty($this->in["base_label"]) && is_string($this->in["base_label"])) {
            $newParentProduct = $this->createSimpleProduct(
                $this->object->ref."_base",
                $this->in["base_label"],
                false
            );
        }
        //====================================================================//
        // Create New Parent Product Failed
        if (!$newParentProduct) {
            return;
        }
        //====================================================================//
        // Update All Product Combinations Parents
        VariantsManager::moveAdditionalVariants(
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
