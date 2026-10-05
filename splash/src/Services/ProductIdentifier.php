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
use Splash\Local\Local;
use Splash\Models\Helpers\ObjectsHelper;

/**
 * Collection of methods to Identify Products
 */
class ProductIdentifier
{
    /**
     * Dolibarr setting disabling Product References Sanitization (since Dolibarr V18)
     *
     * @var string
     */
    const ALLOW_UNSECURED_REFS = "MAIN_SECURITY_ALLOW_UNSECURED_REF_LABELS";

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
        // Candidate References, in Order
        $candidates = array_unique(array(
            // Shorten Item Resume to remove potential spaces
            str_replace(array(" ", "(", ")", "[", "]", "+", "/"), "", $productSku),
            // Reference as Dolibarr Stores it ("A/B" => "A_B")
            self::normalizeRef($productSku),
        ));
        //====================================================================//
        // Try Loading product by SKU
        foreach ($candidates as $productRef) {
            $product = new Product($db);
            $result = $product->fetch(0, $productRef);
            if (($result > 0) && ($product->id > 0)) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Normalize a Product Reference the way Dolibarr stores it
     *
     * Dolibarr sanitizes references on product create & update: special chars
     * (/, \, :, *, ?, ", <, >, |, [, ], spaces...) are replaced by "_".
     * A reference sent by a source may then differ from the stored one, so
     * every lookup or write of a reference must go through this method.
     *
     * Since Dolibarr V18, sanitization is skipped when the
     * MAIN_SECURITY_ALLOW_UNSECURED_REF_LABELS setting is enabled.
     *
     * @param string $ref Product reference, as received from the source
     *
     * @return string Product reference, as Dolibarr would store it
     */
    public static function normalizeRef(string $ref): string
    {
        //====================================================================//
        // Since Dolibarr V18 => Sanitization may be Disabled
        if ((Local::dolVersionCmp("18.0.0") >= 0) && !empty(Local::getParameter(self::ALLOW_UNSECURED_REFS))) {
            return trim($ref);
        }

        //====================================================================//
        // Same Transformation as Dolibarr Product::create() & Product::update()
        return dol_sanitizeFileName(dol_string_nospecial(trim($ref)));
    }
}
