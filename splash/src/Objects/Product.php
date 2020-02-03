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

use Product as BaseProduct;
use Splash\Core\SplashCore      as Splash;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\ImagesTrait;
use Splash\Models\Objects\IntelParserTrait;
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
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use PricesTrait;
    use ListsTrait;
    use ImagesTrait;
    use ObjectsTrait;

    // Dolibarr Core Traits
    use \Splash\Local\Core\ErrorParserTrait;
    use \Splash\Local\Core\MultilangualTrait;
    use \Splash\Local\Core\MetaDatesTrait;
    use \Splash\Local\Core\UnitConverterTrait;
    use \Splash\Local\Core\ExtraFieldsTrait;
    use \Splash\Local\Core\ImagesTrait;
    use \Splash\Local\Core\ObjectsListTrait;
    use \Splash\Local\Core\MultiCompanyTrait;

    // Dolibarr Products Traits
    use Product\ObjectsListTrait;                   // Objecst List Readings Product Specifiers
    use Product\CRUDTrait;                          // Local Products CRUD Functions
    use Product\CoreTrait;                          // Access to Required Fields
    use Product\MultilangTrait;                     // Access to Multilangual Fielms
    use Product\MainTrait;                          // Access to Dimensions, Weights & more...
    use Product\BarcodeTrait;                       // Access to Product Barcodes
    use Product\PricesTrait;                        // Access to Product Sell & Wholsale Prices
    use Product\StockTrait;                         // Access to Product Stocks
    use Product\MetaTrait;                          // Access to Products Metadatas
    use Product\VariantsTrait;                      // Access to Variants Fileds & Management Functions

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
