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

namespace Splash\Local\Objects;

use Societe;
use Splash\Local\Core;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PricesTrait;
use Splash\Models\Objects\PrimaryKeysAwareInterface;
use Splash\Models\Objects\SimpleFieldsTrait;

/**
 * ThirdParty - Customers / Supplier Management Class
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class ThirdParty extends AbstractObject implements PrimaryKeysAwareInterface
{
    // Splash Php Core Traits
    use SimpleFieldsTrait;
    use ObjectsTrait;
    use PricesTrait;

    // Dolibarr Core Traits
    use Core\ErrorParserTrait;
    use Core\LocalizationTrait;
    use Core\MetaDatesTrait;
    use Core\ExtraFieldsTrait;
    use Core\ObjectsListTrait;
    use Core\MultiCompanyFieldsTrait;
    use Core\MarketplaceModeTrait;

    // Dolibarr ThirdParty Traits
    use ThirdParty\ObjectsListTrait;
    use ThirdParty\CRUDTrait;
    use ThirdParty\PrimaryTrait;
    use ThirdParty\CoreTrait;
    use ThirdParty\MainTrait;
    use ThirdParty\AddressTrait;
    use ThirdParty\CategoriesTrait;
    use ThirdParty\MetaTrait;

    //====================================================================//
    // ExtraFields Type
    //====================================================================//

    /**
     * @var string
     */
    public static string $extraFieldsType = "societe";

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static string $name = "Company";

    /**
     * Object Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static string $description = "Dolibarr Company Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static string $ico = "fa fa-user";

    /**
     * @var Societe
     */
    protected object $object;

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
        // Load Required Dolibarr Translation Files
        $langs->load("main");
        $langs->load("admin");
        $langs->load("companies");
        $langs->load("other");
        //====================================================================//
        //  Translate Object Name
        static::$name = $langs->trans("Module1Name");
    }
}
