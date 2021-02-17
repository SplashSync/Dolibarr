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

namespace Splash\Local\Objects\Product;

use MouvementStock;
use Product;
use Splash\Client\Splash;
use Splash\Local\Services\VariantsManager;

/**
 * Product Dolibarr Trigger trait
 */
trait TriggersTrait
{
    /**
     * Products Triggered Action Names
     *
     * @var array
     */
    private static $productActions = array(
        'PRODUCT_CREATE',
        'PRODUCT_MODIFY',
        'PRODUCT_DELETE',
        'PRODUCT_SET_MULTILANGS',
        'PRODUCT_PRICE_MODIFY',
        'STOCK_MOVEMENT',
    );

    /**
     * Prepare Object Commit for Product
     *
     * @param string $action Code de l'evenement
     * @param object $object Objet concerne
     *
     * @return bool Commit is required
     */
    protected function doProductCommit($action, $object)
    {
        //====================================================================//
        // Filter Triggered Actions
        if (!$this->isProductCommitRequired($action, $object)) {
            return false;
        }
        //====================================================================//
        // Store Global Action Parameters
        $this->setProductObjectId($object);
        $this->setProductParameters($action);

        return true;
    }

    /**
     * Check if Commit is Requiered
     *
     * @param string $action Code de l'evenement
     * @param object $object Objet concerne
     *
     * @return bool
     */
    private function isProductCommitRequired($action, $object)
    {
        //====================================================================//
        // Filter on Event Action
        if (!in_array($action, static::$productActions, true)) {
            return false;
        }

        //====================================================================//
        // Prevent Commits for Variants Base Products
        if (($object instanceof Product) && VariantsManager::isProductLocked($object->id)) {
            return false;
        }

        return true;
    }

    /**
     * Identify Order Id from Given Object
     *
     * @param object $object Objet concerne
     *
     * @return void
     */
    private function setProductObjectId($object)
    {
        //====================================================================//
        // Identify Product Id
        if ($object instanceof  Product) {
            $this->objectId = (string) $object->id;
        } elseif ($object instanceof MouvementStock) {
            $this->objectId = (string) $object->product_id;
        }
    }

    /**
     * Prepare Object Commit for Product
     *
     * @param string $action Code de l'evenement
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setProductParameters($action)
    {
        //====================================================================//
        // Check if object if in Remote Create Mode
        $isLockedForCreation = Splash::object("Product")->isLocked();

        //====================================================================//
        // Store Global Action Parameters
        $this->objectType = "Product";
        if ('PRODUCT_CREATE' == $action) {
            $this->action = SPL_A_CREATE;
            $this->comment = "Product Created on Dolibarr";
        } elseif ('PRODUCT_MODIFY' == $action) {
            $this->action = SPL_A_UPDATE;
            $this->comment = "Product Updated on Dolibarr";
        } elseif ('PRODUCT_SET_MULTILANGS' == $action) {
            $this->action = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->comment = "Product Description Updated on Dolibarr";
        } elseif ('STOCK_MOVEMENT' == $action) {
            $this->action = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->comment = "Product Stock Updated on Dolibarr";
        } elseif ('PRODUCT_PRICE_MODIFY' == $action) {
            $this->action = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->comment = "Product Price Updated on Dolibarr";
        } elseif ('PRODUCT_DELETE' == $action) {
            $this->action = SPL_A_DELETE;
            $this->comment = "Product Deleted on Dolibarr";
        }
        //====================================================================//
        // Commit Delete for Base Product if Required
        $this->onProductVariantChanges($action);
    }

    /**
     * Commit Delete for Base Product
     *
     * @param string $action Code de l'evenement
     *
     * @return void
     */
    private function onProductVariantChanges($action)
    {
        //====================================================================//
        // Only When a New Variant is Created
        if (!in_array($action, array('PRODUCT_CREATE', 'PRODUCT_MODIFY'), true)) {
            return;
        }
        //====================================================================//
        // SKIP When in PhpUnit/Travis Mode
        if (!empty(Splash::input('SPLASH_TRAVIS')) || !is_scalar($this->objectId)) {
            return;
        }
        //====================================================================//
        // Load Product Combinations
        $combination = VariantsManager::getProductCombination((int) $this->objectId);
        //====================================================================//
        // Only if Product is a Variant
        if ($combination) {
            //====================================================================//
            // Commit Change to Splash
            Splash::commit(
                (string) $this->objectType,         // Object Type
                $combination->fk_product_parent,    // Parent Product Id
                SPL_A_DELETE,                       // Splash Action Type
                $this->login,                       // Current User Login
                "Variant Created on Dolibarr"       // Action Comment
            );
        }
    }
}
