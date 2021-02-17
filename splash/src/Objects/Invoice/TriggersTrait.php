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

namespace Splash\Local\Objects\Invoice;

use Facture;
use FactureLigne;
use Paiement;
use Splash\Client\Splash;
use Splash\Local\Objects\Invoice;

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
        //====================================================================//
        // Check if Commit is Required
        if (!$this->isInvoiceCommitRequired($action)) {
            return false;
        }
        //====================================================================//
        // Store Global Action Parameters
        $this->setInvoiceObjectId($object);
        $this->setInvoiceObjectType($object);
        //====================================================================//
        // Safety Check => ObjectType is Active
        if (!in_array($this->objectType, Splash::objects(), true)) {
            return false;
        }
        $this->setInvoiceParameters($action);

        if (empty($this->objectId)) {
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
        // Identify Invoice Id from Invoice Line
        if ($object instanceof FactureLigne) {
            $this->objectId = !empty($object->fk_facture)
                ? (string) $object->fk_facture
                : (string) $object->oldline->fk_facture;

            return;
        }

        //====================================================================//
        // Identify Invoice Id from Payment Line
        if ($object instanceof Paiement) {
            //====================================================================//
            // Read Paiement Object Invoices Amounts
            $amounts = Invoice::getPaiementAmounts($object->id);
            //====================================================================//
            // Create Impacted Invoices Ids Array
            $this->objectId = array_keys($amounts);

            return;
        }

        //====================================================================//
        // Invoice Given
        if ($object instanceof Facture) {
            $this->objectId = (string) $object->id;
        }
    }

    /**
     * Identify Splash Object type from Given Object
     *
     * @param object $object Objet concerne
     *
     * @return void
     */
    private function setInvoiceObjectType($object)
    {
        $objectType = Facture::TYPE_STANDARD;
        //====================================================================//
        // Invoice Given
        if ($object instanceof Facture) {
            $objectType = $object->type;
        }
        //====================================================================//
        // Identify Invoice Type from Invoice Line
        if (($object instanceof FactureLigne) && is_scalar($this->objectId)) {
            $objectType = $object->getValueFrom("facture", (int) $this->objectId, "type");
        }
        //====================================================================//
        // Identify Invoice Type from Payment Line
        if (($object instanceof Paiement) && !empty($this->objectId)) {
            $objectType = $object->getValueFrom("facture", $this->objectId[0], "type");
        }
        //====================================================================//
        // Standard Invoice or Credit Note
        $this->objectType = (Facture::TYPE_CREDIT_NOTE == $objectType) ? "CreditNote" : "Invoice";
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
        switch ($action) {
            case 'BILL_CREATE':
                $this->action = SPL_A_CREATE;
                $this->comment = "Invoice Created on Dolibarr";

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
                $this->action = (Splash::object((string) $this->objectType)->isLocked() ? SPL_A_CREATE : SPL_A_UPDATE);
                $this->comment = "Invoice Updated on Dolibarr";

                break;
            case 'BILL_DELETE':
                $this->action = SPL_A_DELETE;
                $this->comment = "Invoice Deleted on Dolibarr";

                break;
        }
    }
}
