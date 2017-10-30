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
trait UnitConverterTrait {
    
    /**
     *  @abstract   Convert Weight form all units to kg. 
     * 
     *  @param      float    $weight     Weight Value
     *  @param      int      $unit       Weight Unit
     *  @return     float                Weight Value in kg
     */
    public static function C_Weight($weight,$unit)
    { 		
        if ( $unit      == "-6")    {   return $weight * 1e-6;  }       // mg
        elseif ( $unit  == "-3")    {   return $weight * 1e-3;  }       // g
        elseif ( $unit  == "0")     {   return $weight;         }       // kg
        elseif ( $unit  == "3")     {   return $weight * 1e3;   }       // Tonne
        elseif ( $unit  == "99")    {   return $weight * 0.45359237;  } // livre
        return 0;
    }

    /**
     *  @abstract   Return Normalized Weight form raw kg value. 
     * 
     *  @param      float    $weight     Weight Raw Value
     *  @return     arrayobject          $r->weight , $r->weight_units , $r->print, $r->raw
     */
    public static function N_Weight($weight)
    { 		
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

        $r = new ArrayObject();
        if ( $weight    >= 1e3 )    {    
            $r->weight              =   $weight * 1e-3;                 // Tonne
            $r->weight_units        =   "3";                            // Tonne
        }
        elseif ( $weight    >= 1 )    {    
            $r->weight              =   $weight;                        // kg
            $r->weight_units        =   "0";                            // kg
        }
        elseif ( $weight    >= 1e-3 )    {    
            $r->weight              =   $weight * 1e3;                  // g
            $r->weight_units        =   "-3";                           // g
        }
        elseif ( $weight    >= 1e-6 )    {    
            $r->weight              =   $weight * 1e6;                  // mg
            $r->weight_units        =   "-6";                           // mg
        }
        $r->print = $r->weight." ".measuring_units_string($r->weight_units,"weight");
        $r->raw =   $weight;
        return $r;
    }

    /**
     *  @abstract   Convert Lenght form all units to m. 
     * 
     *  @param      float    $length     Length Value
     *  @param      int      $unit       Length Unit
     *  @return     float                Length Value in m
     */
    public static function C_Length($length,$unit)
    { 		
        if ( $unit      == "-3")    {   return $length / 1e3;  }        // mm
        elseif ( $unit  == "-2")    {   return $length / 1e2;  }        // cm
        elseif ( $unit  == "-1")    {   return $length / 10;  }         // dm
        elseif ( $unit  == "0")     {   return $length;  }              // m
        elseif ( $unit  == "98")    {   return $length * 0.3048;  }     // foot
        elseif ( $unit  == "99")    {   return $length * 0.0254;  }     // inch
        return 0;
    }
    
    /**
     *  @abstract   Return Normalized Length form raw m value. 
     * 
     *  @param      float    $length     Length Raw Value
     *  @return     arrayobject          $r->length , $r->length_units , $r->print, $r->raw
     */
    public static function N_Length($length)
    { 		
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');
        
        $r = new ArrayObject();
        if ( $length    >= 1 )    {    
            $r->length              =   $length;                        // m
            $r->length_units        =   "0";                            // m
        }
        elseif ( $length    >= 1e-1 )    {    
            $r->length              =   $length * 1e1;                  // dm
            $r->length_units        =   "-1";                            // dm
        }
        elseif ( $length    >= 1e-2 )    {    
            $r->length              =   $length * 1e2;                  // g
            $r->length_units        =   "-2";                           // g
        }
        elseif ( $length    >= 1e-3 )    {    
            $r->length              =   $length * 1e3;                  // mg
            $r->length_units        =   "-3";                           // mg
        }
        $r->print = $r->length." ".measuring_units_string($r->length_units,"size");
        $r->raw =   $length;
        return $r;
    }
    
    /**
     *  @abstract   Convert Surface form all units to m². 
     * 
     *  @param      float    $surface    Surface Value
     *  @param      int      $unit       Surface Unit
     *  @return     float                Surface Value in m²
     */
    public static function C_Surface($surface,$unit)
    { 		
        if ( $unit      == "-6")    {   return $surface / 1e6;  }       // mm²
        elseif ( $unit  == "-4")    {   return $surface / 1e4;  }       // cm²
        elseif ( $unit  == "-2")    {   return $surface / 1e2;  }       // dm²
        elseif ( $unit  == "0")     {   return $surface;  }             // m²
        elseif ( $unit  == "98")    {   return $surface * 0.092903;  }  // foot²
        elseif ( $unit  == "99")    {   return $surface * 0.00064516;  }// inch²
        return 0;
    }
    
    /**
     *  @abstract   Return Normalized Surface form raw m2 value. 
     * 
     *  @param      float    $surface    Surface Raw Value
     *  @return     arrayobject          $r->surface , $r->surface_units , $r->print, $r->raw
     */
    public static function N_Surface($surface)
    { 		
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

        $r = new ArrayObject();
        if ( $surface    >= 1 )    {    
            $r->surface              =   $surface;                      // m2
            $r->surface_units        =   "0";                           // m2
        }
        elseif ( $surface    >= 1e-2 )    {    
            $r->surface              =   $surface * 1e2;                // dm2
            $r->surface_units        =   "-2";                          // dm2
        }
        elseif ( $surface    >= 1e-4 )    {    
            $r->surface              =   $surface * 1e4;                // cm2
            $r->surface_units        =   "-4";                          // cm2
        }
        elseif ( $surface    >= 1e-6 )    {    
            $r->surface              =   $surface * 1e6;                // mm2
            $r->surface_units        =   "-6";                          // mm2
        }
        $r->print = $r->surface." ".measuring_units_string($r->surface_units,"surface");
        $r->raw =   $surface;
        return $r;
    }
    
    /**
     *  @abstract   Convert Volume form all units to m3. 
     * 
     *  @param      float    $volume    Volume Value
     *  @param      int      $unit       Volume Unit
     *  @return     float                Volume Value in m3
     */
    public static function C_Volume($volume,$unit)
    { 		
        if ( $unit      == "-9")    {   return $volume * 1e-9;  }              // mm²
        elseif ( $unit  == "-6")    {   return $volume * 1e-6;  }              // cm²
        elseif ( $unit  == "-3")    {   return $volume * 1e-3;  }              // dm²
        elseif ( $unit  == "0")     {   return $volume;  }                     // m²
        elseif ( $unit  == "88")    {   return $volume * 0.0283168;  }         // foot²
        elseif ( $unit  == "89")    {   return $volume * 1.6387e-5;  }         // inch²
        elseif ( $unit  == "97")    {   return $volume * 2.9574e-5;  }         // ounce
        elseif ( $unit  == "98")    {   return $volume * 1e-3;  }              // litre
        elseif ( $unit  == "99")    {   return $volume * 0.00378541;  }         // gallon
        return 0;
    }
    
/**
    *  Return Normalized Volume form raw m3 value. 
    * 
    *  @param      float    $length     Volume Raw Value
    *  @return     arrayobject          $r->volume , $r->volume_units , $r->print, $r->raw
*/
    public static function N_Volume($volume)
    { 		
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

        $r = new ArrayObject();
        if ( $volume    >= 1 )    {    
            $r->volume              =   $volume;                        // m3
            $r->volume_units        =   "0";                            // m3
        }
        elseif ( $volume    >= 1e-3 )    {    
            $r->volume              =   $volume * 1e3;                  // dm3
            $r->volume_units        =   "-3";                           // dm3
        }
        elseif ( $volume    >= 1e-6 )    {    
            $r->volume              =   $volume * 1e6;                  // cm2
            $r->volume_units        =   "-6";                           // cm2
        }
        elseif ( $volume    >= 1e-9 )    {    
            $r->volume              =   $volume * 1e9;                  // mm2
            $r->volume_units        =   "-9";                           // mm2
        }
        $r->print = $r->volume." ".measuring_units_string($r->volume_units,"volume");
        $r->raw =   $volume;
        return $r;
    } 
}
