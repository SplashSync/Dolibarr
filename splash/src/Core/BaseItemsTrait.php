<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace Splash\Local\Core;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Orders & Invoices Items Fields
 */
trait BaseItemsTrait
{

    //====================================================================//
    // General Class Variables
    //====================================================================//

    protected $ItemUpdate   = false;
    protected $CurrentItem  = null;
//    protected     $Items        = Null;
    
    /**
     *  @abstract     Build Address Fields using FieldFactory
     */
    protected function buildItemsFields()
    {
        global $langs;
        
        if (is_a($this, 'Splash\Local\Objects\Order')) {
            $GroupName  = $langs->trans("OrderLine");
        } elseif (is_a($this, 'Splash\Local\Objects\Invoice')) {
            $GroupName  = $langs->trans("InvoiceLine");
        } else {
            $GroupName  = "Items";
        }
        
        //====================================================================//
        // Order Line Description
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("desc")
                ->InList("lines")
                ->Name($langs->trans("Description"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/partOfInvoice", "description")
                ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Product Identifier
        $this->fieldsFactory()->Create(self::objects()->Encode("Product", SPL_T_ID))
                ->Identifier("fk_product")
                ->InList("lines")
                ->Name($langs->trans("Product"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "productID")
                ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Quantity
        $this->fieldsFactory()->Create(SPL_T_INT)
                ->Identifier("qty")
                ->InList("lines")
                ->Name($langs->trans("Quantity"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/QuantitativeValue", "value")
                ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Discount
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("remise_percent")
                ->InList("lines")
                ->Name($langs->trans("Discount"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Order", "discount")
                ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Unit Price
        $this->fieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("price")
                ->InList("lines")
                ->Name($langs->trans("Price"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/PriceSpecification", "price")
                ->Association("desc@lines", "qty@lines", "price@lines");

        //====================================================================//
        // Order Line Tax Name
        if (Splash::local()->DolVersionCmp("5.0.0") >= 0) {
            $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                    ->Identifier("vat_src_code")
                    ->InList("lines")
                    ->Name($langs->trans("VATRate"))
                    ->MicroData("http://schema.org/PriceSpecification", "valueAddedTaxName")
                    ->Group($GroupName)
                    ->AddOption('maxLength', 10)
                    ->Association("desc@lines", "qty@lines", "price@lines")
                    ;
        }
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getItemsFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::lists()->InitOutput($this->Out, "lines", $FieldName);
        if (!$FieldId) {
            return;
        }
        //====================================================================//
        // Verify List is Not Empty
        if (!is_array($this->Object->lines)) {
            return true;
        }
        //====================================================================//
        // Fill List with Data
        foreach ($this->Object->lines as $key => $OrderLine) {
            //====================================================================//
            // Read Data from Line Item
            $Value  =   $this->getItemField($OrderLine, $FieldName);
            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->Out, "lines", $FieldName, $key, $Value);
        }
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        object    $Line                   Line Data Object
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getItemField($Line, $FieldName)
    {
        global $conf;
        
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Order Line Description
            case 'desc@lines':
                return  $Line->desc;
                
            //====================================================================//
            // Order Line Product Id
            case 'fk_product@lines':
                return ($Line->fk_product)?self::objects()->Encode("Product", $Line->fk_product):null;
                
            //====================================================================//
            // Order Line Quantity
            case 'qty@lines':
                return (int) $Line->qty;
                
            //====================================================================//
            // Order Line Discount Percentile
            case "remise_percent@lines":
                return  (double) $Line->remise_percent;
                
            //====================================================================//
            // Order Line Price
            case 'price@lines':
                $Price  =   (double) $Line->subprice;
                $Vat    =   (double) $Line->tva_tx;
                return  self::prices()->Encode($Price, $Vat, null, $conf->global->MAIN_MONNAIE);

            //====================================================================//
            // Order Line Tax Name
            case 'vat_src_code@lines':
                return  $Line->vat_src_code;
                
            default:
                return null;
        }
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setItemsFields($FieldName, $Data)
    {
        //====================================================================//
        // Safety Check
        if ($FieldName !== "lines") {
            return true;
        }
        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($Data as $ItemData) {
            $this->ItemUpdate = false;
            //====================================================================//
            // Read Next Item Line
            $this->CurrentItem  =   array_shift($this->Object->lines);
            //====================================================================//
            // Update Item Line
            $this->setItem($ItemData);
        }
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Object->lines as $LineItem) {
            $this->deleteItem($LineItem);
        }
        //====================================================================//
        // Update Order/Invoice Total Prices
        $this->Object->update_price();
        //====================================================================//
        // Reload Order/Invoice Lines
        $this->Object->fetch_lines();
        
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Data to Current Item
     *
     *  @param        array     $ItemData       Input Item Data Array
     *
     *  @return         none
     */
    private function setItem($ItemData)
    {
        global $user;
        
        //====================================================================//
        // New Line ? => Create One
        if (!$this->CurrentItem) {
            //====================================================================//
            // Create New Line Item
            $this->CurrentItem =  $this->createItem();
            if (!$this->CurrentItem) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create Line Item. ");
            }
        }
        //====================================================================//
        // Update Line Description
        $this->setItemSimpleData($ItemData, "desc");
        //====================================================================//
        // Update Line Label
        $this->setItemSimpleData($ItemData, "label");
        //====================================================================//
        // Update Quantity
        $this->setItemSimpleData($ItemData, "qty");
        //====================================================================//
        // Update Discount
        $this->setItemSimpleData($ItemData, "remise_percent");
        //====================================================================//
        // Update Sub-Price
        $this->setItemPrice($ItemData);
        //====================================================================//
        // Update Vat Rate Source Name
        $this->setItemVatSrcCode($ItemData);
        //====================================================================//
        // Update Product Link
        $this->setItemProductLink($ItemData);
        //====================================================================//
        // Update Line Totals
        $this->updateItemTotals();
        //====================================================================//
        // Commit Line Update
        if (!$this->ItemUpdate) {
            return;
        }
        
        //====================================================================//
        // Prepare Args
        $Arg1 = ( Splash::local()->DolVersionCmp("5.0.0") > 0 ) ? $user : 0;
        //====================================================================//
        // Perform Line Update
        if ($this->CurrentItem->update($Arg1) <= 0) {
            $this->catchDolibarrErrors($this->CurrentItem);
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to update Line Item. ");
        }
        //====================================================================//
        // Update Item Totals
        $this->CurrentItem->update_total();
    }
            
    /**
     *  @abstract     Write Given Data To Line Item
     *
     *  @param        array     $ItemData       Input Item Data Array
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function setItemSimpleData($ItemData, $FieldName)
    {
        if (!array_key_exists($FieldName, $ItemData)) {
            return;
        }
        if ($this->CurrentItem->$FieldName !== $ItemData[$FieldName]) {
            $this->CurrentItem->$FieldName = $ItemData[$FieldName];
            $this->ItemUpdate = true;
        }
        return;
    }
    
    /**
     *  @abstract     Write Given Price to Line Item
     *
     *  @param        array     $ItemData       Input Item Data Array
     *
     *  @return         none
     */
    private function setItemPrice($ItemData)
    {
        if (!array_key_exists("price", $ItemData)) {
            return;
        }
        //====================================================================//
        // Update Unit & Sub Prices
        if (abs($this->CurrentItem->subprice - $ItemData["price"]["ht"]) > 1E-6) {
            $this->CurrentItem->subprice    = $ItemData["price"]["ht"];
            $this->CurrentItem->price       = $ItemData["price"]["ht"];
            $this->ItemUpdate      = true;
        }
        //====================================================================//
        // Update VAT Rate
        if (abs($this->CurrentItem->tva_tx - $ItemData["price"]["vat"]) > 1E-6) {
            $this->CurrentItem->tva_tx      = $ItemData["price"]["vat"];
            $this->ItemUpdate      = true;
        }
        //====================================================================//
        // Prices Safety Check
        if (empty($this->CurrentItem->subprice)) {
            $this->CurrentItem->subprice = 0;
        }
        if (empty($this->CurrentItem->price)) {
            $this->CurrentItem->price = 0;
        }
        return;
    }
    
    /**
     *  @abstract     Write Given Vat Source Code to Line Item
     *
     *  @param        array     $ItemData       Input Item Data Array
     *
     *  @return         none
     */
    private function setItemVatSrcCode($ItemData)
    {
        global $conf;
        
        if (!isset($ItemData["vat_src_code"])) {
            return;
        }
        //====================================================================//
        // Clean VAT Code
        $CleanedTaxName = substr(preg_replace('/\s/', '', $ItemData["vat_src_code"]), 0, 10);
        //====================================================================//
        // Update VAT Code if Needed
        if ($this->CurrentItem->vat_src_code !== $CleanedTaxName) {
            $this->CurrentItem->vat_src_code = $CleanedTaxName;
            $this->ItemUpdate = true;
        //====================================================================//
        // No Changes? => Exit
        } else {
            return;
        }

        //====================================================================//
        // Safety Check => Feature is Active
        if (!$conf->global->SPLASH_DETECT_TAX_NAME) {
            return;
        }

        //====================================================================//
        // Detect VAT Rates from Vat Src Code
        $IdentifiedVat      =   $this->getVatIdBySrcCode($this->CurrentItem->vat_src_code);
        if (!$IdentifiedVat) {
            return;
        }

        //====================================================================//
        // Update Rates from Vat Type
        $this->CurrentItem->tva_tx          = $IdentifiedVat->tva_tx;
        $this->CurrentItem->localtax1_tx    = $IdentifiedVat->localtax1_tx;
        $this->CurrentItem->localtax1_type  = $IdentifiedVat->localtax1_type;
        $this->CurrentItem->localtax2_tx    = $IdentifiedVat->localtax2_tx;
        $this->CurrentItem->localtax2_type  = $IdentifiedVat->localtax2_type;
        
        return;
    }
    
    /**
     *  @abstract     Identify Vat Type by Source Code
     *
     *  @param        array     $ItemData       Input Item Data Array
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getVatIdBySrcCode($VatSrcCode = null)
    {
        global $db;
        
        //====================================================================//
        // Safety Check => VAT Type Code is Not Empty
        if (empty($VatSrcCode)) {
            return null;
        }

        //====================================================================//
        // Serach for VAT Type from Given Code
        $sql  = "SELECT t.rowid, t.taux as tva_tx, t.localtax1 as localtax1_tx,";
        $sql .= " t.localtax1_type, t.localtax2 as localtax2_tx, t.localtax2_type";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t";
        $sql .= " WHERE t.code = '" . $VatSrcCode . "' AND t.active = 1";

        $resql=$db->query($sql);
        if ($resql) {
            return  $db->fetch_object($resql);
        }
        
        return null;
    }
    
    /**
     *  @abstract     Write Given Product to Line Item
     *
     *  @param        array     $ItemData       Input Item Data Array
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function setItemProductLink($ItemData)
    {
   
        if (!array_key_exists("fk_product", $ItemData)) {
            return;
        }
        //====================================================================//
        // Update Product Link
        $ProductId = self::objects()->Id($ItemData["fk_product"]);
        if ($this->CurrentItem->fk_product == $ProductId) {
            return;
        }
        if (empty($ProductId)) {
            $ProductId  =   0;
        }
        $this->CurrentItem->setValueFrom("fk_product", $ProductId, null, null, null, null, "none");
        $this->catchDolibarrErrors($this->CurrentItem);
    }
    
    protected function insertItem($Item)
    {
        $Item->subprice                     = 0;
        $Item->price                        = 0;
        $Item->qty                          = 0;
        
        $Item->total_ht                     = 0;
        $Item->total_tva                    = 0;
        $Item->total_ttc                    = 0;
        $Item->total_localtax1              = 0;
        $Item->total_localtax2              = 0;
        
        $Item->fk_multicurrency             = "NULL";
        $Item->multicurrency_code           = "NULL";
        $Item->multicurrency_subprice       = "0.0";
        $Item->multicurrency_total_ht       = "0.0";
        $Item->multicurrency_total_tva      = "0.0";
        $Item->multicurrency_total_ttc      = "0.0";
        
        if ($Item->insert() <= 0) {
            $this->catchDolibarrErrors($Item);
            return null;
        }
                
        return $Item;
    }
    
    private function updateItemTotals()
    {
        global $conf, $mysoc;
        
        if (!$this->ItemUpdate) {
            return;
        }

        //====================================================================//
        // Setup default VAT Rates from Current Item
        $VatRateOrId=   $this->CurrentItem->tva_tx;
        $UseId      =   false;
        
        //====================================================================//
        // Detect VAT Rates from Vat Src Code
        if ($conf->global->SPLASH_DETECT_TAX_NAME) {
            $IdentifiedVat      =   $this->getVatIdBySrcCode($this->CurrentItem->vat_src_code);
            if ($IdentifiedVat) {
                $VatRateOrId=   $IdentifiedVat->rowid;
                $UseId      =   true;
            }
        }
        
        //====================================================================//
        // Calcul du total TTC et de la TVA pour la ligne a partir de
        // qty, pu, remise_percent et txtva
        $localtaxes_type    =   getLocalTaxesFromRate($VatRateOrId, 0, $this->Object->socid, $mysoc, $UseId);
        
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
        
        $tabprice   =   calcul_price_total(
            $this->CurrentItem->qty,
            $this->CurrentItem->subprice,
            $this->CurrentItem->remise_percent,
            $this->CurrentItem->tva_tx,
            -1,
            -1,
            0,
            "HT",
            $this->CurrentItem->info_bits,
            $this->CurrentItem->type,
            $mysoc,
            $localtaxes_type
        );

        $this->CurrentItem->total_ht            = $tabprice[0];
        $this->CurrentItem->total_tva           = $tabprice[1];
        $this->CurrentItem->total_ttc           = $tabprice[2];
        $this->CurrentItem->total_localtax1     = $tabprice[9];
        $this->CurrentItem->total_localtax2     = $tabprice[10];
    }
}
