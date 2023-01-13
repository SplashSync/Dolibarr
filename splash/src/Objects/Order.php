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

namespace   Splash\Local\Objects;

use Commande;
use Splash\Local\Core;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\ImagesTrait;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PricesTrait;
use Splash\Models\Objects\SimpleFieldsTrait;

/**
 * CUSTOMERS ORDERS DATA MANAGEMENT
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Order extends AbstractObject
{
    // Splash Php Core Traits
    use SimpleFieldsTrait;
    use ObjectsTrait;
    use PricesTrait;
    use ListsTrait;
    use ImagesTrait;

    // Dolibarr Core Traits
    use Core\ErrorParserTrait;
    use Core\LocalizationTrait;
    use Core\MetaDatesTrait;
    use Core\CreditModeTrait;
    use Core\BaseItemsTrait;
    use Core\ExtraFieldsTrait;
    use Core\ObjectsListTrait;
    use Core\ImagesTrait;
    use Core\CustomerTrait;
    use Core\ContactsTrait;
    use Core\MultiCompanyFieldsTrait;
    use Core\MarketplaceModeTrait;
    use Core\DownloadUrlsTrait;

    // Dolibarr Common Fields Traits
    use Common\PaymentMethodTrait;
    use Common\BankAccountTrait;

    // Dolibarr Orders Traits
    use Order\ObjectsListTrait;
    use Order\CRUDTrait;
    use Order\CoreTrait;
    use Order\MainTrait;
    use Order\ItemsTrait;
    use Order\StatusTrait;

    //====================================================================//
    // ExtraFields Type
    //====================================================================//

    /**
     * Type for Extra Fields
     *
     * @var string
     */
    public static string $extraFieldsType = "commande";

    /**
     * Type for Lines Extra Fields
     *
     * @var string
     */
    public static string $extraLineFieldsType = "commandedet";

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static string $name = "Customer Order";

    /**
     * Object Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static string $description = "Dolibarr Customers Order Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static string $ico = "fa fa-shopping-cart ";

    //====================================================================//
    // Class Constructor
    //====================================================================//

    /**
     * @var Commande
     */
    protected object $object;

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
        $langs->load("banks");
        $langs->load("stocks");
        //====================================================================//
        //  Translate Object Name
        static::$name = $langs->trans("Module25Name");
    }
}
