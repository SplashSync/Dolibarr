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
trait BaseItemsTrait {

    //====================================================================//
    // General Class Variables	
    //====================================================================//

    protected     $ItemUpdate   = False;
    protected     $CurrentItem  = Null;
//    protected     $Items        = Null;
    
    /**
     *  @abstract     Build Address Fields using FieldFactory
     */
    protected function buildItemsFields() {
        global $langs;
        
        if (is_a( $this, 'Splash\Local\Objects\Order')) {
            $GroupName  = $langs->trans("OrderLine");
        } elseif (is_a( $this, 'Splash\Local\Objects\Invoice')) {
            $GroupName  = $langs->trans("InvoiceLine");
        }
        
        //====================================================================//
        // Order Line Description
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("desc")
                ->InList("lines")
                ->Name( $langs->trans("Description"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/partOfInvoice","description")        
                ->Association("desc@lines","qty@lines","price@lines");        

        //====================================================================//
        // Order Line Product Identifier
        $this->FieldsFactory()->Create(self::Objects()->Encode( "Product" , SPL_T_ID))        
                ->Identifier("fk_product")
                ->InList("lines")
                ->Name( $langs->trans("Product"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product","productID")
                ->Association("desc@lines","qty@lines","price@lines");            

        //====================================================================//
        // Order Line Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)        
                ->Identifier("qty")
                ->InList("lines")
                ->Name( $langs->trans("Quantity"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/QuantitativeValue","value")        
                ->Association("desc@lines","qty@lines","price@lines");        

        //====================================================================//
        // Order Line Discount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("remise_percent")
                ->InList("lines")
                ->Name( $langs->trans("Discount"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Order","discount")
                ->Association("desc@lines","qty@lines","price@lines");        

        //====================================================================//
        // Order Line Unit Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)        
                ->Identifier("price")
                ->InList("lines")
                ->Name( $langs->trans("Price"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/PriceSpecification","price")        
                ->Association("desc@lines","qty@lines","price@lines");        

    }

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    protected function getItemsFields($Key,$FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput( $this->Out, "lines", $FieldName );
        if ( !$FieldId ) {
            return;
        } 
        //====================================================================//
        // Verify List is Not Empty
        if ( !is_array($this->Object->lines) ) {
            return True;
        }        
        //====================================================================//
        // Fill List with Data
        foreach ($this->Object->lines as $key => $OrderLine) {
            //====================================================================//
            // Read Data from Line Item
            $Value  =   $this->getItemField($OrderLine,$FieldName);
            //====================================================================//
            // Insert Data in List
            self::Lists()->Insert( $this->Out, "lines", $FieldName, $key, $Value );
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
    private function getItemField($Line,$FieldName)
    {
        global $conf;
        
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Order Line Description
            case 'desc@lines':
                return  $Line->desc;
                
            //====================================================================//
            // Order Line Product Id
            case 'fk_product@lines':
                return ($Line->fk_product)?self::Objects()->Encode( "Product" , $Line->fk_product):Null;
                
            //====================================================================//
            // Order Line Quantity
            case 'qty@lines':
                return (int) $Line->qty;
                
            //====================================================================//
            // Order Line Discount Percentile
            case "remise_percent@lines":
                return  (double) $Line->remise_percent;
                
            //====================================================================//
            // Order Line Quantity
            case 'price@lines':
                $Price  =   (double) $Line->subprice;
                $Vat    =   (double) $Line->tva_tx;
                return  self::Prices()->Encode( $Price, $Vat, Null, $conf->global->MAIN_MONNAIE);
                
            default:
                return Null;
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
    private function setItemsFields($FieldName,$Data) 
    {
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "lines" ) {
            return True;
        }
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $ItemData) {
            $this->ItemUpdate = False;
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
            $this->deleteItem( $LineItem ); 
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
    private function setItem($ItemData) {
        
        //====================================================================//
        // New Line ? => Create One
        if ( !$this->CurrentItem ) {
            //====================================================================//
            // Create New Line Item
            $this->CurrentItem =  $this->createItem();
            if ( !$this->CurrentItem ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create Line Item. ");
            } 
        }
        //====================================================================//
        // Update Line Description
        $this->setItemSimpleData($ItemData,"desc");
        //====================================================================//
        // Update Line Label
        $this->setItemSimpleData($ItemData,"label");
        //====================================================================//
        // Update Quantity
        $this->setItemSimpleData($ItemData,"qty");
        //====================================================================//
        // Update Discount
        $this->setItemSimpleData($ItemData,"remise_percent");
        //====================================================================//
        // Update Sub-Price
        $this->setItemPrice($ItemData);
        //====================================================================//
        // Update Product Link
        $this->setItemProductLink($ItemData);
        //====================================================================//
        // Update Line Totals
        $this->updateItemTotals();
        //====================================================================//
        // Commit Line Update
        if ( !$this->ItemUpdate ) {
            return;
        }                
        if ( $this->CurrentItem->update() <= 0) {
            $this->CatchDolibarrErrors($this->CurrentItem);
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Line Item. ");
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
    private function setItemSimpleData($ItemData,$FieldName) 
    {
        if ( !array_key_exists($FieldName, $ItemData) ) {
            return;
        }
        if ($this->CurrentItem->$FieldName !== $ItemData[$FieldName]) {
            $this->CurrentItem->$FieldName = $ItemData[$FieldName];
            $this->ItemUpdate = TRUE;
        }   
        return;
    }   
    
    /**
     *  @abstract     Write Given Price to Line Item
     * 
     *  @param        array     $ItemData       Input Item Data Array
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function setItemPrice($ItemData) 
    {
        if ( !array_key_exists("price", $ItemData) ) {
            return;
        }
        //====================================================================//
        // Update Unit & Sub Prices
        if ( abs($this->CurrentItem->subprice - $ItemData["price"]["ht"]) > 1E-6 ) {
            $this->CurrentItem->subprice    = $ItemData["price"]["ht"];
            $this->CurrentItem->price       = $ItemData["price"]["ht"];
            $this->ItemUpdate      = TRUE;
        }
        //====================================================================//
        // Update VAT Rate
        if ( abs($this->CurrentItem->tva_tx - $ItemData["price"]["vat"]) > 1E-6 ) {
            $this->CurrentItem->tva_tx      = $ItemData["price"]["vat"];
            $this->ItemUpdate      = TRUE;
        }
        //====================================================================//
        // Prices Safety Check
        if ( empty($this->CurrentItem->subprice) ) {
            $this->CurrentItem->subprice = 0;
        }
        if ( empty($this->CurrentItem->price) ) {
            $this->CurrentItem->price = 0;
        }
        return;
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
   
        if ( !array_key_exists("fk_product", $ItemData) ) {
            return;
        }
        //====================================================================//
        // Update Product Link
        $ProductId = self::Objects()->Id($ItemData["fk_product"]);
        if ( $this->CurrentItem->fk_product == $ProductId )  {
            return;
        }            
        if ( empty($ProductId) ) {
            $ProductId  =   0;
        } 
        $this->CurrentItem->setValueFrom("fk_product",$ProductId, Null, Null, Null, Null, "none");
        $this->CatchDolibarrErrors($this->CurrentItem);   
    } 
    
    private function updateItemTotals() 
    {
        global $mysoc;        
        
        if ( !$this->ItemUpdate ) {
            return;
        }
        
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        // Calcul du total TTC et de la TVA pour la ligne a partir de
        // qty, pu, remise_percent et txtva
        // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
        // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
        $localtaxes_type    =   getLocalTaxesFromRate($this->OrderLine->tva_tx,0,$this->OrderLine->socid, $mysoc);

        $tabprice   =   calcul_price_total(
                $this->CurrentItem->qty, $this->CurrentItem->subprice, 
                $this->CurrentItem->remise_percent, $this->CurrentItem->tva_tx, 
                -1,-1,
                0, "HT", 
                $this->CurrentItem->info_bits, $this->CurrentItem->type, 
                '', $localtaxes_type);

        $this->CurrentItem->total_ht            = $tabprice[0];
        $this->CurrentItem->total_tva           = $tabprice[1];
        $this->CurrentItem->total_ttc           = $tabprice[2];
        $this->CurrentItem->total_localtax1     = $tabprice[9];
        $this->CurrentItem->total_localtax2     = $tabprice[10];  
                
    }
}
