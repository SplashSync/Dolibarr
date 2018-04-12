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

namespace Splash\Local\Objects\Invoice;

use Splash\Client\Splash;

/**
 * @abstract    Invoices Dolibarr Trigger trait
 */
trait TriggersTrait {
    
    /**
     *      @abstract      Prepare Object Commit for Order
     * 
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     * 
     *      @return bool        Commit is required
     */
    function doInvoiceCommit($Action, $Object)
    {    
        global $db;
        
        //====================================================================//
        // Check if Commit is Requierd
        if (!$this->isInvoiceCommitRequired($Action)) {
            return False;
        }        

        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();

        //====================================================================//
        // Store Global Action Parameters 
        $this->setInvoiceObjectId($Object);
        $this->setInvoiceParameters($Action);        
        
        if ( empty($this->Id) ) {
            return False;
        } 
        
        return True;
    } 

    /**
     * @abstract      Check if Commit is Requiered
     * 
     * @param  string      $Action      Code de l'evenement
     * 
     * @return bool
     */
    private function isInvoiceCommitRequired($Action)
    {                
        return in_array($Action,array(
            // Invoice Actions
            'BILL_CREATE',
            'BILL_CLONE',
            'BILL_MODIFY', 
            'BILL_VALIDATE',
            'BILL_UNVALIDATE',
            'BILL_CANCEL', 
            'BILL_DELETE',
            'BILL_PAYED',
            'BILL_UNPAYED', 
            // Invoice Lines Actions
            'LINEBILL_INSERT',
            'LINEBILL_UPDATE',
            'LINEBILL_DELETE', 
// Not Managed up to now. User Select Default Bank for payments created by the module
//            &&  ($Action !== 'PAYMENT_ADD_TO_BANK')             
            // Invoice Payments Actions
            'PAYMENT_CUSTOMER_CREATE',
            'PAYMENT_CUSTOMER_DELETE',
            'PAYMENT_DELETE', 
        ));
    }     

    
    /**
     *      @abstract      Identify Order Id from Given Object
     * 
     *      @param  object      $Object      Objet concerne
     * 
     *      @return void
     */
    private function setInvoiceObjectId($Object)
    {    
        //====================================================================//
        // Identify Invoice Id         
        if (is_a($Object, "FactureLigne")) {
            if ($Object->fk_facture) {
                $this->Id        = $Object->fk_facture;
            } else {
                $this->Id        = $Object->oldline->fk_facture;
            }
        } elseif (is_a($Object, "Paiement")) {
            //====================================================================//
            // Read Paiement Object Invoices Amounts 
            $Amounts = Splash::Object("Invoice")->getPaiementAmounts($Object->id);
            //====================================================================//
            // Create Impacted Invoices Ids Array  
            $this->Id        = array_keys($Amounts);
        } else {
            $this->Id        = $Object->id;
        } 
    }  
    
    /**
     *      @abstract      Prepare Object Commit for Product
     * 
     *      @param  string      $Action      Code de l'evenement
     * 
     *      @return void
     */
    private function setInvoiceParameters($Action)
    {    
        $this->Type      = "Invoice";
        switch($Action) {
            case 'BILL_CREATE':
                $this->Action       = SPL_A_CREATE;
                $this->Comment      = "Invoice Created on Dolibarr";
                break;
            
            case 'BILL_MODIFY':
            case 'BILL_CLONE':
            case 'BILL_VALIDATE':
            case 'BILL_UNVALIDATE':
            case 'BILL_CANCEL':
            case 'BILL_PAYED':
            case 'BILL_UNPAYED':
            case 'PAYMENT_CUSTOMER_CREATE':
            case 'PAYMENT_CUSTOMER_DELETE':
            case 'PAYMENT_DELETE':
            case 'LINEBILL_INSERT':
            case 'LINEBILL_UPDATE':
            case 'LINEBILL_DELETE':
                $this->Action       = (Splash::Object("Invoice")->isLocked() ?   SPL_A_CREATE : SPL_A_UPDATE);
                $this->Comment      = "Invoice Updated on Dolibarr";
                break;

            case 'BILL_DELETE':
                $this->Action       = SPL_A_DELETE;
                $this->Comment      = "Invoice Deleted on Dolibarr";
                break;
        }
    }      
}
