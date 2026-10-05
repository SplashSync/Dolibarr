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

namespace Splash\Local\Objects\Product;

use Exception;
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
    private static array $productActions = array(
        'PRODUCT_CREATE',
        'PRODUCT_MODIFY',
        'PRODUCT_DELETE',
        'PRODUCT_SET_MULTILANGS',
        'PRODUCT_PRICE_MODIFY',
        'STOCK_MOVEMENT',
    );

    /**
     * Prices Import Triggered Action Names, with their Prices Table
     *
     * Since Dolibarr V24, imports in Secured mode run triggers. Prices tables
     * have no business object: Dolibarr builds these action names from the
     * table element, and passes a stdClass holding the price line id.
     *
     * @var array<string, string>
     */
    private static array $productPricesImportActions = array(
        'PRODUCTPRICE_CREATE' => 'product_price',
        'PRODUCTPRICE_MODIFY' => 'product_price',
        'PRODUCTSUPPLIERPRICE_CREATE' => 'product_fournisseur_price',
        'PRODUCTSUPPLIERPRICE_MODIFY' => 'product_fournisseur_price',
    );

    /**
     * Prepare Object Commit for Product
     *
     * @param string $action Event Code
     * @param object $object Impacted Objet
     *
     * @throws Exception
     *
     * @return bool Commit is required
     */
    protected function doProductCommit(string $action, object $object): bool
    {
        //====================================================================//
        // Filter Triggered Actions
        if (!$this->isProductCommitRequired($action, $object)) {
            return false;
        }
        //====================================================================//
        // Identify Product, or Skip Commit
        if (!$this->setProductObjectId($action, $object)) {
            return false;
        }
        //====================================================================//
        // Store Global Action Parameters
        $this->setProductParameters($action);

        return true;
    }

    /**
     * Check if Commit is Required
     *
     * @param string $action Event Code
     * @param object $object Impacted Objet
     *
     * @return bool
     */
    private function isProductCommitRequired(string $action, object $object): bool
    {
        //====================================================================//
        // Filter on Event Action
        if (!in_array($action, self::$productActions, true)
            && !isset(self::$productPricesImportActions[$action])) {
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
     * Identify Product ID from Given Object
     *
     * @param string $action Event Code
     * @param object $object Impacted Objet
     *
     * @return bool False if no Product could be Identified
     */
    private function setProductObjectId(string $action, object $object): bool
    {
        //====================================================================//
        // Identify Product Id
        if ($object instanceof  Product) {
            $this->objectId = (string) $object->id;
        } elseif ($object instanceof MouvementStock) {
            $this->objectId = (string) $object->product_id;
        } elseif (isset(self::$productPricesImportActions[$action])) {
            $this->objectId = $this->getProductIdFromPriceLine(
                self::$productPricesImportActions[$action],
                $object
            );
        }

        return !empty($this->objectId);
    }

    /**
     * Prepare Object Commit for Product
     *
     * @param string $action Event Code
     *
     * @throws Exception
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setProductParameters(string $action): void
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
        } elseif (isset(self::$productPricesImportActions[$action])) {
            $this->action = SPL_A_UPDATE;
            $this->comment = "Product Prices Imported on Dolibarr";
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
     * @param string $action Event Code
     *
     * @return void
     */
    private function onProductVariantChanges(string $action): void
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

    /**
     * Identify Product Id from an Imported Price Line
     *
     * The object is a stdClass holding the price line id, and its product id
     * only when IMPORT_TRIGGER_ENRICH_OBJECT is enabled: read it otherwise.
     *
     * @param string $priceTable Prices table, without prefix
     * @param object $object     Impacted Objet
     *
     * @return null|string
     */
    private function getProductIdFromPriceLine(string $priceTable, object $object): ?string
    {
        global $db;

        //====================================================================//
        // Product ID Already Known (Enriched Object)
        if (!empty($object->fk_product) && is_scalar($object->fk_product)) {
            return (string) $object->fk_product;
        }
        //====================================================================//
        // Read Product Id from Price Line
        if (empty($object->id) || !is_scalar($object->id)) {
            return null;
        }
        $sql = "SELECT fk_product FROM ".MAIN_DB_PREFIX.$priceTable;
        $sql .= " WHERE rowid = ".((int) $object->id);
        $result = $db->query($sql);
        $line = $result ? $db->fetch_object($result) : null;

        return empty($line->fk_product) ? null : (string) $line->fk_product;
    }
}
