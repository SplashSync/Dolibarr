<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Local\Objects;

use Commande;
use Splash\Core\SplashCore      as Splash;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\ImagesTrait;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PricesTrait;
use Splash\Models\Objects\SimpleFieldsTrait;

/**
 * CUSTOMERS ORDERS DATA MANAGEMENT
 */
class Order extends AbstractObject
{
    // Splash Php Core Traits
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use ObjectsTrait;
    use PricesTrait;
    use ListsTrait;
    use ImagesTrait;

    // Dolibarr Core Traits
    use \Splash\Local\Core\ErrorParserTrait;
    use \Splash\Local\Core\LocalizationTrait;
    use \Splash\Local\Core\MetaDatesTrait;
    use \Splash\Local\Core\CreditModeTrait;
    use \Splash\Local\Core\BaseItemsTrait;
    use \Splash\Local\Core\ExtraFieldsTrait;
    use \Splash\Local\Core\ObjectsListTrait;
    use \Splash\Local\Core\ImagesTrait;
    use \Splash\Local\Core\CustomerTrait;
    use \Splash\Local\Core\ContactsTrait;
    use \Splash\Local\Core\MultiCompanyTrait;

    // Dolibarr Orders Traits
    use \Splash\Local\Objects\Order\ObjectsListTrait;
    use \Splash\Local\Objects\Order\CRUDTrait;
    use \Splash\Local\Objects\Order\CoreTrait;
    use \Splash\Local\Objects\Order\MainTrait;
    use \Splash\Local\Objects\Order\ItemsTrait;
    use \Splash\Local\Objects\Order\StatusTrait;

    //====================================================================//
    // ExtraFields Type
    //====================================================================//

    /**
     * @var string
     */
    public static $ExtraFieldsType = "commande";

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $NAME = "Customer Order";

    /**
     * Object Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $DESCRIPTION = "Dolibarr Customers Order Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static $ICO = "fa fa-shopping-cart ";

    //====================================================================//
    // Class Constructor
    //====================================================================//

    /**
     * @var Commande
     */
    protected $object;

    /**
     * Class Constructor (Used only if localy necessary)
     */
    public function __construct()
    {
        global $langs;
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

        //====================================================================//
        // Load Required Dolibarr Translation Files
        $langs->load("main");
        $langs->load("admin");
        $langs->load("companies");
        $langs->load("orders");
        $langs->load("other");
        $langs->load("stocks");
        //====================================================================//
        //  Translate Object Name
        static::$NAME = $langs->trans("Module25Name");
    }
}
