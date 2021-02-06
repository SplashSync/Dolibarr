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

use Splash\Local\Services\ConfigManager;

/**
 * Dolibarr Products Multi-Prices Fields
 */
trait MultiPricesTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMultiPricesFields()
    {
        global $conf, $langs;

        //====================================================================//
        // Check if Feature Enabled & Default Price Selected
        $multiPricesLevel = ConfigManager::getDefaultPriceLevel();
        if (!$multiPricesLevel) {
            return;
        }
        //====================================================================//
        // Walk on Prices Levels
        for ($level = 1; $level <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $level++) {
            if ($level == $multiPricesLevel) {
                continue;
            }
            //====================================================================//
            // Build Field Name
            $keyforlabel = 'PRODUIT_MULTIPRICES_LABEL'.$level;
            $code = !empty($conf->global->{$keyforlabel})
                ? $langs->trans($conf->global->{$keyforlabel})
                : "Level ".$level;
            //====================================================================//
            // Product Selling Multi-Price
            $this->fieldsFactory()->create(SPL_T_PRICE)
                ->Identifier("price_level_".$level)
                ->Name(
                    "[".$code."] ".$langs->trans("SellingPrice")." (".$conf->global->MAIN_MONNAIE.")"
                )
                ->MicroData("http://schema.org/Product", "price".$code)
                ->isLogged();
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
    protected function getMultiPricesFields($key, $fieldName)
    {
        global $conf;

        //====================================================================//
        // Check if Feature Enabled & Default Price Selected
        $multiPricesLevel = ConfigManager::getDefaultPriceLevel();
        if (!$multiPricesLevel) {
            return;
        }
        //====================================================================//
        // Walk on Prices Levels
        for ($level = 1; $level <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $level++) {
            //====================================================================//
            // Detect is Requested Location
            if ($fieldName != "price_level_".$level) {
                continue;
            }
            //====================================================================//
            // Read Price for this Level
            $priceType = $this->object->multiprices_base_type[$level];
            $priceHT = (double) $this->object->multiprices[$level];
            $priceTTC = (double) $this->object->multiprices_ttc[$level];
            $priceVAT = (double) $this->object->multiprices_tva_tx[$level];
            if ($this->isVariant() && !empty($this->baseProduct)) {
                $priceVAT = (double) $this->baseProduct->multiprices_tva_tx[$level];
            }

            //====================================================================//
            // Encode Price for this Level
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
            if (null != $key) {
                unset($this->in[$key]);
            }
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
    protected function setMuiltiPricesFields($fieldName, $fieldData)
    {
        global $conf, $user;

        //====================================================================//
        // Check if Feature Enabled & Default Price Selected
        $multiPricesLevel = ConfigManager::getDefaultPriceLevel();
        if (!$multiPricesLevel) {
            return;
        }
        //====================================================================//
        // Walk on Prices Levels
        for ($level = 1; $level <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $level++) {
            //====================================================================//
            // Detect is Requested Location
            if ($fieldName != "price_level_".$level) {
                continue;
            }
            unset($this->in[$fieldName]);
            //====================================================================//
            // Read Current Product Price (Via Out Buffer)
            $this->getMultiPricesFields(null, $fieldName);
            //====================================================================//
            // Compare Prices
            if (self::prices()->compare($this->out[$fieldName], $fieldData, $conf->global->MAIN_MAX_DECIMALS_UNIT)) {
                return;
            }

            //====================================================================//
            // Perform Prices Update
            //====================================================================//

            //====================================================================//
            // Update Based on TTC Price
            if ($fieldData["base"]) {
                $price = $fieldData["ttc"];
                $priceBase = "TTC";
            //====================================================================//
            // Update Based on HT Price
            } else {
                $price = $fieldData["ht"];
                $priceBase = "HT";
            }
            //====================================================================//
            // Update Price on Product Combination
            if ($this->isVariant() && !empty($this->baseProduct)) {
                $this->setVariantPrice($price, $fieldData["vat"], $priceBase, $level);
            }
            //====================================================================//
            // Commit Price Update on Simple Product
            $result = $this->object->updatePrice($price, $priceBase, $user, $fieldData["vat"], 0.0, $level);
            //====================================================================//
            // Check potential Errors
            if ($result < 0) {
                $this->catchDolibarrErrors();

                return;
            }
            $this->needUpdate();
        }
    }
}
