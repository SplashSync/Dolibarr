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

namespace Splash\Local\Services;

use Splash\Core\SplashCore as Splash;

/**
 * Splash Configurations Manager
 */
class ConfigManager
{
    /**
     * Check if Splash Uses Advanced Configuration
     *
     * @return bool
     */
    public static function isExpertMode(): bool
    {
        global $conf;

        return !empty($conf->global->SPLASH_WS_EXPERT);
    }

    /**
     * Check if Splash Uses Multi-Stocks Feature
     *
     * @return bool
     */
    public static function isMultiStocksMode(): bool
    {
        global $conf;

        return self::isExpertMode() && !empty($conf->global->SPLASH_MULTISTOCK);
    }

    /**
     * Get Default Warehouse ID for Stock Updates
     *
     * @return null|int
     */
    public static function getSplashWarehouse()
    {
        global $conf;

        //====================================================================//
        // Verify Default Product Stock is defined
        if (empty($conf->global->SPLASH_STOCK) || !is_scalar($conf->global->SPLASH_STOCK)) {
            Splash::log()->errTrace("Product : No Local WareHouse Defined.");

            return null;
        }

        return (int) $conf->global->SPLASH_STOCK;
    }

    /**
     * Get Products Default Location ID
     *
     * @return null|string
     */
    public static function getProductsDefaultWarehouse()
    {
        global $conf;

        if (empty($conf->global->SPLASH_PRODUCT_STOCK) || !is_scalar($conf->global->SPLASH_PRODUCT_STOCK)) {
            return null;
        }

        return (string) $conf->global->SPLASH_PRODUCT_STOCK;
    }

    /**
     * Get Default Pricve level
     *
     * @return int
     */
    public static function getDefaultPriceLevel(): int
    {
        global $conf;

        //====================================================================//
        // If multiprices are enabled
        if (empty($conf->global->PRODUIT_MULTIPRICES)) {
            return 0;
        }

        return !empty($conf->global->SPLASH_MULTIPRICE_LEVEL) ? $conf->global->SPLASH_MULTIPRICE_LEVEL : 1;
    }
}
