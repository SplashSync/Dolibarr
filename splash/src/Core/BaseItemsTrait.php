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

namespace Splash\Local\Core;

use FactureLigne;
use OrderLine;
use Product;
use Societe;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Local\Services\LinesExtraFieldsParser;
use stdClass;
use SupplierInvoiceLine;

/**
 * Dolibarr Orders & Invoices Items Fields
 *
 * @phpstan-type Line FactureLigne|OrderLine|SupplierInvoiceLine
 */
trait BaseItemsTrait
{
    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var bool
     */
    private bool $itemUpdate = false;

    /**
     * @var null|Line
     */
    private $currentItem;

    /**
     * Build Line Item Fields using FieldFactory
     *
     * @return void
     */
    protected function buildItemsFields()
    {
        global $langs;

        if (is_a($this, Local::CLASS_ORDER)) {
            $groupName = $langs->trans("OrderLine");
        } elseif (is_a($this, Local::CLASS_INVOICE)) {
            $groupName = $langs->trans("InvoiceLine");
        } else {
            $groupName = "Items";
        }

        //====================================================================//
        // Order Line Description
        $descFieldName = is_a($this, Local::CLASS_SUPPLIER_INVOICE) ? "description" : "desc";
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier($descFieldName)
            ->inList("lines")
            ->name($langs->trans("Description"))
            ->group($groupName)
            ->microData("http://schema.org/partOfInvoice", "description")
            ->association($descFieldName."@lines", "qty@lines", "price@lines")
        ;
        //====================================================================//
        // Order Line Product Identifier
        $this->fieldsFactory()->create((string) self::objects()->encode("Product", SPL_T_ID))
            ->identifier("fk_product")
            ->inList("lines")
            ->name($langs->trans("Product"))
            ->group($groupName)
            ->microData("http://schema.org/Product", "productID")
            ->association($descFieldName."@lines", "qty@lines", "price@lines")
        ;
        //====================================================================//
        // Order Line Quantity
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("qty")
            ->inList("lines")
            ->name($langs->trans("Quantity"))
            ->group($groupName)
            ->microData("http://schema.org/QuantitativeValue", "value")
            ->association($descFieldName."@lines", "qty@lines", "price@lines")
        ;
        //====================================================================//
        // Order Line Discount
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("remise_percent")
            ->inList("lines")
            ->name($langs->trans("Discount"))
            ->group($groupName)
            ->microData("http://schema.org/Order", "discount")
            ->association($descFieldName."@lines", "qty@lines", "price@lines")
        ;
        //====================================================================//
        // Order Line Unit Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price")
            ->inList("lines")
            ->name($langs->trans("Price"))
            ->group($groupName)
            ->microData("http://schema.org/PriceSpecification", "price")
            ->association($descFieldName."@lines", "qty@lines", "price@lines")
        ;
        //====================================================================//
        // Order Line Tax Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("vat_src_code")
            ->inList("lines")
            ->name($langs->trans("VATRate"))
            ->microData("http://schema.org/PriceSpecification", "valueAddedTaxName")
            ->group($groupName)
            ->addOption('maxLength', '10')
            ->association($descFieldName."@lines", "qty@lines", "price@lines")
        ;

        //====================================================================//
        // Order Line Extra Fields
        LinesExtraFieldsParser::fromSplashObject($this)->buildExtraFields();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getItemsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "lines", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Verify List is Not Empty
        if (!is_array($this->object->lines)) {
            return;
        }
        //====================================================================//
        // Fill List with Data
        /** @var Line $orderLine */
        foreach ($this->object->lines as $index => $orderLine) {
            //====================================================================//
            // Read Data from Line Item
            $value = $this->getItemField($orderLine, $fieldId);
            //====================================================================//
            // Insert Data in List
            self::lists()->insert($this->out, "lines", $fieldName, $index, $value);
        }
        unset($this->in[$key]);
    }

    /**
     * Insert an Item to Order or Invoice
     *
     * @param Line $item
     *
     * @return bool
     */
    protected function insertItem(object $item): bool
    {
        if (!$item instanceof SupplierInvoiceLine) {
            $item->subprice = 0;
            $item->price = 0;
        }
        $item->qty = 0;

        $item->total_ht = 0;
        $item->total_tva = 0;
        $item->total_ttc = 0;
        $item->total_localtax1 = 0;
        $item->total_localtax2 = 0;

        $item->fk_multicurrency = 0;
        $item->multicurrency_code = "0";
        $item->multicurrency_subprice = 0.0;
        $item->multicurrency_total_ht = 0.0;
        $item->multicurrency_total_tva = 0.0;
        $item->multicurrency_total_ttc = 0.0;

        if (!method_exists($item, 'insert') || $item->insert() <= 0) {
            $this->catchDolibarrErrors($item);

            return false;
        }

        return true;
    }

    /**
     * Read requested Field
     *
     * @param Line   $line    Line Data Object
     * @param string $fieldId Field Identifier / Name
     *
     * @return null|array|bool|float|int|string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getItemField($line, string $fieldId)
    {
        global $conf;

        //====================================================================//
        // READ Fields
        switch ($fieldId) {
            //====================================================================//
            // Order Line Description
            case 'desc':
                return ($line instanceof SupplierInvoiceLine) ? "" : $line->desc;
            case 'description':
                return $line->description ?? "";
                //====================================================================//
                // Order Line Product ID
            case 'fk_product':
                return ($line->fk_product)
                    ? self::objects()->encode("Product", (string) $line->fk_product)
                    : null
                ;
                //====================================================================//
                // Order Line Quantity
            case 'qty':
                return (int) $line->qty;
                //====================================================================//
                // Order Line Discount Percentile
            case "remise_percent":
                return  (double) $line->remise_percent;
                //====================================================================//
                // Order Line Price
            case 'price':
                $price = (double) self::parsePrice($line->subprice);
                $vat = (double) $line->tva_tx;

                return  self::prices()->encode($price, $vat, null, $conf->global->MAIN_MONNAIE);
                //====================================================================//
                // Order Line Tax Name
            case 'vat_src_code':
                return  $line->vat_src_code;
                //====================================================================//
                // Extra Field or Null
            default:
                return LinesExtraFieldsParser::fromSplashObject($this)
                    ->getExtraField($line, $fieldId)
                ;
        }
    }

    /**
     * Write Given Fields
     *
     * @param string     $fieldName Field Identifier / Name
     * @param null|array $fieldData Field Data
     *
     * @return void
     */
    private function setItemsFields(string $fieldName, ?array $fieldData): void
    {
        //====================================================================//
        // Safety Check
        if ("lines" !== $fieldName) {
            return;
        }
        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($fieldData ?? array() as $itemData) {
            $this->itemUpdate = false;
            //====================================================================//
            // Read Next Item Line
            /** @var null|Line $item */
            $item = array_shift($this->object->lines);
            $this->currentItem = $item;
            //====================================================================//
            // Update Item Line
            $this->setItem($itemData);
        }
        //====================================================================//
        // Delete Remaining Lines
        /** @var Line $lineItem */
        foreach ($this->object->lines as $lineItem) {
            /** @phpstan-ignore-next-line  */
            $this->deleteItem($lineItem);
        }
        //====================================================================//
        // Update Order/Invoice Total Prices
        $this->object->update_price();
        //====================================================================//
        // Reload Order/Invoice Lines
        $this->object->fetch_lines();

        unset($this->in[$fieldName]);
    }

    /**
     * Write Data to Current Item
     *
     * @param array $itemData Input Item Data Array
     *
     * @return void
     */
    private function setItem($itemData)
    {
        global $user;

        //====================================================================//
        // New Line ? => Create One
        if (!isset($this->currentItem)) {
            //====================================================================//
            // Create New Line Item
            $this->currentItem = $this->createItem();
            if (empty($this->currentItem)) {
                Splash::log()->errTrace("Unable to create Line Item. ");

                return;
            }
        }
        //====================================================================//
        // FIX for Module that Compare Changed Data on Update
        if (property_exists($this->currentItem, 'oldline')) {
            $this->currentItem->oldline = clone $this->currentItem;
        }
        //====================================================================//
        // Update Line Description
        $this->setItemSimpleData($itemData, "description");
        $this->setItemSimpleData($itemData, "desc");
        //====================================================================//
        // Update Line Label
        $this->setItemSimpleData($itemData, "label");
        //====================================================================//
        // Update Quantity
        $this->setItemSimpleData($itemData, "qty");
        //====================================================================//
        // Update Discount
        $this->setItemSimpleData($itemData, "remise_percent");
        //====================================================================//
        // Update Sub-Price
        $this->setItemPrice($itemData);
        //====================================================================//
        // Update Vat Rate Source Name
        $this->setItemVatSrcCode($itemData);
        //====================================================================//
        // Update Product Link
        $this->setItemProductLink($itemData);
        //====================================================================//
        // Update Extra Fields
        $this->setItemExtraFields($itemData);
        //====================================================================//
        // Update Line Totals
        $this->updateItemTotals();
        //====================================================================//
        // Commit Line Update
        if (!$this->itemUpdate) {
            return;
        }
        //====================================================================//
        // Safety Check
        if (null == $this->currentItem) {
            return;
        }

        //====================================================================//
        // Prepare Args
        $arg1 = (Local::dolVersionCmp("5.0.0") > 0) ? $user : 0;
        //====================================================================//
        // Perform Line Update
        if ($this->currentItem->update($arg1) <= 0) {
            $this->catchDolibarrErrors($this->currentItem);
            Splash::log()->errTrace($this->currentItem->db->lastquery());
            Splash::log()->errTrace("Unable to update Line Item. ");

            return;
        }
        //====================================================================//
        // Update Item Totals
        $this->currentItem->update_total();
    }

    /**
     * Write Given Data To Line Item
     *
     * @param array  $itemData  Input Item Data Array
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function setItemSimpleData($itemData, $fieldName)
    {
        if (!isset($itemData[$fieldName]) || is_null($this->currentItem)) {
            return;
        }
        if ($this->currentItem->{$fieldName} !== $itemData[$fieldName]) {
            $this->currentItem->{$fieldName} = $itemData[$fieldName];
            $this->itemUpdate = true;
        }
    }

    /**
     * Write Given Price to Line Item
     *
     * @param array $itemData Input Item Data Array
     *
     * @return void
     */
    private function setItemPrice($itemData)
    {
        if (!isset($itemData["price"]) || is_null($this->currentItem)) {
            return;
        }
        //====================================================================//
        // Parse Item Prices
        $htPrice = self::parsePrice($itemData["price"]["ht"]);
        $ttcPrice = self::parsePrice($itemData["price"]["ttc"]);
        $vatPercent = $itemData["price"]["vat"];
        //====================================================================//
        // Update Unit & Sub Prices
        if (abs($this->currentItem->subprice - $htPrice) > 1E-6) {
            $this->currentItem->subprice = $htPrice;
            if ($this->currentItem instanceof SupplierInvoiceLine) {
                $this->currentItem->pu_ht = $htPrice;
                $this->currentItem->pu_ttc = $ttcPrice;
            } else {
                $this->currentItem->price = $htPrice;
            }
            $this->itemUpdate = true;
        }
        //====================================================================//
        // Update VAT Rate
        if (abs($this->currentItem->tva_tx - $vatPercent) > 1E-6) {
            $this->currentItem->tva_tx = $vatPercent;
            $this->itemUpdate = true;
        }
        //====================================================================//
        // Prices Safety Check
        if (empty($this->currentItem->subprice)) {
            $this->currentItem->subprice = 0;
        }
        if (empty($this->currentItem->price) && (!$this->currentItem instanceof SupplierInvoiceLine)) {
            $this->currentItem->price = 0;
        }
    }

    /**
     * Write Given Vat Source Code to Line Item
     *
     * @param array $itemData Input Item Data Array
     *
     * @return void
     */
    private function setItemVatSrcCode(array $itemData): void
    {
        global $conf;

        if (!isset($itemData["vat_src_code"]) || is_null($this->currentItem)) {
            return;
        }
        //====================================================================//
        // Clean VAT Code
        $taxName = preg_replace('/\s|%/', '', $itemData["vat_src_code"]);
        $cleanedTaxName = is_string($taxName) ? substr($taxName, 0, 10) : "0";
        //====================================================================//
        // Update VAT Code if Needed
        if ($this->currentItem->vat_src_code !== $cleanedTaxName) {
            $this->currentItem->vat_src_code = $cleanedTaxName;
            $this->itemUpdate = true;
        }
        //====================================================================//
        // No Changes On Item? => Exit
        // Feature is Disabled? => Exit
        if (!$this->itemUpdate || empty($conf->global->SPLASH_DETECT_TAX_NAME)) {
            return;
        }
        //====================================================================//
        // Detect VAT Rates from Vat Src Code
        $identifiedVat = $this->getVatIdBySrcCode($this->currentItem->vat_src_code);
        if (!$identifiedVat) {
            return;
        }
        //====================================================================//
        // Update Rates from Vat Type
        $this->currentItem->tva_tx = $identifiedVat->tva_tx;
        $this->currentItem->localtax1_tx = $identifiedVat->localtax1_tx;
        $this->currentItem->localtax1_type = $identifiedVat->localtax1_type;
        $this->currentItem->localtax2_tx = $identifiedVat->localtax2_tx;
        $this->currentItem->localtax2_type = $identifiedVat->localtax2_type;
    }

    /**
     * Identify Vat Type by Source Code
     *
     * @param null|string $vatSrcCode
     *
     * @return null|stdClass
     */
    private function getVatIdBySrcCode($vatSrcCode = null)
    {
        global $db;

        //====================================================================//
        // Safety Check => VAT Type Code is Not Empty
        if (empty($vatSrcCode)) {
            return null;
        }

        //====================================================================//
        // Serach for VAT Type from Given Code
        $sql = "SELECT t.rowid, t.taux as tva_tx, t.localtax1 as localtax1_tx,";
        $sql .= " t.localtax1_type, t.localtax2 as localtax2_tx, t.localtax2_type";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t";
        $sql .= " WHERE t.code = '".$vatSrcCode."' AND t.active = 1";

        $resql = $db->query($sql);
        if ($resql) {
            return  $db->fetch_object($resql);
        }

        return null;
    }

    /**
     * Write Given Product to Line Item
     *
     * @param array $itemData Input Item Data Array
     *
     * @return void
     */
    private function setItemProductLink($itemData)
    {
        //====================================================================//
        // Safety Check
        if (is_null($this->currentItem)) {
            return;
        }
        //====================================================================//
        // Compare Product Link
        $productId = $this->detectProductId($itemData);
        if ($this->currentItem->fk_product == $productId) {
            return;
        }
        //====================================================================//
        // Update Product Link
        $this->currentItem->setValueFrom("fk_product", $productId, '', null, '', '', "none");
        $this->catchDolibarrErrors($this->currentItem);
        //====================================================================//
        // Update Product Type
        $productType = $this->currentItem->getValueFrom(
            "product",
            $this->currentItem->fk_product,
            "fk_product_type"
        );
        $this->currentItem->setValueFrom("product_type", $productType, '', null, '', '', "none");
        $this->catchDolibarrErrors($this->currentItem);
    }

    /**
     * Write Given ExtraFields to Line Item
     *
     * @param array $itemData Input Item Data Array
     *
     * @return void
     */
    private function setItemExtraFields($itemData)
    {
        //====================================================================//
        // Safety Check
        if (is_null($this->currentItem) || !is_iterable($itemData)) {
            return;
        }
        $extraFieldsParser = LinesExtraFieldsParser::fromSplashObject($this);
        //====================================================================//
        // Walk on Received Data
        foreach ($itemData as $fieldName => $fieldData) {
            $update = $extraFieldsParser->setExtraField(
                $this->currentItem,
                $fieldName,
                $fieldData
            );
            if ($update) {
                $this->itemUpdate = true;
            }
        }
    }

    /**
     * Detect Product ID from Input Line Item with SKU Detection
     *
     * @param array $itemData Input Item Data Array
     *
     * @return int
     */
    private function detectProductId($itemData)
    {
        global $db, $conf;
        //====================================================================//
        // Product Id is Given
        if (isset($itemData["fk_product"])) {
            //====================================================================//
            // Decode Splash Id String
            $fkProduct = self::objects()->Id($itemData["fk_product"]);
            if ($fkProduct) {
                return (int) $fkProduct;
            }
        }
        //====================================================================//
        // Search for Product SKU from Item Description
        if (!empty($conf->global->SPLASH_DECTECT_ITEMS_BY_SKU) && isset($itemData["desc"])) {
            //====================================================================//
            // Ensure Product Class is Loaded
            include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
            //====================================================================//
            // Shorten Item Resume to remove potential spaces
            $productRef = str_replace(array(" ", "(", ")", "[", "]", "+", "/"), "", $itemData["desc"]);
            //====================================================================//
            // Try Loading product by SKU
            $product = new Product($db);
            $result = $product->fetch(0, $productRef);
            if (($result > 0) && ($product->id > 0)) {
                return $product->id;
            }
        }

        return 0;
    }

    /**
     * Update Item Totals
     *
     * @return void
     */
    private function updateItemTotals()
    {
        global $conf, $mysoc;

        if (!$this->itemUpdate || is_null($this->currentItem)) {
            return;
        }

        //====================================================================//
        // Setup default VAT Rates from Current Item
        $vatRateOrId = $this->currentItem->tva_tx;
        $useId = false;

        //====================================================================//
        // Detect VAT Rates from Vat Src Code
        if (!empty($conf->global->SPLASH_DETECT_TAX_NAME)) {
            $identifiedVat = $this->getVatIdBySrcCode($this->currentItem->vat_src_code);
            if ($identifiedVat) {
                $vatRateOrId = $identifiedVat->rowid;
                $useId = true;
            }
        }

        //====================================================================//
        // Ensure ThirdParty is Loaded
        if (!$this->object->thirdparty instanceof Societe) {
            $this->object->fetch_thirdparty();
        }
        if (!$this->object->thirdparty instanceof Societe) {
            return;
        }
        //====================================================================//
        // Calcul du total TTC et de la TVA pour la ligne à partir de
        // qty, pu, remise_percent et txtva
        $localTaxType = getLocalTaxesFromRate(
            (string) $vatRateOrId,
            0,
            $this->object->thirdparty,
            $mysoc,
            (int) $useId
        );

        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        $tabPrice = calcul_price_total(
            (int) $this->currentItem->qty,
            $this->currentItem->subprice,
            $this->currentItem->remise_percent,
            (float) $this->currentItem->tva_tx,
            -1,
            -1,
            0,
            "HT",
            $this->currentItem->info_bits,
            $this->currentItem->product_type,
            $mysoc,
            $localTaxType
        );

        $this->currentItem->total_ht = $tabPrice[0];
        $this->currentItem->total_tva = $tabPrice[1];
        $this->currentItem->total_ttc = $tabPrice[2];
        $this->currentItem->total_localtax1 = $tabPrice[9];
        $this->currentItem->total_localtax2 = $tabPrice[10];

        //====================================================================//
        // FIX for Dolibarr V16
        if (property_exists($this->currentItem, "remise") && empty($this->currentItem->remise)) {
            $this->currentItem->remise = 0;
        }
    }
}
