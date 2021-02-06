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

namespace Splash\Local\Core;

/**
 * Dolibarr Orders & Invoices Price Converter for Debits or Credits
 */
trait CreditModeTrait
{
    /**
     * Reverse Items Prices
     *
     * @var bool
     */
    private static $isCredit = false;

    /**
     * Indicate Parser we are in Credit Notes Modes
     * All Prices Storages are Inverted
     *
     * @return $this
     */
    protected function setCreditMode()
    {
        static::$isCredit = true;

        return $this;
    }

    /**
     * Check If we are in Credit Notes Modes
     * All Prices Storages are Inverted
     *
     * @return bool
     */
    protected static function isCreditMode()
    {
        return static::$isCredit;
    }

    /**
     * Convert a Price to Inverted if Credit Mode is Enabled
     *
     * @param mixed $price
     *
     * @return float|int
     */
    protected static function parsePrice($price)
    {
        return static::$isCredit ? (-1 * $price) : $price;
    }
}
