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

namespace   Splash\Local\Core;

use ArrayObject;
use Splash\Components\UnitConverter as Converter;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;

/**
 * Dolibarr Unit Converter
 *
 * Now Uses Splash Core Unit Converter to Detect & Convert Units Values
 */
trait UnitConverterTrait
{
    /**
     * @var array<string, array<float|int>>
     */
    private static array $knowUnits = array(
        "weight" => array(
            "-9" => Converter::MASS_MICROGRAM,
            "-6" => Converter::MASS_MILLIGRAM,
            "-3" => Converter::MASS_GRAM,
            "0" => Converter::MASS_KILOGRAM,
            "3" => Converter::MASS_TONNE,
            "98" => Converter::MASS_OUNCE,
            "99" => Converter::MASS_LIVRE,
        ),
        "length" => array(
            "-3" => Converter::LENGTH_MILIMETER,
            "-2" => Converter::LENGTH_CENTIMETER,
            "-1" => Converter::LENGTH_DECIMETER,
            "0" => Converter::LENGTH_METER,
            "3" => Converter::LENGTH_KM,
            "98" => Converter::LENGTH_FOOT,
            "99" => Converter::LENGTH_INCH,
        ),
        "surface" => array(
            "-6" => Converter::AREA_MM2,
            "-4" => Converter::AREA_CM2,
            "-2" => Converter::AREA_DM2,
            "0" => Converter::AREA_M2,
            "3" => Converter::AREA_KM2,
            "98" => Converter::AREA_FOOT2,
            "99" => Converter::AREA_INCH2,
        ),
        "volume" => array(
            "-9" => Converter::VOLUME_MM3,
            "-6" => Converter::VOLUME_CM3,
            "-3" => Converter::VOLUME_DM3,
            "0" => Converter::VOLUME_M3,
            "88" => Converter::VOLUME_FOOT3,
            "89" => Converter::VOLUME_INCH3,
            "97" => Converter::VOLUME_OUNCE3,
            "98" => Converter::VOLUME_LITER,
            "99" => Converter::VOLUME_GALON,
        ),
    );

    /**
     * Current Install Units Dictionary
     *
     * @var array
     */
    private static array $dico;

    /**
     * Convert Weight form all units to kg.
     *
     * @param null|float $weight Weight Value
     * @param int|string $unit   Weight Unit
     *
     * @return float Weight Value in kg
     */
    public static function convertWeight(?float $weight, $unit): float
    {
        //====================================================================//
        // Detect Splash Generic Unit Factor
        $splFactor = self::detectSplashUnit((string) $unit, "weight", Converter::MASS_KG);
        //====================================================================//
        // Convert Value to Generic Factor
        return Converter::normalizeWeight((float) $weight, $splFactor);
    }

    /**
     * Return Normalized Weight form raw kg value.
     *
     * @param null|float $weight Weight Raw Value
     *
     * @return arrayobject $r->weight , $r->weight_units , $r->print, $r->raw
     */
    public function normalizeWeight(?float $weight): ArrayObject
    {
        $result = new ArrayObject();

        //====================================================================//
        // Variable Product Weight - Always Parent Weight Unit
        if ($this->isVariant() && !empty($this->baseProduct)) {
            // Detect Splash Generic Unit Factor from Parent
            $splFactor = self::detectSplashUnit(
                (string) $this->baseProduct->weight_units,
                "weight",
                Converter::MASS_KG
            );
            // Convert Generic Weight to Parent Unit
            $result->weight = Converter::convertWeight((float) $weight, $splFactor);
            // Force Variant Weight Unit to Parent Unit
            $result->weight_units = $this->baseProduct->weight_units;
        //====================================================================//
        // Weight - Tonne
        } elseif ($weight >= 1e3) {
            $result->weight = Converter::convertWeight((float) $weight, Converter::MASS_TONNE);
            $result->weight_units = self::getDolUnitId("weight", "3");
        //====================================================================//
        // Weight - KiloGram
        } elseif ($weight >= 1) {
            $result->weight = Converter::convertWeight((float) $weight, Converter::MASS_KILOGRAM);
            $result->weight_units = self::getDolUnitId("weight", "0");
        //====================================================================//
        // Weight - Gram
        } elseif ($weight >= 1e-3) {
            $result->weight = Converter::convertWeight((float) $weight, Converter::MASS_GRAM);
            $result->weight_units = self::getDolUnitId("weight", "-3");
        //====================================================================//
        // Weight - MilliGram
        } elseif ($weight >= 1e-6) {
            $result->weight = Converter::convertWeight((float) $weight, Converter::MASS_MILLIGRAM);
            $result->weight_units = self::getDolUnitId("weight", "-6");
        }
        $result->raw = $weight;

        return $result;
    }

    /**
     * Convert Lenght form all units to m.
     *
     * @param null|float $length Length Value
     * @param int|string $unit   Length Unit
     *
     * @return float Length Value in m
     */
    public static function convertLength(?float $length, $unit): float
    {
        //====================================================================//
        // Detect Splash Generic Unit Factor
        $splFactor = self::detectSplashUnit((string) $unit, "length", Converter::LENGTH_M);
        //====================================================================//
        // Convert Value to Generic Factor
        return Converter::normalizeLength((float) $length, $splFactor);
    }

    /**
     * Return Normalized Length form raw m value.
     *
     * @param null|float $length Length Raw Value
     *
     * @return arrayobject $r->length , $r->length_units , $r->print, $r->raw
     */
    public static function normalizeLength(?float $length): ArrayObject
    {
        $result = new ArrayObject();
        //====================================================================//
        // Length - Meter
        if ($length >= 1) {
            $result->length = Converter::convertLength((float) $length, Converter::LENGTH_M);
            $result->length_units = self::getDolUnitId("size", "0");
        //====================================================================//
        // Length - DecaMeter
        } elseif ($length >= 1e-1) {
            $result->length = Converter::convertLength((float) $length, Converter::LENGTH_DM);
            $result->length_units = self::getDolUnitId("size", "-1");
        //====================================================================//
        // Length - CentiMeter
        } elseif ($length >= 1e-2) {
            $result->length = Converter::convertLength((float) $length, Converter::LENGTH_CM);
            $result->length_units = self::getDolUnitId("size", "-2");
        //====================================================================//
        // Length - MilliMeter
        } elseif ($length >= 1e-3) {
            $result->length = Converter::convertLength((float) $length, Converter::LENGTH_MM);
            $result->length_units = self::getDolUnitId("size", "-3");
        }
        $result->raw = $length;

        return $result;
    }

    /**
     * Convert Surface form all units to m².
     *
     * @param null|float $surface Surface Value
     * @param int|string $unit    Surface Unit
     *
     * @return float Surface Value in m²
     */
    public static function convertSurface(?float $surface, $unit): float
    {
        //====================================================================//
        // Detect Splash Generic Unit Factor
        $splFactor = self::detectSplashUnit((string) $unit, "surface", Converter::AREA_M2);
        //====================================================================//
        // Convert Value to Generic Factor
        return Converter::normalizeLength((float) $surface, $splFactor);
    }

    /**
     * Return Normalized Surface form raw m2 value.
     *
     * @param null|float $surface Surface Raw Value
     *
     * @return arrayobject $r->surface , $r->surface_units , $r->print, $r->raw
     */
    public static function normalizeSurface(?float $surface): ArrayObject
    {
        $result = new ArrayObject();
        //====================================================================//
        // Surface - Meter 2
        if ($surface >= 1) {
            $result->surface = Converter::convertSurface((float) $surface, Converter::AREA_M2);
            $result->surface_units = self::getDolUnitId("surface", "0");
        //====================================================================//
        // Surface - DecaMeter 2
        } elseif ($surface >= 1e-2) {
            $result->surface = Converter::convertSurface((float) $surface, Converter::AREA_DM2);
            $result->surface_units = self::getDolUnitId("surface", "-2");
        //====================================================================//
        // Surface - CentiMeter 2
        } elseif ($surface >= 1e-4) {
            $result->surface = Converter::convertSurface((float) $surface, Converter::AREA_CM2);
            $result->surface_units = self::getDolUnitId("surface", "-4");
        //====================================================================//
        // Surface - MilliMeter 2
        } elseif ($surface >= 1e-6) {
            $result->surface = Converter::convertSurface((float) $surface, Converter::AREA_MM2);
            $result->surface_units = self::getDolUnitId("surface", "-6");
        }
        $result->raw = $surface;

        return $result;
    }

    /**
     * Convert Volume form all units to m3.
     *
     * @param null|float $volume Volume Value
     * @param int|string $unit   Volume Unit
     *
     * @return float Volume Value in m3
     */
    public static function convertVolume(?float $volume, $unit): float
    {
        //====================================================================//
        // Detect Splash Generic Unit Factor
        $splFactor = self::detectSplashUnit((string) $unit, "volume", Converter::VOLUME_M3);
        //====================================================================//
        // Convert Value to Generic Factor
        return Converter::normalizeLength((float) $volume, $splFactor);
    }

    /**
     * Return Normalized Volume form raw m3 value.
     *
     * @param null|float $volume Volume Raw Value
     *
     * @return arrayobject $r->volume , $r->volume_units , $r->print, $r->raw
     */
    public static function normalizeVolume(?float $volume): ArrayObject
    {
        $result = new ArrayObject();
        //====================================================================//
        // Volume - Meter 3
        if ($volume >= 1) {
            $result->volume = Converter::convertVolume((float) $volume, Converter::VOLUME_M3);
            $result->volume_units = self::getDolUnitId("volume", "0");
        //====================================================================//
        // Volume - DecaMeter 3
        } elseif ($volume >= 1e-3) {
            $result->volume = Converter::convertVolume((float) $volume, Converter::VOLUME_DM3);
            $result->volume_units = self::getDolUnitId("volume", "-3");
        //====================================================================//
        // Volume - CentiMeter 3
        } elseif ($volume >= 1e-6) {
            $result->volume = Converter::convertVolume((float) $volume, Converter::VOLUME_CM3);
            $result->volume_units = self::getDolUnitId("volume", "-6");
        //====================================================================//
        // Volume - MilliMeter 3
        } elseif ($volume >= 1e-9) {
            $result->volume = Converter::convertVolume((float) $volume, Converter::VOLUME_MM3);
            $result->volume_units = self::getDolUnitId("volume", "-9");
        }
        $result->raw = $volume;

        return $result;
    }

    /**
     * Detect Unit from Object or Database Dictionary.
     *
     * @param string $unit     Raw Unit Code or Database Id
     * @param string $type     Unit Type
     * @param float  $fallBack FallBack Splash Unit Code
     *
     * @return float Splash Unit Factor
     */
    private static function detectSplashUnit(string $unit, string $type, float $fallBack): float
    {
        //====================================================================//
        // STANDARD => Dolibarr Unit Scale Factored Stored in Objects
        if (!self::useDatabaseUnitsIds()) {
            if (isset(self::$knowUnits[$type][$unit])) {
                return self::$knowUnits[$type][$unit];
            }

            return $fallBack;
        }

        //====================================================================//
        // SINCE V10 => Dolibarr Unit Code Stored in Dictionary
        if (!self::loadDolUnits() || !isset(self::$dico[$unit])) {
            return $fallBack;
        }
        if (isset(self::$knowUnits[$type][self::$dico[$unit]->scale])) {
            return self::$knowUnits[$type][self::$dico[$unit]->scale];
        }

        return $fallBack;
    }

    /**
     * Load Units Scales from Dictionary in Database.
     *
     * @return bool
     */
    private static function loadDolUnits(): bool
    {
        global $db;
        //====================================================================//
        // STANDARD => Dolibarr Unit Scale Factored Stored in Objects
        if (!self::useDatabaseUnitsIds()) {
            return true;
        }
        //====================================================================//
        // Dictionary Already Loaded
        if (isset(self::$dico)) {
            return true;
        }
        //====================================================================//
        // Load Dictionary Already Loaded
        self::$dico = array();
        $sql = "SELECT t.rowid as id, t.code, t.label, t.short_label, t.unit_type, t.scale, t.active";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_units as t WHERE t.active=1";
        $resSql = $db->query($sql);
        if (!$resSql) {
            return Splash::log()->errTrace($db->lasterror());
        }
        //====================================================================//
        // Parse Dictionary to Cache
        $num = $db->num_rows($resSql);
        if ($num > 0) {
            while ($obj = $db->fetch_object($resSql)) {
                self::$dico[$obj->id] = $obj;
            }
        }
        $db->free($resSql);

        return true;
    }

    /**
     * Identify Dolibarr Unit from Scale.
     *
     * @param string $type
     * @param string $scale
     *
     * @return int|string
     */
    private static function getDolUnitId(string $type, string $scale)
    {
        //====================================================================//
        // STANDARD => Dolibarr Unit Scale Factored Stored in Objects
        if (!self::useDatabaseUnitsIds()) {
            return $scale;
        }
        //====================================================================//
        // V10.0.0 to V10.0.2 => Dolibarr Unit IDs Stored in Object
        if (!self::loadDolUnits()) {
            return 0;
        }
        //====================================================================//
        // Search for Unit in Dictionary
        foreach (self::$dico as $cUnit) {
            if ($cUnit->unit_type != $type) {
                continue;
            }
            if ($cUnit->scale != $scale) {
                continue;
            }

            return $cUnit->id;
        }

        return 0;
    }

    /**
     * Detect if Stored Units are Scales or Database Dictionary IDs.
     *
     * V10.0.0 to V10.0.2 => Dolibarr Unit IDs Stored in Objects
     *
     * @return bool TRUE if Database
     */
    private static function useDatabaseUnitsIds(): bool
    {
        if (Local::dolVersionCmp("10.0.0") < 0) {
            return false;
        }
        if (Local::dolVersionCmp("10.0.2") > 0) {
            return false;
        }

        return true;
    }
}
