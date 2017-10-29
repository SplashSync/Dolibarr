<?php
/*
 * Copyright (C) 2011-2014  Bernard Paquier       <bernard.paquier@gmail.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * 
 *  \Id 	$Id: osws-local-Customers.class.php 92 2014-09-16 22:18:01Z Nanard33 $
 *  \version    $Revision: 92 $
 *  \date       $LastChangedDate: 2014-09-17 00:18:01 +0200 (mer. 17 sept. 2014) $ 
 *  \ingroup    OSWS - Open Synchronisation WebService
 *  \brief      Local Function Definition for Management of Customers Data
 *  \class      OsWs_Local_Customers
 *  \remarks	Designed for Splash Module - Dolibar ERP Version
*/
                    
//====================================================================//
// *******************************************************************//
//                     SPLASH FOR DOLIBARR                            //
// *******************************************************************//
//               PRODUCT / SERVICE DATA MANAGEMENT                    //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Objects;

use Splash\Core\SplashCore      as Splash;

use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\SimpleFieldsTrait;
use Splash\Models\Objects\PricesTrait;

/**
 * @abstract Dolibarr Product for SplashSync
 */
class Product extends AbstractObject
{
    // Splash Php Core Traits
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use PricesTrait;

    // Dolibarr Core Traits
    use \Splash\Local\Core\ErrorParserTrait;
    use \Splash\Local\Core\MultilangualTrait;
    
    // Dolibarr Products Traits
    use \Splash\Local\Objects\Product\ObjectsListTrait;
    use \Splash\Local\Objects\Product\CRUDTrait;
    use \Splash\Local\Objects\Product\CoreTrait;
    use \Splash\Local\Objects\Product\DescriptionsTrait;
    use \Splash\Local\Objects\Product\MainTrait;
    use \Splash\Local\Objects\Product\StockTrait;
    use \Splash\Local\Objects\Product\MetaTrait;
        
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment thius line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static    $NAME            =  "Product";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Dolibarr Product Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-product-hunt";
    
    //====================================================================//
    // Class Constructor
    //====================================================================//
        
    /**
     *      @abstract       Class Constructor (Used only if localy necessary)
     *      @return         int                     0 if KO, >0 if OK
     */
    function __construct()
    {
        global $langs;
        
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
        //====================================================================//
        // Load Dolibarr Default Language
        Splash::Local()->LoadDefaultLanguage();
        
        //====================================================================//
        // Load Required Dolibarr Translation Files
        $langs->load("main");
        $langs->load("other");
        $langs->load("products");
        $langs->load("stocks");
        
        return True;
    }    

}

