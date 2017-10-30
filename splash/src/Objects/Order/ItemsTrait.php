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

namespace Splash\Local\Objects\Order;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Customer Orders Items Fields
 */
trait ItemsTrait {

    //====================================================================//
    // General Class Variables	
    //====================================================================//

    private     $orderlineupdate = False;
    
   
    /**
     *  @abstract     Create a New Line Item
     * 
     *  @return         OrderLine
     */
    protected function createItem() 
    {
        global $db;
        
        $Item   = new  \OrderLine($db);
        
        //====================================================================//
        // Pre-Setup of Item
        $Item->fk_commande = $this->Object->id;
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
            
        if ( $Item->insert() <= 0) { 
            $this->CatchDolibarrErrors($Item);
            return Null;
        }
                
        return $Item;
    }
    
    /**
     *  @abstract     Delete a Line Item
     * 
     *  @param        OrderLine     $OrderLine  Order OrderLine Item
     * 
     *  @return         bool
     */
    protected function deleteItem( $OrderLine ) 
    {
        global $user;
        //====================================================================//
        // Force Order Status To Draft
        $this->Object->statut         = 0;
        $this->Object->brouillon      = 1;
        //====================================================================//
        // Prepare Args
        $Arg1 = ( Splash::Local()->DolVersionCmp("5.0.0") > 0 ) ? $user : $OrderLine->id;
        $Arg2 = ( Splash::Local()->DolVersionCmp("5.0.0") > 0 ) ? $OrderLine->id : Null;
        //====================================================================//
        // Perform Line Delete
        if ( $this->Object->deleteline($Arg1, $Arg2) <= 0) { 
            return $this->CatchDolibarrErrors();
        }
        return True;
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function setOrderLineData($OrderLineData,$FieldName) 
    {
        if ( !array_key_exists($FieldName, $OrderLineData) ) {
            return;
        }
        if ($this->OrderLine->$FieldName !== $OrderLineData[$FieldName]) {
            $this->OrderLine->$FieldName = $OrderLineData[$FieldName];
            $this->orderlineupdate = TRUE;
        }   
        return;
    }   
    
}
