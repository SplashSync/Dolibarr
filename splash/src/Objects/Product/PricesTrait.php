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

namespace   Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;

/**
 * Dolibarr Products Prices Fields
 */
trait PricesTrait
{
    /**
     * Build Fields using FieldFactory
     */
    protected function buildPricesFields()
    {
        global $conf,$langs;

        //====================================================================//
        // Product Selling Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->Identifier("price")
            ->Name($langs->trans("SellingPrice")." (".$conf->global->MAIN_MONNAIE.")")
            ->MicroData("http://schema.org/Product", "price")
            ->isLogged()
            ->isListed();

        if (Local::dolVersionCmp("4.0.0") >= 0) {
            //====================================================================//
            // WholeSale Price
            $this->fieldsFactory()->create(SPL_T_PRICE)
                ->Identifier("cost_price")
                ->Name($langs->trans("CostPrice")." (".$conf->global->MAIN_MONNAIE.")")
                ->Description($langs->trans("CostPriceDescription"))
                ->isLogged()
                ->MicroData("http://schema.org/Product", "wholesalePrice");
        }
    }

    /**
     * Read requested Field
     *
     * @param null|string $key       Input List Key
     * @param string      $fieldName Field Identifier / Name
     */
    protected function getPricesFields($key, $fieldName)
    {
        global $conf;

        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'price':
                //====================================================================//
                // If multiprices are enabled
                if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
                    $cfgPriceLevel = isset($conf->global->SPLASH_MULTIPRICE_LEVEL)
                            ? $conf->global->SPLASH_MULTIPRICE_LEVEL
                            : null;
                    $priceLevel = !empty($cfgPriceLevel) ? $cfgPriceLevel : 1;
                    $priceType = $this->object->multiprices_base_type[$priceLevel];
                    $priceHT = (double) $this->object->multiprices[$priceLevel];
                    $priceTTC = (double) $this->object->multiprices_ttc[$priceLevel];
                    $priceVAT = (double) $this->object->multiprices_tva_tx[$priceLevel];
                } else {
                    $priceType = $this->object->price_base_type;
                    $priceHT = (double) $this->object->price;
                    $priceTTC = (double) $this->object->price_ttc;
                    $priceVAT = (double) $this->object->tva_tx;
                }

                if ('TTC' === $priceType) {
                    $this->out[$fieldName] = self::prices()->Encode(
                        null,
                        $priceVAT,
                        $priceTTC,
                        $conf->global->MAIN_MONNAIE
                    );
                } else {
                    $this->out[$fieldName] = self::prices()->Encode(
                        $priceHT,
                        $priceVAT,
                        null,
                        $conf->global->MAIN_MONNAIE
                    );
                }

                break;
            case 'cost_price':
                    $priceHT = (double) $this->object->cost_price;
                    $this->out[$fieldName] = self::prices()
                        ->Encode($priceHT, (double)$this->object->tva_tx, null, $conf->global->MAIN_MONNAIE);

                break;
            default:
                return;
        }

        if (null != $key) {
            unset($this->in[$key]);
        }
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     */
    protected function setPricesFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                $this->setProductPrice($fieldData);

                break;
            case 'cost_price':
                $this->setSimpleFloat($fieldName, $fieldData["ht"]);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write New Price
     *
     * @param array $newPrice
     *
     * @return bool
     */
    private function setProductPrice($newPrice)
    {
        global $user, $conf;

        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        $this->getPricesFields(null, "price");
        //====================================================================//
        // Compare Prices
        if (self::prices()->Compare($this->out["price"], $newPrice)) {
            return true;
        }

        //====================================================================//
        // Perform Prices Update
        //====================================================================//

        //====================================================================//
        // Update Based on TTC Price
        if ($newPrice["base"]) {
            $price = $newPrice["ttc"];
            $priceBase = "TTC";
        //====================================================================//
        // Update Based on HT Price
        } else {
            $price = $newPrice["ht"];
            $priceBase = "HT";
        }

        //====================================================================//
        // If multiprices are enabled
        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
            $priceLevel = !empty($conf->global->SPLASH_MULTIPRICE_LEVEL) ? $conf->global->SPLASH_MULTIPRICE_LEVEL : 1;
        } else {
            $priceLevel = 0;
        }

        //====================================================================//
        // Update Variant Product Weight
        if ($this->isVariant() && !empty($this->baseProduct)) {
            return $this->setVariantPrice($price, $priceLevel);
        }

        //====================================================================//
        // Commit Price Update on Product Object
        //====================================================================//
        // For compatibility with previous versions => V3.5.0 or Above
        $result = $this->object->updatePrice($price, $priceBase, $user, $newPrice["vat"], 0.0, $priceLevel);

        //====================================================================//
        // Check potential Errors
        if ($result < 0) {
            $this->catchDolibarrErrors();

            return false;
        }
        $this->needUpdate();

        return true;
    }

    /**
     * Write New Price
     *
     * @param float $price      New Variant Price
     * @param int   $priceLevel MultiPrice Level
     *
     * @return bool
     */
    private function setVariantPrice($price, $priceLevel)
    {
        global $conf;

        //====================================================================//
        // If multiprices are enabled
        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
            $parentPrice = (double) $this->baseProduct->multiprices[$priceLevel];
        } else {
            $parentPrice = (double) $this->baseProduct->price;
        }
        //====================================================================//
        // Update Price on Product Combination
        $this->setSimple("variation_price_percentage", 0, "combination");
        // Update Combination
        $this->setSimple("variation_price", $price - $parentPrice, "combination");

        return true;
    }
}
