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

use Product;
use ProductCombination;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Local\Services\VariantsManager;

/**
 * Product Variant CRUD Function & Data Access
 */
trait CRUDTrait
{
    /**
     * @var Product
     */
    protected $parent;

    /**
     * @var null|ProductCombination
     */
    protected $combination;

    //====================================================================//
    // Variants CRUD Functions
    //====================================================================//

    /**
     * Create Variant Product
     *
     * @return false|Product
     */
    protected function createVariantProduct()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Identify Parent Product using Given Variants Ids
        $parentProduct = $this->identifyParent();
        if (!$parentProduct) {
            //====================================================================//
            // Create New Parent Product
            $parentProduct = $this->createSimpleProduct($this->in["ref"]."_base", $this->in["base_label"], false);
        }
        //====================================================================//
        // Create New Parent Product Failed
        if (!$parentProduct) {
            return false;
        }
        //====================================================================//
        // Create New Variant Product
        $variantProduct = $this->createSimpleProduct($this->in["ref"], $this->in["base_label"], true);
        if ($variantProduct) {
            //====================================================================//
            // Create New Product Combination
            $this->combination = VariantsManager::addProductCombination($parentProduct, $variantProduct);
            //====================================================================//
            // Store Parent Product
            $this->baseProduct = $parentProduct;
        }

        return $variantProduct;
    }

    /**
     * Update Variant Product Objects
     *
     * @return bool
     */
    protected function updateVariantProduct()
    {
        global $user;

        //====================================================================//
        // Update Base Product
        if ($this->isToUpdate("baseProduct")) {
            if ($this->baseProduct->update($this->baseProduct->id, $user) <= 0) {
                $this->catchDolibarrErrors($this->baseProduct);

                return Splash::log()->errTrace("Unable to Update Base Product (".$this->baseProduct->id.")");
            }
        }

        //====================================================================//
        // Update Product Combination
        if ($this->isToUpdate("combination") && (null !== $this->combination)) {
            if ($this->combination->update($user) <= 0) {
                $this->catchDolibarrErrors($this->combination);

                return Splash::log()->errTrace("Unable to Update Product Combination (".$this->combination->id.")");
            }
            if ($this->combination->updateProperties($this->baseProduct, $user) <= 0) {
                $this->catchDolibarrErrors($this->combination);

                return Splash::log()->errTrace("Unable to Update Combination Properties (".$this->combination->id.")");
            }
        }

        return true;
    }

    //====================================================================//
    // General Variants Functions
    //====================================================================//

    /**
     * Check if Variants Module is Active
     *
     * @return bool
     */
    protected static function isVariantEnabled()
    {
        return (bool) Local::getParameter("MAIN_MODULE_VARIANTS");
    }

    /**
     * Check if Product is Variants
     *
     * @return bool
     */
    protected function isVariant()
    {
        return (null !== $this->combination);
    }

    /**
     * Identify Parent Product Id
     *
     * @return false|Product
     */
    private function identifyParent()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Check Variant Products Array
//        if (!isset($this->in["variants"]) || !is_iterable($this->in["variants"])) {
//            return false;
//        }
        if (!is_array($this->in["variants"]) && !is_a($this->in["variants"], "ArrayObject")) {
            return false;
        }
        //====================================================================//
        // Walk on Variant Products
        $variantProductId = false;
        foreach ($this->in["variants"] as $listData) {
            //====================================================================//
            // Check Product Id is here
            if (!isset($listData["id"]) || !is_string($listData["id"])) {
                continue;
            }
            //====================================================================//
            // Extract Variable Product Id
            $variantProductId = self::objects()->id($listData["id"]);
            if (false !== $variantProductId) {
                break;
            }
        }
        //====================================================================//
        // No Variant Products Id Given
        if (false == $variantProductId) {
            return false;
        }
        //====================================================================//
        // Load Product Combinations
        $combination = VariantsManager::getProductCombination((int) $variantProductId);
        if (null == $combination) {
            return false;
        }
        //====================================================================//
        // Load Base Product (Jedi Mode => Force Loading)
        return $this->load((string) $combination->fk_product_parent, true);
    }
}
