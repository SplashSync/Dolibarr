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

use Splash\Local\Core\ExtraFieldsPhpUnitTrait;
use Splash\Local\Local;

/**
 * Phpunit Test Sequences Manager
 */
class SequencesManager
{
    use ExtraFieldsPhpUnitTrait;

    /**
     * @var int
     */
    private static $entity;

    /**
     * Boot Manager
     */
    public static function init(): void
    {
        global $conf;

        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
        require_once(DOL_DOCUMENT_ROOT."/variants/class/ProductCombination.class.php");
        self::$entity = $conf->entity;
        //====================================================================//
        // Disable BackLog for Dolibarr Version below 9.0
        if (Local::dolVersionCmp("9.0.0") < 0) {
            $conf->blockedlog->enabled = 0;
        }
    }

    /**
     * @return string[]
     */
    public static function listSequences(): array
    {
        $list = array("Basic", "Advanced", "Variants");
        //====================================================================//
        // Enable Variant Multi-prices for Dolibarr Version above 13.0
        if (property_exists("ProductCombination", "combination_price_levels")) {
            $list[] = "Variants";
        } else {
            $list[] = "Variants";
        };
        //====================================================================//
        // Check if PhpUnit Testsuite is a Sequence Name
        $testSuite = self::getPhpunitTestSuite();
        if (in_array($testSuite, $list, true)) {
            return array($testSuite);
        }

        return $list;
    }

    //====================================================================//
    //  NEW Test Sequences
    //====================================================================//

    /**
     * Basic Sequence: Minimal Configuration with Min Options
     *
     * @return string[]
     */
    public static function initBasic(): array
    {
        self::setupCore();
        self::setupMultiLangs(false);
        self::setupMultiPrices(false);
        self::setupExtraFields(false);
        self::setupVariants(false);
        self::setupGuestOrders(false);
        self::setupCustomerRequiredFields(false);

        return array();
    }

    /**
     * Advanced Sequence: Minimal Configuration with Min Options
     * - Multi Langs Mode is Active
     * - Multi Prices Mode is Active
     * - Extra Fields are Enabled
     * - Guest Orders Mode is Active
     *
     * @return string[]
     */
    public static function initAdvanced(): array
    {
        self::setupCore();
        self::setupMultiLangs(true);
        self::setupMultiPrices(true);
        self::setupExtraFields(true);
        self::setupVariants(false);
        self::setupGuestOrders(true);
        self::setupCustomerRequiredFields(true);

        return array();
    }

    /**
     * Variants Basic Sequence: Minimal Configuration
     * - Products Variants Mode is Disabled
     * - Multi Langs Mode is Active
     * - Multi Prices Mode is Active IF AVAILABLE
     *
     * @return string[]
     */
    public static function initVariants(): array
    {
        //====================================================================//
        // Enable Variant Multi-prices for Dolibarr Version above 13.0
        $multiPrices = property_exists("ProductCombination", "combination_price_levels");

        self::setupCore();
        self::setupMultiLangs(false);
        self::setupMultiPrices($multiPrices);
        self::setupExtraFields(false);
        self::setupVariants(true);
        self::setupGuestOrders(false);
        self::setupCustomerRequiredFields(false);

        return array();
    }

    //====================================================================//
    //  Modes Setup Methods
    //====================================================================//

    /**
     * Setup of Multi Langs Mode
     */
    private static function setupMultiLangs(bool $enabled): void
    {
        global $db;

        $val = $enabled ? "1" : "0";
        dolibarr_set_const($db, "MAIN_MULTILANGS", $val, 'chaine', 0, '', self::$entity);
    }

    /**
     * Setup of Multi Langs Mode
     */
    private static function setupVariants(bool $enabled): void
    {
        global $db;

        $val = $enabled ? "1" : "0";
        dolibarr_set_const($db, "MAIN_MODULE_VARIANTS", $val, 'chaine', 0, '', self::$entity);
    }

    /**
     * Setup of Multi Prices Mode
     */
    private static function setupMultiPrices(bool $enabled): void
    {
        global $db;

        if (!$enabled) {
            dolibarr_set_const($db, "PRODUIT_MULTIPRICES", '0', 'chaine', 0, '', self::$entity);

            return;
        }
        dolibarr_set_const($db, "PRODUIT_MULTIPRICES", '1', 'chaine', 0, '', self::$entity);
        dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", '3', 'chaine', 0, '', self::$entity);
        dolibarr_set_const($db, "SPLASH_MULTIPRICE_LEVEL", "2", 'chaine', 0, '', self::$entity);
    }

    /**
     * Setup Extra Fields for Phpunit Tests
     */
    private static function setupExtraFields(bool $enabled): void
    {
        self::configurePhpUnitExtraFields("societe", $enabled);
        self::configurePhpUnitExtraFields("socpeople", $enabled);
        self::configurePhpUnitExtraFields("product", $enabled);
        self::configurePhpUnitExtraFields("commande", $enabled);
        self::configurePhpUnitExtraFields("facture", $enabled);
    }

    /**
     * Setup for Guest Orders Tests Mode
     */
    private static function setupGuestOrders(bool $enabled): void
    {
        global $db;

        if (!$enabled) {
            dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_ALLOW", '0', 'chaine', 0, '', self::$entity);

            return;
        }

        dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_ALLOW", '1', 'chaine', 0, '', self::$entity);
        dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_CUSTOMER", '1', 'chaine', 0, '', self::$entity);
        dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_EMAIL", '1', 'chaine', 0, '', self::$entity);
    }

    /**
     * Setup Customer Mandatory Fields
     */
    private static function setupCustomerRequiredFields(bool $enabled): void
    {
        global $db;

        $val = $enabled ? "1" : "0";

        dolibarr_set_const($db, "SOCIETE_EMAIL_MANDATORY", $val, 'chaine', 0, '', self::$entity);
        dolibarr_set_const($db, "SOCIETE_IDPROF1_MANDATORY", '0', 'chaine', 0, '', self::$entity);
        dolibarr_set_const($db, "SOCIETE_IDPROF2_MANDATORY", $val, 'chaine', 0, '', self::$entity);
        dolibarr_set_const($db, "SOCIETE_IDPROF3_MANDATORY", '0', 'chaine', 0, '', self::$entity);
        dolibarr_set_const($db, "SOCIETE_IDPROF4_MANDATORY", $val, 'chaine', 0, '', self::$entity);
    }

    /**
     * Setup Extra Fields for Phpunit Tests
     */
    private static function setupCore(): void
    {
        global $db;

        //====================================================================//
        //  Force Disable of Multi Company Mode/Flags
        dolibarr_set_const($db, "MAIN_MODULE_MULTICOMPANY", '0', 'chaine', 0, '', 0);
        MultiCompany::isMultiCompany(true);
    }

    //====================================================================//
    //  Private Methods
    //====================================================================//

    /**
     * Detect Current PhpUnit Test Suite Name
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private static function getPhpunitTestSuite(): ?string
    {
        foreach ($_SERVER['argv'] as $arg) {
            if (!is_scalar($arg)) {
                continue;
            }
            sscanf((string) $arg, "--testsuite=%s", $testsuite);
            if ($testsuite && is_string($testsuite)) {
                return $testsuite;
            }
        }

        return null;
    }
}
