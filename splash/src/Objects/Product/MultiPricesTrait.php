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
    protected function buildMultiPricesFields(): void
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
                ->identifier("price_level_".$level)
                ->name(
                    "[".$code."] ".$langs->trans("SellingPrice")." (".$conf->global->MAIN_MONNAIE.")"
                )
                ->microData("http://schema.org/Product", "price".$code)
                ->isLogged()
            ;
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
    protected function getMultiPricesFields(?string $key, string $fieldName): void
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
            $priceType = $this->object->multiprices_base_type[$level] ?? 0;
            $priceHT = (double) ($this->object->multiprices[$level] ?? 0);
            $priceTTC = (double) ($this->object->multiprices_ttc[$level] ?? 0);
            $priceVAT = (double) ($this->object->multiprices_tva_tx[$level] ?? 0);
            if ($this->isVariant() && !empty($this->baseProduct)) {
                $priceVAT = (double) ($this->baseProduct->multiprices_tva_tx[$level] ?? 0);
            }

            //====================================================================//
            // Encode Price for this Level
            if ('TTC' === $priceType) {
                $this->out[$fieldName] = self::prices()->encode(
                    null,
                    $priceVAT,
                    $priceTTC,
                    $conf->global->MAIN_MONNAIE
                );
            } else {
                $this->out[$fieldName] = self::prices()->encode(
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
     * @param string     $fieldName Field Identifier / Name
     * @param null|array $fieldData Field Data
     *
     * @return void
     */
    protected function setMultiPricesFields(string $fieldName, ?array $fieldData): void
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
            /** @var array $fieldData */
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
