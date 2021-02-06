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

namespace   Splash\Local\Objects\Product;

use Splash\Local\Local;
use Splash\Local\Services\ConfigManager;

/**
 * Dolibarr Products Prices Fields
 */
trait PricesTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
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
     *
     * @return void
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
                $this->out[$fieldName] = $this->getProductPrice();

                break;
            case 'cost_price':
                    $priceHT = (double) $this->object->cost_price;
                    $this->out[$fieldName] = self::prices()
                        ->encode($priceHT, (double)$this->object->tva_tx, null, $conf->global->MAIN_MONNAIE);

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
     *
     * @return void
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
     * Write New Price Variations for Variant Product
     *
     * @param float $price      New Variant Price
     * @param int   $priceLevel MultiPrice Level
     *
     * @return void
     */
    protected function setVariantVariationPrice($price, $priceLevel)
    {
        global $conf;

        //====================================================================//
        // If multi-prices are enabled
        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
            $parentPrice = (double) $this->baseProduct->multiprices[$priceLevel];
        } else {
            $parentPrice = (double) $this->baseProduct->price;
        }
        $priceVariation = $price - $parentPrice;
        //====================================================================//
        // No Multi-prices => Variant Main Price Update
        if (empty($conf->global->PRODUIT_MULTIPRICES)) {
            $this->setSimple("variation_price_percentage", 0, "combination");
            $this->setSimple("variation_price", $priceVariation, "combination");

            return;
        }
        //====================================================================//
        // First Price => Variant Main Price Update for BC
        if (1 == $priceLevel) {
            $this->setSimple("variation_price_percentage", 0, "combination");
            $this->setSimple("variation_price", $priceVariation, "combination");
        }
        //====================================================================//
        // Before DOL V13 => No Variant Prices levels
        if (!isset($this->combination) || !property_exists($this->combination, "combination_price_levels")) {
            return;
        }
        if (!isset($this->combination->combination_price_levels[$priceLevel])) {
            return;
        }
        //====================================================================//
        // Since DOL V13 => Update Variant Prices levels
        $combPriceLevel = $this->combination->combination_price_levels[$priceLevel];
        if (0 != $combPriceLevel->variation_price_percentage) {
            $combPriceLevel->variation_price_percentage = false;
            $this->needUpdate("combination");
        }
        if (abs($combPriceLevel->variation_price - $priceVariation) > 1E-4) {
            $combPriceLevel->variation_price = $priceVariation;
            $this->needUpdate("combination");
        }
    }

    /**
     * Read Product Price
     *
     * @return array|string
     */
    protected function getProductPrice()
    {
        global $conf;

        //====================================================================//
        // If multi-prices are enabled
        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
            $cfgPriceLevel = isset($conf->global->SPLASH_MULTIPRICE_LEVEL)
                ? $conf->global->SPLASH_MULTIPRICE_LEVEL
                : null;
            $priceLevel = !empty($cfgPriceLevel) ? $cfgPriceLevel : 1;
            $priceType = $this->object->multiprices_base_type[$priceLevel];
            $priceHT = (double) $this->object->multiprices[$priceLevel];
            $priceTTC = (double) $this->object->multiprices_ttc[$priceLevel];
            $priceVAT = (double) $this->object->multiprices_tva_tx[$priceLevel];
            if ($this->isVariant() && !empty($this->baseProduct)) {
                $priceVAT = (double) $this->baseProduct->multiprices_tva_tx[$priceLevel];
            }
        } else {
            $priceType = $this->object->price_base_type;
            $priceHT = (double) $this->object->price;
            $priceTTC = (double) $this->object->price_ttc;
            $priceVAT = (double) $this->object->tva_tx;
        }

        return self::prices()->encode(
            ('TTC' === $priceType) ? null : $priceHT,
            $priceVAT,
            ('TTC' === $priceType) ? $priceTTC : null,
            $conf->global->MAIN_MONNAIE
        );
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
        global $conf, $user;

        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        $this->getPricesFields(null, "price");
        //====================================================================//
        // Compare Prices
        if (self::prices()->Compare($this->out["price"], $newPrice, $conf->global->MAIN_MAX_DECIMALS_UNIT)) {
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
        $priceLevel = ConfigManager::getDefaultPriceLevel();

        //====================================================================//
        // Update Variant Product Price
        if ($this->isVariant() && !empty($this->baseProduct)) {
            return $this->setVariantPrice($price, $newPrice["vat"], $priceBase, $priceLevel);
        }

        //====================================================================//
        // Commit Price Update on Simple Product
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
     * Write New Price for Variant Product
     *
     * @param float  $price      New Variant Price
     * @param float  $priceVat   New Price Vat Rate
     * @param string $priceBase  Price Mode (HT/TTC)
     * @param int    $priceLevel MultiPrice Level
     *
     * @return bool
     */
    private function setVariantPrice($price, $priceVat, $priceBase, $priceLevel)
    {
        global $conf, $user;

        //====================================================================//
        // If multi-prices are enabled
        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
            $parentPrice = (double) $this->baseProduct->multiprices[$priceLevel];
        } else {
            $parentPrice = (double) $this->baseProduct->price;
        }
        //====================================================================//
        // Update Price on Product Combination
        $this->setVariantVariationPrice($price, $priceLevel);
        //====================================================================//
        // Commit Price Update on Parent Product (Only To Update Taxes Rates)
        $result = $this->baseProduct->updatePrice($parentPrice, $priceBase, $user, $priceVat, 0.0, $priceLevel);
        //====================================================================//
        // Check potential Errors
        if ($result < 0) {
            $this->catchDolibarrErrors($this->baseProduct);

            return false;
        }
        $this->needUpdate("baseProduct");

        return true;
    }
}
