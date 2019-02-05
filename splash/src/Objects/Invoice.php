<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Local\Objects;

use Facture;
use Splash\Core\SplashCore      as Splash;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PricesTrait;
use Splash\Models\Objects\SimpleFieldsTrait;

/**
 * CUSTOMERS INVOICE DATA MANAGEMENT
 */
class Invoice extends AbstractObject
{
    // Splash Php Core Traits
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use ObjectsTrait;
    use PricesTrait;
    use ListsTrait;
    
    // Dolibarr Core Traits
    use \Splash\Local\Core\ErrorParserTrait;
    use \Splash\Local\Core\LocalizationTrait;
    use \Splash\Local\Core\MetaDatesTrait;
    use \Splash\Local\Core\BaseItemsTrait;
    use \Splash\Local\Core\ExtraFieldsTrait;
    use \Splash\Local\Core\ObjectsListTrait;
    use \Splash\Local\Core\CustomerTrait;
    use \Splash\Local\Core\MultiCompanyTrait;
    
    // Dolibarr Invoices Traits
    use \Splash\Local\Objects\Invoice\ObjectsListTrait;
    use \Splash\Local\Objects\Invoice\CRUDTrait;
    use \Splash\Local\Objects\Invoice\CoreTrait;
    use \Splash\Local\Objects\Invoice\MainTrait;
    use \Splash\Local\Objects\Invoice\ItemsTrait;
    use \Splash\Local\Objects\Invoice\PaymentsTrait;
    use \Splash\Local\Objects\Invoice\StatusTrait;
    
    //====================================================================//
    // ExtraFields Type
    //====================================================================//
    
    public static $ExtraFieldsType    =  "facture";
    
    /**
     * @var Facture
     */
    protected $object;
    
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment this line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static $NAME            =  "Customer Invoice";
    
    /**
     *  Object Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "Dolibarr Customers Invoice Object";
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO     =  "fa fa-money";
    
    //====================================================================//
    // Class Constructor
    //====================================================================//
    
    /**
     * Class Constructor (Used only if localy necessary)
     */
    public function __construct()
    {
        global $langs;
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

        //====================================================================//
        // Load Required Dolibarr Translation Files
        $langs->load("main");
        $langs->load("admin");
        $langs->load("companies");
        $langs->load("orders");
        $langs->load("bills");
        $langs->load("other");
        $langs->load("stocks");
        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->Load("objects@local");
    }
}
