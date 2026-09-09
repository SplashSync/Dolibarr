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

use Splash\Local\Local;
use stdClass;

/**
 * Collection of reusable functions to manage Dolibarr Tax Rates
 */
class TaxManager
{
    /**
     * Identify VAT Rate by Country & Rate
     */
    public static function findTaxByRate(float $vatRate, int $countryId = null): ?stdClass
    {
        global $db;

        //====================================================================//
        // Safety Check => Country ID is defined
        if (empty($countryId ??= self::getDefaultCountryId())) {
            return null;
        }
        //====================================================================//
        // Search for VAT Rate by Country & Rate
        $sql = "SELECT t.rowid as id, t.code, t.taux as tva_tx, t.localtax1 as localtax1_tx,";
        $sql .= " t.localtax1_type, t.localtax2 as localtax2_tx, t.localtax2_type,";
        $sql .= " t.recuperableonly as npr";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t";
        $sql .= " WHERE t.fk_pays = ".$countryId." AND t.taux = ".$vatRate." AND t.active = 1";
        $sql .= self::getTaxRatesEntityFilter();
        $sql .= self::getTaxRatesOrderBy();
        $sql .= $db->plimit(1);
        $results = $db->query($sql);
        if ($results) {
            return  $db->fetch_object($results);
        }

        return null;
    }

    /**
     * Identify Vat Type by Source Code
     *
     * @param null|string $code
     *
     * @return null|stdClass
     */
    public static function findTaxByCode(string $code = null): ?stdClass
    {
        global $db;

        //====================================================================//
        // Safety Check => VAT Type Code is Not Empty
        if (empty($code = self::getSanitizedCode($code))) {
            return null;
        }

        //====================================================================//
        // Search for VAT Type from Given Code
        $sql = "SELECT t.rowid as id, t.taux as tva_tx, t.localtax1 as localtax1_tx,";
        $sql .= " t.localtax1_type, t.localtax2 as localtax2_tx, t.localtax2_type";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t";
        $sql .= " WHERE t.code = '".$code."' AND t.active = 1";
        $sql .= self::getTaxRatesEntityFilter();

        $results = $db->query($sql);
        if ($results) {
            return  $db->fetch_object($results);
        }

        return null;
    }

    /**
     * Add Tax Rate for Country
     */
    public static function addTaxeCode(int $countryId, float $vatRate, string $code, float $vatRate2 = 0): bool
    {
        global $db;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_tva ";
        $sql .= " (`fk_pays`, `code`, `taux`, `localtax1`, `localtax1_type`,";
        $sql .= " `localtax2`, `localtax2_type`, `recuperableonly`, `note`, `active`)";
        $sql .= " VALUES ('".$countryId."', '".$code."', '".$vatRate."', '".$vatRate2."', ";
        $sql .= " '".($vatRate2 ? "1" : "0")."', '0', '0', '0', '".$code."', '1')";
        $result = $db->query($sql);
        if (!$result) {
            dol_print_error($db);

            return false;
        }
        $db->free($result);

        return true;
    }

    /**
     * Update Tax Rate Code
     */
    public static function updateTaxeCode(int $rateId, string $code): bool
    {
        global $db;

        $sql = "UPDATE ".MAIN_DB_PREFIX."c_tva as t SET code = '".$code;
        $sql .= "' WHERE t.rowId = ".$rateId;
        $result = $db->query($sql);
        if (!$result) {
            dol_print_error($db);

            return false;
        }
        $db->free($result);

        return true;
    }

    /**
     * Sanitize VAT Rate Code
     */
    public static function getSanitizedCode(?string $code): string
    {
        //====================================================================//
        // Clean VAT Code
        $taxName = preg_replace('/\s|%/', '', (string) $code);

        return is_string($taxName) ? substr($taxName, 0, 10) : "0";
    }

    /**
     * Build Multi-Company Filter for the VAT Dictionary
     *
     * The entity column was only added to llx_c_tva on Dolibarr V19.
     */
    private static function getTaxRatesEntityFilter(): string
    {
        if (Local::dolVersionCmp("19.0.0") < 0) {
            return "";
        }

        return " AND t.entity IN (".getEntity('c_tva').")";
    }

    /**
     * Build ORDER BY Clause used to Pick a Single Rate from the VAT Dictionary
     *
     * A same country & rate may hold several dictionary entries: NPR variants,
     * code-less rows, and user defined codes. Ordering must be deterministic,
     * and must not rely on rowid, which carries no business meaning and differs
     * from one installation to another.
     */
    private static function getTaxRatesOrderBy(): string
    {
        $orderBy = array();
        //====================================================================//
        // Since Dolibarr V17 => Honor Dictionary Default Flag
        // Only editable from Dictionary Setup since Dolibarr V22
        if (Local::dolVersionCmp("17.0.0") >= 0) {
            $orderBy[] = "t.use_default DESC";
        }
        //====================================================================//
        // Standard Rate before Non Recoverable (NPR) ones
        $orderBy[] = "t.recuperableonly ASC";
        //====================================================================//
        // Last Resort => Coded Rate before Code-less one, then Code itself
        $orderBy[] = "(COALESCE(t.code, '') <> '') DESC";
        $orderBy[] = "t.code ASC";

        return " ORDER BY ".implode(", ", $orderBy);
    }

    /**
     * Get Default Company Country ID
     */
    private static function getDefaultCountryId(): ?int
    {
        global $conf;

        //====================================================================//
        // Ensure Global Configuration is OK
        if (empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY) || !is_string($conf->global->MAIN_INFO_SOCIETE_COUNTRY)) {
            return null;
        }
        //====================================================================//
        // Extract ID from Configuration
        $countryId = $code = $name = null;
        sscanf($conf->global->MAIN_INFO_SOCIETE_COUNTRY, "%d:%s:%s", $countryId, $code, $name);
        if (empty($countryId) || !is_numeric($countryId)) {
            return null;
        }

        return (int) $countryId;
    }
}
