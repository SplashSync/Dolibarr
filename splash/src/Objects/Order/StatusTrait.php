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
 * @abstract    Dolibarr Customer Order Status Field  
 */
trait StatusTrait {

    /**
     *  @abstract     Build Customer Order Status Fields using FieldFactory
     */
    protected function buildStatusFields()   {
        
        global $langs;
        
        //====================================================================//
        // Order Current Status
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("status")
                ->Name($langs->trans("Status"))
                ->Group(html_entity_decode($langs->trans("Status")))
                ->MicroData("http://schema.org/Order","orderStatus")
                ->AddChoice("OrderCanceled",    $langs->trans("StatusOrderCanceled"))
                ->AddChoice("OrderDraft",       $langs->trans("StatusOrderDraftShort"))
                ->AddChoice("OrderInTransit",   $langs->trans("StatusOrderSent"))
                ->AddChoice("OrderProcessing",  $langs->trans("StatusOrderSentShort"))
                ->AddChoice("OrderDelivered",   $langs->trans("StatusOrderProcessed"))
                ->NotTested()
                ;

    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    protected function getStatusFields($Key,$FieldName)
    {
        if ( $FieldName != 'status')  {
            return;
        }
        
        if ($this->Object->statut == -1) {
            $this->Out[$FieldName]  = "OrderCanceled";
        } elseif ($this->Object->statut == 0) {
            $this->Out[$FieldName]  = "OrderDraft";
        } elseif ($this->Object->statut == 1) {
            $this->Out[$FieldName]  = "OrderProcessing";
        } elseif ($this->Object->statut == 2) {
            $this->Out[$FieldName]  = "OrderInTransit";
        } elseif ($this->Object->statut == 3) {
            $this->Out[$FieldName]  = "OrderDelivered";
        } else {
            $this->Out[$FieldName]  = "Unknown";
        }
        
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setStatusFields($FieldName,$Data) 
    {
        global $conf,$langs,$user;
        
        if ( $FieldName != 'status')  {
            return;
        }
        unset($this->In[$FieldName]);

        //====================================================================//
        // Safety Check
        if ( empty($this->Object->id) ) {
            return False;
        }
        //====================================================================//
        // Verify Stock Is Defined if Required
        // If stock is incremented on validate order, we must increment it          
        if ( !empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1) {
            if ( empty($conf->global->SPLASH_STOCK ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, $langs->trans("WarehouseSourceNotDefined"));
            }
        }    
        //====================================================================//
        // Statut Canceled
        //====================================================================//
        // Statut Canceled
        if ( ($Data == "OrderCanceled") && ($this->Object->statut != -1) )    {
            //====================================================================//
            // If Previously Closed => Set Draft
            if ( ( $this->Object->statut == 3 ) && ( $this->Object->set_draft($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
                return $this->CatchDolibarrErrors();
            }         
            //====================================================================//
            // If Previously Draft => Valid
            if ( ( $this->Object->statut == 0 ) && ( $this->Object->valid($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
                return $this->CatchDolibarrErrors();
            }               
            //====================================================================//
            // Set Canceled
            if ( $this->Object->cancel($conf->global->SPLASH_STOCK) != 1 ) {
                    return $this->CatchDolibarrErrors();
            }     
            $this->Object->statut = \Commande::STATUS_CANCELED;
            return True;
        }
        //====================================================================//
        // If Previously Canceled => Re-Validate
        if ( ( $this->Object->statut == -1 ) && ( $this->Object->valid($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
            return $this->CatchDolibarrErrors();
        }         
        //====================================================================//
        // Statut Draft
        if ( $Data == "OrderDraft" )    {
            //====================================================================//
            // If Not Draft (Validated or Closed)            
            if ( ($this->Object->statut != 0) && $this->Object->set_draft($user,$conf->global->SPLASH_STOCK) != 1 ) {
                return $this->CatchDolibarrErrors();
            }        
            $this->Object->statut = \Commande::STATUS_DRAFT;
            return True;
        }        
        //====================================================================//
        // Statut Validated || Closed => Go Valid if Draft
        if ( ( $this->Object->statut == 0 ) && ( $this->Object->valid($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Validated", $langs->trans($this->Object->error) );
        }         
        //====================================================================//
        // Statut Not Closed but Validated Only => ReOpen 
        if ($Data != "OrderDelivered")    {
            //====================================================================//
            // If Previously Closed => Re-Open
            if ( ( $this->Object->statut == 3 ) && ( $this->Object->set_reopen($user) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Re-Open", $langs->trans($this->Object->error) );
            }     
            $this->Object->statut = \Commande::STATUS_VALIDATED;
        }            
        //====================================================================//
        // Statut Closed => Go Closed
        if ( ($Data == "OrderDelivered") && ($this->Object->statut != 3) )    {
            //====================================================================//
            // If Previously Validated => Close
            if ( ( $this->Object->statut == 1 ) && ( $this->Object->cloture($user) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Closed", $langs->trans($this->Object->error) );
            }         
            $this->Object->statut = \Commande::STATUS_CLOSED;
        }
        //====================================================================//
        // Redo Billed flag Update if Impacted by Status Change
        $this->updateBilledFlag();
        
    }    
    
}
