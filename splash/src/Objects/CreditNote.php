<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
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
use Splash\Local\Core;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PricesTrait;
use Splash\Models\Objects\SimpleFieldsTrait;

/**
 * CUSTOMERS CREDIT NOTES DATA MANAGEMENT
 */
class CreditNote extends AbstractObject
{
    // Splash Php Core Traits
    use SimpleFieldsTrait;
    use ObjectsTrait;
    use PricesTrait;
    use ListsTrait;

    // Dolibarr Core Traits
    use Core\ErrorParserTrait;
    use Core\LocalizationTrait;
    use Core\MetaDatesTrait;
    use Core\CreditModeTrait;
    use Core\BaseItemsTrait;
    use Core\ExtraFieldsTrait;
    use Core\ObjectsListTrait;
    use Core\CustomerTrait;
    use Core\MultiCompanyFieldsTrait;
    use Core\MarketplaceModeTrait;

    // Dolibarr Invoices Traits
    use Invoice\ObjectsListTrait;
    use Invoice\CRUDTrait;
    use Invoice\CoreTrait;
    use Invoice\MainTrait;
    use Invoice\ItemsTrait;
    use Invoice\PaymentsTrait;
    use Invoice\StatusTrait;

    //====================================================================//
    // ExtraFields Type
    //====================================================================//

    /**
     * @var string
     */
    public static $ExtraFieldsType = "facture";

    //====================================================================//
    // Dolibarr Type
    // 0 => Standard invoice
    // 1 => Replacement invoice
    // 2 => Credit note invoice
    // 3 => Deposit invoice
    // 4 => Proforma invoice
    //====================================================================//

    /**
     * @var array
     */
    public static $dolibarrTypes = array(2);

    /**
     * @var Facture
     */
    protected $object;

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Disable Flag. Uncomment this line to Override this flag and disable Object.
     *
     * {@inheritdoc}
     */
    protected static $DISABLED = true;

    /**
     * Object Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $NAME = "Customer Credit Note";

    /**
     * Object Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $DESCRIPTION = "Dolibarr Customers Credit Note Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static $ICO = "fa fa-eur";

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
        //====================================================================//
        //  Translate Object Name
        static::$NAME = $langs->trans("CreditNotes");
        //====================================================================//
        //  Enable Credit Notes Mode for Prices
        self::setCreditMode();
    }

    /**
     * {@inheritdoc}
     */
    public static function getIsDisabled()
    {
        if (Splash::isDebugMode()) {
            return false;
        }

        return static::$DISABLED;
    }
}
