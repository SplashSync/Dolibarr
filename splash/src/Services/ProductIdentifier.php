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

use Product;
use Splash\Models\Helpers\ObjectsHelper;

/**
 * Collection of methods to Identify Products
 */
class ProductIdentifier
{
    /**
     * Detect Product ID from Input Line Item with SKU Detection
     *
     * @param array $lineItem Input Line Item Data Array
     *
     * @return null|Product
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function findIdByLineItem(array $lineItem): ?Product
    {
        global $conf;

        //====================================================================//
        // Product Id is Given
        if (!empty($lineItem["fk_product"]) && is_string($lineItem["fk_product"])) {
            //====================================================================//
            // Decode Splash Id String
            if ($product = self::findById((int) ObjectsHelper::id($lineItem["fk_product"]))) {
                return $product;
            }
        }
        //====================================================================//
        // Search for Product SKU from Item SKU
        if (!empty($lineItem["product_ref"]) && is_string($lineItem["product_ref"])) {
            //====================================================================//
            // Find Product by Sku
            if ($product = self::findBySku($lineItem["product_ref"])) {
                return $product;
            }
        }
        //====================================================================//
        // Search for Product SKU from Item Description
        if (empty($conf->global->SPLASH_DECTECT_ITEMS_BY_SKU)) {
            return null;
        }
        if (!empty($lineItem["desc"]) && is_string($lineItem["desc"])) {
            //====================================================================//
            // Find Product by Sku
            if ($product = self::findBySku($lineItem["desc"])) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Load Product by ID
     */
    public static function findById(int $productId): ?Product
    {
        global $db;

        //====================================================================//
        // Ensure Product Class is Loaded
        include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
        //====================================================================//
        // Try Loading product by SKU
        $product = new Product($db);
        $result = $product->fetch($productId);
        if (($result > 0) && ($product->id == $productId)) {
            return $product;
        }

        return null;
    }

    /**
     * Load Product by SKU
     */
    public static function findBySku(string $productSku): ?Product
    {
        global $db;

        //====================================================================//
        // Ensure Product Class is Loaded
        include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
        //====================================================================//
        // Shorten Item Resume to remove potential spaces
        $productRef = str_replace(array(" ", "(", ")", "[", "]", "+", "/"), "", $productSku);
        //====================================================================//
        // Try Loading product by SKU
        $product = new Product($db);
        $result = $product->fetch(0, $productRef);
        if (($result > 0) && ($product->id > 0)) {
            return $product;
        }

        return null;
    }
}
