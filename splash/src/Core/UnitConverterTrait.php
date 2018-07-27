<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace   Splash\Local\Core;

use ArrayObject;

/**
 * @abstract    Dolibarr Unit Converter
 */
trait UnitConverterTrait
{
    
    /**
     *  @abstract   Convert Weight form all units to kg.
     *
     *  @param      float    $weight     Weight Value
     *  @param      int      $unit       Weight Unit
     *  @return     float                Weight Value in kg
     */
    public static function convertWeight($weight, $unit)
    {
        // mg
        if ($unit      == "-6") {
            return $weight * 1e-6;
        // g
        } elseif ($unit  == "-3") {
            return $weight * 1e-3;
        // kg
        } elseif ($unit  == "0") {
            return $weight;
        // Tonne
        } elseif ($unit  == "3") {
            return $weight * 1e3;
        // livre
        } elseif ($unit  == "99") {
            return $weight * 0.45359237;
        }
        return 0;
    }

    /**
     *  @abstract   Return Normalized Weight form raw kg value.
     *
     *  @param      float    $weight     Weight Raw Value
     *  @return     arrayobject          $r->weight , $r->weight_units , $r->print, $r->raw
     */
    public static function normalizeWeight($weight)
    {
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

        $result = new ArrayObject();
        if ($weight    >= 1e3) {
            $result->weight              =   $weight * 1e-3;                 // Tonne
            $result->weight_units        =   "3";                            // Tonne
        } elseif ($weight    >= 1) {
            $result->weight              =   $weight;                        // kg
            $result->weight_units        =   "0";                            // kg
        } elseif ($weight    >= 1e-3) {
            $result->weight              =   $weight * 1e3;                  // g
            $result->weight_units        =   "-3";                           // g
        } elseif ($weight    >= 1e-6) {
            $result->weight              =   $weight * 1e6;                  // mg
            $result->weight_units        =   "-6";                           // mg
        }
        $result->print = $result->weight." ".measuring_units_string($result->weight_units, "weight");
        $result->raw =   $weight;
        return $result;
    }

    /**
     *  @abstract   Convert Lenght form all units to m.
     *
     *  @param      float    $length     Length Value
     *  @param      int      $unit       Length Unit
     *  @return     float                Length Value in m
     */
    public static function convertLength($length, $unit)
    {
        // mm
        if ($unit      == "-3") {
            return $length / 1e3;
        // cm
        } elseif ($unit  == "-2") {
            return $length / 1e2;
        // dm
        } elseif ($unit  == "-1") {
            return $length / 10;
        // m
        } elseif ($unit  == "0") {
            return $length;
        // foot
        } elseif ($unit  == "98") {
            return $length * 0.3048;
        // inch
        } elseif ($unit  == "99") {
            return $length * 0.0254;
        }
        return 0;
    }
    
    /**
     *  @abstract   Return Normalized Length form raw m value.
     *
     *  @param      float    $length     Length Raw Value
     *  @return     arrayobject          $r->length , $r->length_units , $r->print, $r->raw
     */
    public static function normalizeLength($length)
    {
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');
        
        $result = new ArrayObject();
        if ($length    >= 1) {
            $result->length              =   $length;                        // m
            $result->length_units        =   "0";                            // m
        } elseif ($length    >= 1e-1) {
            $result->length              =   $length * 1e1;                  // dm
            $result->length_units        =   "-1";                            // dm
        } elseif ($length    >= 1e-2) {
            $result->length              =   $length * 1e2;                  // g
            $result->length_units        =   "-2";                           // g
        } elseif ($length    >= 1e-3) {
            $result->length              =   $length * 1e3;                  // mg
            $result->length_units        =   "-3";                           // mg
        }
        $result->print = $result->length." ".measuring_units_string($result->length_units, "size");
        $result->raw =   $length;
        return $result;
    }
    
    /**
     *  @abstract   Convert Surface form all units to m².
     *
     *  @param      float    $surface    Surface Value
     *  @param      int      $unit       Surface Unit
     *  @return     float                Surface Value in m²
     */
    public static function convertSurface($surface, $unit)
    {
        // mm²
        if ($unit      == "-6") {
            return $surface / 1e6;
        // cm²
        } elseif ($unit  == "-4") {
            return $surface / 1e4;
        // dm²
        } elseif ($unit  == "-2") {
            return $surface / 1e2;
        // m²
        } elseif ($unit  == "0") {
            return $surface;
        // foot²
        } elseif ($unit  == "98") {
            return $surface * 0.092903;
        // inch²
        } elseif ($unit  == "99") {
            return $surface * 0.00064516;
        }
        return 0;
    }
    
    /**
     *  @abstract   Return Normalized Surface form raw m2 value.
     *
     *  @param      float    $surface    Surface Raw Value
     *  @return     arrayobject          $r->surface , $r->surface_units , $r->print, $r->raw
     */
    public static function normalizeSurface($surface)
    {
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

        $result = new ArrayObject();
        if ($surface    >= 1) {
            $result->surface              =   $surface;                      // m2
            $result->surface_units        =   "0";                           // m2
        } elseif ($surface    >= 1e-2) {
            $result->surface              =   $surface * 1e2;                // dm2
            $result->surface_units        =   "-2";                          // dm2
        } elseif ($surface    >= 1e-4) {
            $result->surface              =   $surface * 1e4;                // cm2
            $result->surface_units        =   "-4";                          // cm2
        } elseif ($surface    >= 1e-6) {
            $result->surface              =   $surface * 1e6;                // mm2
            $result->surface_units        =   "-6";                          // mm2
        }
        $result->print = $result->surface." ".measuring_units_string($result->surface_units, "surface");
        $result->raw =   $surface;
        return $result;
    }
    
    /**
     *  @abstract   Convert Volume form all units to m3.
     *
     *  @param      float    $volume        Volume Value
     *  @param      int      $unit          Volume Unit
     *
     *  @return     float   Volume Value in m3
     *
     *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function convertVolume($volume, $unit)
    {
        // mm²
        if ($unit      == "-9") {
            return $volume * 1e-9;
        // cm²
        } elseif ($unit  == "-6") {
            return $volume * 1e-6;
        // dm²
        } elseif ($unit  == "-3") {
            return $volume * 1e-3;
        // m²
        } elseif ($unit  == "0") {
            return $volume;
        // foot²
        } elseif ($unit  == "88") {
            return $volume * 0.0283168;
        // inch²
        } elseif ($unit  == "89") {
            return $volume * 1.6387e-5;
        // ounce
        } elseif ($unit  == "97") {
            return $volume * 2.9574e-5;
        // litre
        } elseif ($unit  == "98") {
            return $volume * 1e-3;
        // gallon
        } elseif ($unit  == "99") {
            return $volume * 0.00378541;
        }
        return 0;
    }
    
/**
    *  Return Normalized Volume form raw m3 value.
    *
    *  @param      float    $length     Volume Raw Value
    *  @return     arrayobject          $r->volume , $r->volume_units , $r->print, $r->raw
*/
    public static function normalizeVolume($volume)
    {
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

        $result = new ArrayObject();
        if ($volume    >= 1) {
            $result->volume              =   $volume;                        // m3
            $result->volume_units        =   "0";                            // m3
        } elseif ($volume    >= 1e-3) {
            $result->volume              =   $volume * 1e3;                  // dm3
            $result->volume_units        =   "-3";                           // dm3
        } elseif ($volume    >= 1e-6) {
            $result->volume              =   $volume * 1e6;                  // cm2
            $result->volume_units        =   "-6";                           // cm2
        } elseif ($volume    >= 1e-9) {
            $result->volume              =   $volume * 1e9;                  // mm2
            $result->volume_units        =   "-9";                           // mm2
        }
        $result->print = $result->volume." ".measuring_units_string($result->volume_units, "volume");
        $result->raw =   $volume;
        return $result;
    }
}
