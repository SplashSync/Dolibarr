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

use Splash\Local\Core;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PricesTrait;
use Splash\Models\Objects\SimpleFieldsTrait;

/**
 * Dolibarr Contacts Address for SplashSync
 */
class Address extends AbstractObject
{
    // Splash Php Core Traits
    use SimpleFieldsTrait;
    use ObjectsTrait;
    use PricesTrait;

    // Dolibarr Core Traits
    use Core\ErrorParserTrait;
    use Core\DirectAccessTrait;
    use Core\LocalizationTrait;
    use Core\MetaDatesTrait;
    use Core\ExtraFieldsTrait;
    use Core\ObjectsListTrait;
    use Core\MultiCompanyFieldsTrait;
    use Core\MarketplaceModeTrait;

    // Dolibarr Address Traits
    use Address\ObjectsListTrait;
    use Address\CRUDTrait;
    use Address\CoreTrait;
    use Address\MainTrait;

    //====================================================================//
    // ExtraFields Type
    //====================================================================//

    /**
     * @var string
     */
    public static $ExtraFieldsType = "socpeople";

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $NAME = "Contact Address";

    /**
     * Object Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $DESCRIPTION = "Dolibarr Contact Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static $ICO = "fa fa-envelope-o";

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
        require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

        //====================================================================//
        // Load Required Dolibarr Translation Files
        $langs->load("main");
        $langs->load("admin");
        $langs->load("companies");
        $langs->load("other");
        //====================================================================//
        //  Translate Object Name
        static::$NAME = $langs->trans("Address");
    }
}
