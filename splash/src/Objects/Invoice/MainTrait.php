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

namespace Splash\Local\Objects\Invoice;

use Facture;
use Splash\Local\Local;

/**
 * Dolibarr Customer Orders Fields
 */
trait MainTrait
{
    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMainFields(): void
    {
        global $langs,$conf;

        //====================================================================//
        // Invoice PaymentDueDate Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier(is_a($this, Local::CLASS_SUPPLIER_INVOICE) ? "date_echeance" : "date_lim_reglement")
            ->name($langs->trans("DateMaxPayment"))
            ->microData("http://schema.org/Invoice", "paymentDueDate")
        ;

        //====================================================================//
        // PRICES INFORMATION
        //====================================================================//

        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("total_ht")
            ->name($langs->trans("TotalHT")." (".$conf->global->MAIN_MONNAIE.")")
            ->microData("http://schema.org/Invoice", "totalPaymentDue")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("total_ttc")
            ->name($langs->trans("TotalTTC")." (".$conf->global->MAIN_MONNAIE.")")
            ->microData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
            ->isReadOnly()
        ;

        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//

        //====================================================================//
        // Is Draft
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isDraft")
            ->name($langs->trans("Invoice")." : ".$langs->trans("Draft"))
            ->group($langs->trans("Status"))
            ->microData("http://schema.org/PaymentStatusType", "InvoiceDraft")
            ->association("isDraft", "isCanceled", "isValidated")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Canceled
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isCanceled")
            ->name($langs->trans("Invoice")." : ".$langs->trans("Canceled"))
            ->group($langs->trans("Status"))
            ->microData("http://schema.org/PaymentStatusType", "PaymentDeclined")
            ->association("isDraft", "isCanceled", "isValidated")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isValidated")
            ->name($langs->trans("Invoice")." : ".$langs->trans("Validated"))
            ->group($langs->trans("Status"))
            ->microData("http://schema.org/PaymentStatusType", "PaymentDue")
            ->association("isDraft", "isCanceled", "isValidated")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isPaid")
            ->name($langs->trans("Invoice")." : ".$langs->trans("Paid"))
            ->group($langs->trans("Status"))
            ->microData("http://schema.org/PaymentStatusType", "PaymentComplete")
            ->isNotTested()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Order Delivery Date
            case 'date_lim_reglement':
            case 'date_echeance':
                $date = $this->object->{$fieldName} ?? null;
                $this->out[$fieldName] = !empty($date) ? dol_print_date($date, '%Y-%m-%d') : null;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getTotalsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_ht':
            case 'total_ttc':
            case 'total_vat':
                $this->getSimple($fieldName);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getStateFields(string $key, string $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//

            case 'isDraft':
                $this->out[$fieldName] = (Facture::STATUS_DRAFT == $this->getInvoiceStatus());

                break;
            case 'isCanceled':
                $this->out[$fieldName] = (Facture::STATUS_ABANDONED == $this->getInvoiceStatus());

                break;
            case 'isValidated':
                $this->out[$fieldName] = (Facture::STATUS_VALIDATED == $this->getInvoiceStatus());

                break;
            case 'isPaid':
                $this->out[$fieldName] = (Facture::STATUS_CLOSED == $this->getInvoiceStatus());

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setMainFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Invoice Payment Due Date
            case 'date_lim_reglement':
            case 'date_echeance':
                if (dol_print_date($this->object->{$fieldName} ?? null, 'standard') === $fieldData) {
                    break;
                }
                $this->setSimple($fieldName, $fieldData);

                break;
                //====================================================================//
                // PAYMENT STATUS
                //====================================================================//
            case 'isPaid':
                $this->setPaidFlag($fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param mixed $data Field Data
     *
     * @return bool
     */
    private function setPaidFlag($data): bool
    {
        global $user;

        //====================================================================//
        // If Status Unchanged => Skip Update
        if ($data == $this->object->paye) {
            return true;
        }
        //====================================================================//
        // If Status Is Not Validated | Closed => Cannot Update This Flag
        if (!in_array($this->getInvoiceStatus(), array(Facture::STATUS_VALIDATED, Facture::STATUS_CLOSED), false)) {
            return true;
        }
        //====================================================================//
        // Update This Flag
        if ($data) {
            //====================================================================//
            // Never close an invoice the recorded money does not cover.
            //
            // The source knows whether its order was paid. It does not know how
            // much of THIS invoice has actually been received, because a shop
            // has a single paid/unpaid state where accounting has a running
            // balance: a schedule of cheques, a deposit, a settlement part
            // vouchers part transfer. The shop marks the order paid the day the
            // arrangement is agreed; the invoice is extinguished only when the
            // last movement lands.
            //
            // An invoice closed early is a receivable that disappears from the
            // books while it is still owed. Under the French e-invoicing regime
            // each payment is also reported with its own date, so the missing
            // movements are never declared either.
            //
            // A human may still close a residue by hand in Dolibarr — writing
            // off a few cents is a decision. A connector may not.
            $covered = (float) $this->object->getSommePaiement();
            if (method_exists($this->object, "getSumCreditNotesUsed")) {
                $covered += (float) $this->object->getSumCreditNotesUsed();
            }
            if (method_exists($this->object, "getSumDepositsUsed")) {
                $covered += (float) $this->object->getSumDepositsUsed();
            }
            $invoiceTotal = abs((float) $this->object->total_ttc);
            if (($invoiceTotal > 1E-6) && ((abs($covered) + 1E-6) < $invoiceTotal)) {
                dol_syslog(
                    "splash: invoice ".$this->object->ref." left open, only ".$covered
                    ." of ".$invoiceTotal." is recorded, source opinion ignored",
                    LOG_WARNING
                );

                return true;
            }
            //====================================================================//
            // Set Paid using Dolibarr Function
            if ((Facture::STATUS_VALIDATED == $this->getInvoiceStatus()) && (1 != $this->object->set_paid($user))) {
                return $this->catchDolibarrErrors();
            }
        } else {
            //====================================================================//
            // Set UnPaid using Dolibarr Function
            if ((Facture::STATUS_CLOSED == $this->getInvoiceStatus()) && (1 != $this->object->set_unpaid($user))) {
                return $this->catchDolibarrErrors();
            }
        }
        //====================================================================//
        // Update Current Object not to Override changes with Update
        $this->object->paye = ($data ? 1 : 0);

        return true;
    }
}
