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

namespace Splash\Local\Core;

use FactureLigne;
use OrderLine;
use Product;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;

/**
 * Dolibarr Orders & Invoices Items Fields
 */
trait BaseItemsTrait
{
    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var bool
     */
    private $itemUpdate = false;

    /**
     * @var null|FactureLigne|OrderLine
     */
    private $currentItem;

    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildItemsFields()
    {
        global $langs;

        if (is_a($this, 'Splash\Local\Objects\Order')) {
            $groupName = $langs->trans("OrderLine");
        } elseif (is_a($this, 'Splash\Local\Objects\Invoice')) {
            $groupName = $langs->trans("InvoiceLine");
        } else {
            $groupName = "Items";
        }

        //====================================================================//
        // Order Line Description
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("desc")
            ->InList("lines")
            ->Name($langs->trans("Description"))
            ->Group($groupName)
            ->MicroData("http://schema.org/partOfInvoice", "description")
            ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Product Identifier
        $this->fieldsFactory()->create((string) self::objects()->Encode("Product", SPL_T_ID))
            ->Identifier("fk_product")
            ->InList("lines")
            ->Name($langs->trans("Product"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "productID")
            ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Quantity
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("qty")
            ->InList("lines")
            ->Name($langs->trans("Quantity"))
            ->Group($groupName)
            ->MicroData("http://schema.org/QuantitativeValue", "value")
            ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Discount
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("remise_percent")
            ->InList("lines")
            ->Name($langs->trans("Discount"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Order", "discount")
            ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Unit Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->Identifier("price")
            ->InList("lines")
            ->Name($langs->trans("Price"))
            ->Group($groupName)
            ->MicroData("http://schema.org/PriceSpecification", "price")
            ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Tax Name
        if (Local::dolVersionCmp("5.0.0") >= 0) {
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("vat_src_code")
                ->InList("lines")
                ->Name($langs->trans("VATRate"))
                ->MicroData("http://schema.org/PriceSpecification", "valueAddedTaxName")
                ->Group($groupName)
                ->addOption('maxLength', '10')
                ->Association("desc@lines", "qty@lines", "price@lines")
                    ;
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getItemsFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "lines", $fieldName);
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
        foreach ($this->object->lines as $index => $orderLine) {
            //====================================================================//
            // Read Data from Line Item
            $value = $this->getItemField($orderLine, $fieldName);
            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->out, "lines", $fieldName, $index, $value);
        }
        unset($this->in[$key]);
    }

    /**
     * Insert an Item to Order or Invoice
     *
     * @param FactureLigne|OrderLine $item
     *
     * @return null|FactureLigne|OrderLine
     */
    protected function insertItem($item)
    {
        $item->subprice = 0;
        $item->price = 0;
        $item->qty = 0;

        $item->total_ht = 0;
        $item->total_tva = 0;
        $item->total_ttc = 0;
        $item->total_localtax1 = 0;
        $item->total_localtax2 = 0;

        $item->fk_multicurrency = "NULL";
        $item->multicurrency_code = "NULL";
        $item->multicurrency_subprice = "0.0";
        $item->multicurrency_total_ht = "0.0";
        $item->multicurrency_total_tva = "0.0";
        $item->multicurrency_total_ttc = "0.0";

        if ($item->insert() <= 0) {
            $this->catchDolibarrErrors($item);

            return null;
        }

        return $item;
    }

    /**
     * Read requested Field
     *
     * @param FactureLigne|OrderLine $line      Line Data Object
     * @param string                 $fieldName Field Identifier / Name
     *
     * @return null|array|bool|float|int|string
     */
    private function getItemField($line, $fieldName)
    {
        global $conf;

        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Order Line Description
            case 'desc@lines':
                return  $line->desc;
            //====================================================================//
            // Order Line Product Id
            case 'fk_product@lines':
                return ($line->fk_product)
                    ? self::objects()->Encode("Product", (string) $line->fk_product)
                    : null;
            //====================================================================//
            // Order Line Quantity
            case 'qty@lines':
                return (int) $line->qty;
            //====================================================================//
            // Order Line Discount Percentile
            case "remise_percent@lines":
                return  (double) $line->remise_percent;
            //====================================================================//
            // Order Line Price
            case 'price@lines':
                $price = (double) self::parsePrice($line->subprice);
                $vat = (double) $line->tva_tx;

                return  self::prices()->Encode($price, $vat, null, $conf->global->MAIN_MONNAIE);
            //====================================================================//
            // Order Line Tax Name
            case 'vat_src_code@lines':
                return  $line->vat_src_code;
            default:
                return null;
        }
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setItemsFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Safety Check
        if ("lines" !== $fieldName) {
            return;
        }
        //====================================================================//
        // Verify Lines List & Update if Needed
        if (is_array($fieldData) || is_a($fieldData, "ArrayObject")) {
            foreach ($fieldData as $itemData) {
                $this->itemUpdate = false;
                //====================================================================//
                // Read Next Item Line
                $this->currentItem = array_shift($this->object->lines);
                //====================================================================//
                // Update Item Line
                $this->setItem($itemData);
            }
        }
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->object->lines as $lineItem) {
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
                Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create Line Item. ");

                return;
            }
        }
        //====================================================================//
        // FIX for Module that Compare Changed Data on Update
        if (is_object($this->currentItem)) {
            $this->currentItem->oldline = clone $this->currentItem;
        }
        //====================================================================//
        // Update Line Description
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
            Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to update Line Item. ");

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
        if (!array_key_exists($fieldName, $itemData) || is_null($this->currentItem)) {
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
        if (!array_key_exists("price", $itemData) || is_null($this->currentItem)) {
            return;
        }
        //====================================================================//
        // Parse Item Prices
        $htPrice = self::parsePrice($itemData["price"]["ht"]);
        $vatPrecent = $itemData["price"]["vat"];
        //====================================================================//
        // Update Unit & Sub Prices
        if (abs($this->currentItem->subprice - $htPrice) > 1E-6) {
            $this->currentItem->subprice = $htPrice;
            $this->currentItem->price = $htPrice;
            $this->itemUpdate = true;
        }
        //====================================================================//
        // Update VAT Rate
        if (abs($this->currentItem->tva_tx - $vatPrecent) > 1E-6) {
            $this->currentItem->tva_tx = $vatPrecent;
            $this->itemUpdate = true;
        }
        //====================================================================//
        // Prices Safety Check
        if (empty($this->currentItem->subprice)) {
            $this->currentItem->subprice = 0;
        }
        if (empty($this->currentItem->price)) {
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
    private function setItemVatSrcCode($itemData)
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
        if (!$this->itemUpdate || !$conf->global->SPLASH_DETECT_TAX_NAME) {
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
     * @return null|FactureLigne|OrderLine
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
    }

    /**
     * Detect Product Id from Input Line Item with SKU Detection
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
        if (array_key_exists("fk_product", $itemData)) {
            //====================================================================//
            // Decode Splash Id String
            $fkProduct = self::objects()->Id($itemData["fk_product"]);
            if ($fkProduct) {
                return (int) $fkProduct;
            }
        }
        //====================================================================//
        // Search for Product SKU from Item Description
        if (!empty($conf->global->SPLASH_DECTECT_ITEMS_BY_SKU) && array_key_exists("desc", $itemData)) {
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
        if ($conf->global->SPLASH_DETECT_TAX_NAME) {
            $identifiedVat = $this->getVatIdBySrcCode($this->currentItem->vat_src_code);
            if ($identifiedVat) {
                $vatRateOrId = $identifiedVat->rowid;
                $useId = true;
            }
        }

        //====================================================================//
        // Calcul du total TTC et de la TVA pour la ligne a partir de
        // qty, pu, remise_percent et txtva
        $localtaxType = getLocalTaxesFromRate(
            (string) $vatRateOrId,
            0,
            $this->object->fetch_thirdparty(),
            $mysoc,
            (int) $useId
        );

        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        $tabPrice = calcul_price_total(
            (int) $this->currentItem->qty,
            $this->currentItem->subprice,
            $this->currentItem->remise_percent,
            $this->currentItem->tva_tx,
            -1,
            -1,
            0,
            "HT",
            $this->currentItem->info_bits,
            $this->currentItem->type,
            $mysoc,
            $localtaxType
        );

        $this->currentItem->total_ht = $tabPrice[0];
        $this->currentItem->total_tva = $tabPrice[1];
        $this->currentItem->total_ttc = $tabPrice[2];
        $this->currentItem->total_localtax1 = $tabPrice[9];
        $this->currentItem->total_localtax2 = $tabPrice[10];
    }
}
