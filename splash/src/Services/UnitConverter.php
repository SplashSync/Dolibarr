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

use ArrayObject;
use Product;
use Splash\Components\UnitConverter as Converter;
use Splash\Core\SplashCore as Splash;

/**
 * Dolibarr Unit Converter
 *
 * Now Uses Splash Core Unit Converter to Detect & Convert Units Values
 */
class UnitConverter
{
    const WEIGHT = "weight";
    const LENGTH = "size";
    const SURFACE = "surface";
    const VOLUME = "volume";

    /**
     * @var array<string, array<float|int>>
     */
    private static array $knowUnits = array(
        self::WEIGHT => array(
            "-9" => Converter::MASS_MICROGRAM,
            "-6" => Converter::MASS_MILLIGRAM,
            "-3" => Converter::MASS_GRAM,
            "0" => Converter::MASS_KILOGRAM,
            "3" => Converter::MASS_TONNE,
            "98" => Converter::MASS_OUNCE,
            "99" => Converter::MASS_LIVRE,
        ),
        self::LENGTH => array(
            "-3" => Converter::LENGTH_MILIMETER,
            "-2" => Converter::LENGTH_CENTIMETER,
            "-1" => Converter::LENGTH_DECIMETER,
            "0" => Converter::LENGTH_METER,
            "3" => Converter::LENGTH_KM,
            "98" => Converter::LENGTH_FOOT,
            "99" => Converter::LENGTH_INCH,
        ),
        self::SURFACE => array(
            "-6" => Converter::AREA_MM2,
            "-4" => Converter::AREA_CM2,
            "-2" => Converter::AREA_DM2,
            "0" => Converter::AREA_M2,
            "3" => Converter::AREA_KM2,
            "98" => Converter::AREA_FOOT2,
            "99" => Converter::AREA_INCH2,
        ),
        self::VOLUME => array(
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
        $splFactor = self::detectSplashUnit((string) $unit, self::WEIGHT, Converter::MASS_KG);

        //====================================================================//
        // Convert Value to Generic Factor
        return Converter::normalizeWeight((float) $weight, $splFactor);
    }

    /**
     * Return Normalized Weight form raw kg value.
     *
     * @param null|float $weight Weight Raw Value
     *
     * @return ArrayObject $r->weight , $r->weight_units , $r->print, $r->raw
     */
    public static function normalizeWeight(?float $weight, Product $baseProduct = null): ArrayObject
    {
        $result = new ArrayObject();
        $result->raw = $weight;

        //====================================================================//
        // Variable Product Weight - Always Parent Weight Unit
        if (!empty($baseProduct)) {
            // Detect Splash Generic Unit Factor from Parent
            $splFactor = self::detectSplashUnit(
                (string) $baseProduct->weight_units,
                self::WEIGHT,
                Converter::MASS_KG
            );
            // Convert Generic Weight to Parent Unit
            $result->weight = Converter::convertWeight((float) $weight, $splFactor);
            // Force Variant Weight Unit to Parent Unit
            $result->weight_units = $baseProduct->weight_units;

            return $result;
        }
        //====================================================================//
        // Detect Best Unit
        foreach (self::getDolUnits(self::WEIGHT) as $scale => $minValue) {
            if ($weight >= $minValue) {
                $result->weight = Converter::convertWeight(
                    (float) $weight,
                    (float) self::getFactor(self::WEIGHT, $scale)
                );
                $result->weight_units = $scale;

                return $result;
            }
        }
        //====================================================================//
        // Weight - Default
        $result->weight = Converter::convertWeight((float) $weight, Converter::MASS_KILOGRAM);
        $result->weight_units = "0";

        return $result;
    }

    /**
     * Convert Length form all units to m.
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
        $splFactor = self::detectSplashUnit((string) $unit, self::LENGTH, Converter::LENGTH_M);

        //====================================================================//
        // Convert Value to Generic Factor
        return Converter::normalizeLength((float) $length, $splFactor);
    }

    /**
     * Return Normalized Length form raw m value.
     *
     * @param null|float $length Length Raw Value
     *
     * @return ArrayObject{ 'length': float, 'length_units': string, 'raw': null|float  }
     */
    public static function normalizeLength(?float $length): ArrayObject
    {
        $result = new ArrayObject();
        $result->raw = $length;
        //====================================================================//
        // Detect Best Unit
        foreach (self::getDolUnits(self::LENGTH) as $scale => $minValue) {
            if ($length >= $minValue) {
                $result->length = Converter::convertLength(
                    (float) $length,
                    (float) self::getFactor(self::LENGTH, $scale)
                );
                $result->length_units = $scale;

                return $result;
            }
        }
        //====================================================================//
        // Length - Default
        $result->length = Converter::convertLength((float) $length, Converter::LENGTH_M);
        $result->length_units = "0";

        return $result;
    }

    /**
     * Return Normalized Dimension form raw m value.
     *
     * @param null|float        $length     Length Raw Value
     * @param array<null|float> $dimensions List of Other Dimensions (in Meter)
     *
     * @return ArrayObject{ 'length': float, 'length_units': string, 'raw': null|float  }
     */
    public static function normalizeDimension(?float $length, array $dimensions): ArrayObject
    {
        //====================================================================//
        // Remove Empty Dimensions
        $dimensions = array_filter($dimensions);
        //====================================================================//
        // Find Min Dimensions
        $minDimension = !empty($dimensions) ? (float) min($dimensions) : 0.0;
        //====================================================================//
        // Convert Min Dimension to get Minimal Unit
        $minResult = self::normalizeLength($minDimension);

        //====================================================================//
        // Convert Requested Dimension to Minimal Unit
        return new ArrayObject(array(
            "length" => Converter::convertLength(
                (float) $length,
                (float) self::getFactor(self::LENGTH, $minResult->length_units)
            ),
            "length_units" => $minResult->length_units,
            "raw" => $length,
        ), ArrayObject::ARRAY_AS_PROPS);
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
     * @return ArrayObject{ 'surface': float, 'surface_units': string, 'raw': null|float  }
     */
    public static function normalizeSurface(?float $surface): ArrayObject
    {
        $result = new ArrayObject();
        $result->raw = $surface;
        //====================================================================//
        // Detect Best Unit
        foreach (self::getDolUnits(self::SURFACE) as $scale => $minValue) {
            if ($surface >= $minValue) {
                $result->surface = Converter::convertSurface(
                    (float) $surface,
                    (float) self::getFactor(self::SURFACE, $scale)
                );
                $result->surface_units = $scale;

                return $result;
            }
        }
        //====================================================================//
        // Surface - Default
        $result->surface = Converter::convertSurface((float) $surface, Converter::AREA_M2);
        $result->surface_units = "0";

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
        $splFactor = self::detectSplashUnit((string) $unit, self::VOLUME, Converter::VOLUME_M3);

        //====================================================================//
        // Convert Value to Generic Factor
        return Converter::normalizeLength((float) $volume, $splFactor);
    }

    /**
     * Return Normalized Volume form raw m3 value.
     *
     * @param null|float $volume Volume Raw Value
     *
     * @return ArrayObject{ 'volume': float, 'volume_units': string, 'raw': null|float  }
     */
    public static function normalizeVolume(?float $volume): ArrayObject
    {
        $result = new ArrayObject();
        $result->raw = $volume;
        //====================================================================//
        // Detect Best Unit
        foreach (self::getDolUnits(self::VOLUME) as $scale => $minValue) {
            if ($volume >= $minValue) {
                $result->volume = Converter::convertVolume(
                    (float) $volume,
                    (float) self::getFactor(self::VOLUME, $scale)
                );
                $result->volume_units = $scale;

                return $result;
            }
        }
        //====================================================================//
        // Volume - Default
        $result->volume = Converter::convertVolume((float) $volume, Converter::VOLUME_M3);
        $result->volume_units = "0";

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
        if (!self::loadDolUnits()) {
            return $fallBack;
        }
        if (!is_null($factor = self::getFactor($type, $unit))) {
            return $factor;
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
        // Dictionary Already Loaded
        if (isset(self::$dico)) {
            return true;
        }
        //====================================================================//
        // Load Dictionary Already Loaded
        self::$dico = array();
        $sql = "SELECT t.rowid as id, t.code, t.label, t.short_label, t.unit_type, t.scale, t.active";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_units as t WHERE t.active=1 ORDER BY t.sortorder";
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
     * Get List of Available Dolibarr Units for type.
     *
     * @return array<string, float>
     */
    private static function getDolUnits(string $type): array
    {
        $units = array();
        //====================================================================//
        // Load Dolibarr Unit IDs
        if (!self::loadDolUnits()) {
            return array();
        }
        //====================================================================//
        // Search for Unit in Dictionary
        foreach (self::$dico as $cUnit) {
            if ($cUnit->unit_type != $type) {
                continue;
            }
            //====================================================================//
            // Get Conversion Factor for Unit
            if (is_null($factor = self::getFactor($type, $cUnit->scale))) {
                continue;
            }
            $units[(string) $cUnit->scale] = Converter::normalizeLength(1.0, $factor);
        }

        return $units;
    }

    /**
     * Get Factor for Units for type.
     */
    private static function getFactor(string $type, string $scale): ?float
    {
        return self::$knowUnits[$type][$scale] ?? null;
    }
}
