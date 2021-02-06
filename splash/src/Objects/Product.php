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

use Product as BaseProduct;
use Splash\Local\Core;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\ImagesTrait;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PricesTrait;
use Splash\Models\Objects\SimpleFieldsTrait;

/**
 * Dolibarr Product for SplashSync
 */
class Product extends AbstractObject
{
    // Splash Php Core Traits
    use SimpleFieldsTrait;
    use PricesTrait;
    use ListsTrait;
    use ImagesTrait;
    use ObjectsTrait;

    // Dolibarr Core Traits
    use Core\ErrorParserTrait;
    use Core\MultilangualTrait;
    use Core\MetaDatesTrait;
    use Core\UnitConverterTrait;
    use Core\ExtraFieldsTrait;
    use Core\ImagesTrait;
    use Core\ObjectsListTrait;
    use Core\MultiCompanyFieldsTrait;
    use Core\MarketplaceModeTrait;

    // Dolibarr Products Traits
    use Product\ObjectsListTrait;                   // Objects List Readings Product Specifiers
    use Product\CRUDTrait;                          // Local Products CRUD Functions
    use Product\CoreTrait;                          // Access to Required Fields
    use Product\MultilangTrait;                     // Access to Multi-lang Fields
    use Product\MainTrait;                          // Access to Dimensions, Weights & more...
    use Product\BarcodeTrait;                       // Access to Product Barcodes
    use Product\PricesTrait;                        // Access to Product Sell & Wholesale Prices
    use Product\MultiPricesTrait;                   // Access to Product Sell Multi-Prices
    use Product\StockTrait;                         // Access to Product Stocks
    use Product\MetaTrait;                          // Access to Products Metadata
    use Product\VariantsTrait;                      // Access to Variants Fields & Management Functions

    //====================================================================//
    // ExtraFields Type
    //====================================================================//

    /**
     * @var string
     */
    public static $ExtraFieldsType = "product";

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $NAME = "Product";

    /**
     * Object Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $DESCRIPTION = "Dolibarr Product Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static $ICO = "fa fa-product-hunt";

    //====================================================================//
    // Class Constructor
    //====================================================================//

    /**
     * @var BaseProduct
     */
    protected $object;

    /**
     * @var BaseProduct
     */
    protected $baseProduct;

    /**
     * Class Constructor (Used only if localy necessary)
     */
    public function __construct()
    {
        global $langs;

        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

        //====================================================================//
        // Load Required Dolibarr Translation Files
        $langs->load("main");
        $langs->load("other");
        $langs->load("products");
        $langs->load("stocks");
        //====================================================================//
        //  Translate Object Name
        static::$NAME = $langs->trans("Products");
    }
}
