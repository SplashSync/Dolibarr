<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Invoice;

use Splash\Client\Splash;

/**
 * Invoices Dolibarr Trigger trait
 */
trait TriggersTrait
{
    /**
     * Prepare Object Commit for Order
     *
     * @param string $action Code de l'evenement
     * @param object $object Objet concerne
     *
     * @return bool Commit is required
     */
    protected function doInvoiceCommit($action, $object)
    {
        global $db;
        
        //====================================================================//
        // Check if Commit is Requierd
        if (!$this->isInvoiceCommitRequired($action)) {
            return false;
        }

        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();

        //====================================================================//
        // Store Global Action Parameters
        $this->setInvoiceObjectId($object);
        $this->setInvoiceParameters($action);
        
        if (empty($this->Id)) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if Commit is Requiered
     *
     * @param string $action Code de l'evenement
     *
     * @return bool
     */
    private function isInvoiceCommitRequired($action)
    {
        return in_array($action, array(
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
        ), true);
    }

    /**
     * Identify Order Id from Given Object
     *
     * @param object $object Objet concerne
     *
     * @return void
     */
    private function setInvoiceObjectId($object)
    {
        //====================================================================//
        // Identify Invoice Id
        if (is_a($object, "FactureLigne")) {
            if ($object->fk_facture) {
                $this->Id        = $object->fk_facture;
            } else {
                $this->Id        = $object->oldline->fk_facture;
            }
        } elseif (is_a($object, "Paiement")) {
            //====================================================================//
            // Read Paiement Object Invoices Amounts
            $amounts = Splash::object("Invoice")->getPaiementAmounts($object->id);
            //====================================================================//
            // Create Impacted Invoices Ids Array
            $this->Id        = array_keys($amounts);
        } else {
            $this->Id        = $object->id;
        }
    }
    
    /**
     * Prepare Object Commit for Product
     *
     * @param string $action Code de l'evenement
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setInvoiceParameters($action)
    {
        $this->Type      = "Invoice";
        switch ($action) {
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
                $this->Action       = (Splash::object("Invoice")->isLocked() ?   SPL_A_CREATE : SPL_A_UPDATE);
                $this->Comment      = "Invoice Updated on Dolibarr";

                break;
            case 'BILL_DELETE':
                $this->Action       = SPL_A_DELETE;
                $this->Comment      = "Invoice Deleted on Dolibarr";

                break;
        }
    }
}
