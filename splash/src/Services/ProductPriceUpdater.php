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

/**
 * Update Product Price with TAx Detection
 */
class ProductPriceUpdater
{
    /**
     * Sell Price for Product or Variant Product
     *
     * @param Product $product Product to Update
     * @param float   $price   New Variant Price
     * @param float   $vat     New Price Vat Rate
     * @param string  $base    Price Mode (HT/TTC)
     * @param int     $level   MultiPrice Level
     */
    public static function update(Product $product, float $price, float $vat, string $base, int $level): int
    {
        global $user;

        //====================================================================//
        // Detect TAX Rate from VAT Percentile
        $vatRate = TaxManager::findTaxByRate($vat);

        //====================================================================//
        // Perform Dolibarr Product Price Update
        return $product->updatePrice(
            // New price
            $price,
            // HT or TTC
            $base,
            // User that make change
            $user,
            // New VAT Rate
            $vat,
            // New price min
            $product->price_min ?: 0.0,
            // Impacted Price level 0=standard, >0 = level if multilevel prices
            $level,
            // IS NPR 0=Standard vat rate, 1=Special vat rate for French NPR VAT
            $vatRate ? $vatRate->npr : 0,
            // 1 if it has price by quantity
            0,
            // Used to avoid infinite loops
            0,
            // Array with Local Taxes info array('0'=>type1,'1'=>rate1,'2'=>type2,'3'=>rate2).
            array(
                "0" => $vatRate ? $vatRate->localtax1_type : 0,
                "1" => $vatRate ? $vatRate->localtax1_tx : 0,
                "2" => $vatRate ? $vatRate->localtax2_type : 0,
                "3" => $vatRate ? $vatRate->localtax2_tx : 0,
            ),
            // Default vat code
            $vatRate ? $vatRate->code : $product->default_vat_code
        );
    }
}
