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
    use \Splash\Local\Core\MetaDatesTrait;
    use \Splash\Local\Core\UnitConverterTrait;
    use \Splash\Local\Core\ExtraFieldsTrait;
    
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
    // ExtraFields Type
    //====================================================================//
    
    public  static $ExtraFieldsType    =  "product"; 
    
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

