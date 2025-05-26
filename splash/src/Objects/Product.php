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

use Product as BaseProduct;
use Splash\Local\Core;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\ImagesTrait;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PricesTrait;
use Splash\Models\Objects\PrimaryKeysAwareInterface;
use Splash\Models\Objects\SimpleFieldsTrait;

/**
 * Dolibarr Product for SplashSync
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Product extends AbstractObject implements PrimaryKeysAwareInterface
{
    // Splash Php Core Traits
    use SimpleFieldsTrait;
    use PricesTrait;
    use ListsTrait;
    use ImagesTrait;
    use ObjectsTrait;

    // Dolibarr Core Traits
    use Core\ErrorParserTrait;
    use Core\MultiLangsTrait;
    use Core\MetaDatesTrait;
    use Core\ExtraFieldsTrait;
    use Core\ImagesTrait;
    use Core\LocalizationTrait;
    use Core\ObjectsListTrait;
    use Core\MultiCompanyFieldsTrait;
    use Core\MarketplaceModeTrait;

    // Dolibarr Products Traits
    use Product\ObjectsListTrait;                   // Objects List Readings Product Specifiers
    use Product\CRUDTrait;                          // Local Products CRUD Functions
    use Product\PrimaryTrait;                       // Search Products by Primary Key
    use Product\CoreTrait;                          // Access to Required Fields
    use Product\MultiLangTrait;                     // Access to Multi-lang Fields
    use Product\MainTrait;                          // Access to Customs Code, Country, & more...
    use Product\DimensionsTrait;                    // Access to Dimensions, Weights & more...
    use Product\UnitsTrait;                         // Access to Sell Units...
    use Product\BarcodeTrait;                       // Access to Product Barcodes
    use Product\PricesTrait;                        // Access to Product Sell & Wholesale Prices
    use Product\MultiPricesTrait;                   // Access to Product Sell Multi-Prices
    use Product\StockTrait;                         // Access to Product Stocks
    use Product\MetaTrait;                          // Access to Products Metadata
    use Product\VariantsTrait;                      // Access to Variants Fields & Management Functions
    use Product\CategoriesTrait;                    // Access to Products Categories

    //====================================================================//
    // ExtraFields Type
    //====================================================================//

    /**
     * @var string
     */
    public static string $extraFieldsType = "product";

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static string $name = "Product";

    /**
     * Object Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static string $description = "Dolibarr Product Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static string $ico = "fa fa-product-hunt";

    //====================================================================//
    // Class Constructor
    //====================================================================//

    /**
     * @var BaseProduct
     */
    protected object $object;

    /**
     * @var null|BaseProduct
     */
    protected ?BaseProduct $baseProduct;

    /**
     * Class Constructor (Used only if locally necessary)
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
        static::$name = $langs->trans("Products");
    }
}
