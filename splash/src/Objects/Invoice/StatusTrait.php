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
use Splash\Core\SplashCore as Splash;
use Splash\Local\Local;
use Splash\Local\Objects\Invoice;
use Splash\Models\Objects\Invoice\Status;

/**
 * Dolibarr Customer Invoice Status Field
 */
trait StatusTrait
{
    /**
     * Build Customer Order Status Fields using FieldFactory
     *
     * @return void
     */
    protected function buildStatusFields()
    {
        global $langs;

        //====================================================================//
        // Invoice Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("status")
            ->name($langs->trans("Status"))
            ->group($langs->trans("Status"))
            ->microData("http://schema.org/Invoice", "paymentStatus")
            ->addChoice("PaymentDraft", $langs->trans("BillStatusDraft"))
            ->addChoice("PaymentDue", $langs->trans("BillStatusNotPaid"))
            ->addChoice("PaymentComplete", $langs->trans("BillStatusConverted"))
            ->addChoice("PaymentCanceled", $langs->trans("BillStatusCanceled"))
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
    protected function getStatusFields(string $key, string $fieldName)
    {
        if ('status' != $fieldName) {
            return;
        }

        switch ($this->getInvoiceStatus()) {
            case Facture::STATUS_DRAFT:
                $this->out[$fieldName] = Status::DRAFT;

                break;
            case Facture::STATUS_VALIDATED:
                $this->out[$fieldName] = Status::PAYMENT_DUE;

                break;
            case Facture::STATUS_CLOSED:
                $this->out[$fieldName] = Status::COMPLETE;

                break;
            case Facture::STATUS_ABANDONED:
                $this->out[$fieldName] = Status::CANCELED;

                break;
            default:
                $this->out[$fieldName] = Status::UNKNOWN;

                break;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setStatusFields(string $fieldName, $fieldData)
    {
        global $conf, $langs, $user;

        if ('status' != $fieldName) {
            return true;
        }
        unset($this->in[$fieldName]);

        //====================================================================//
        // Safety Check
        if (empty($this->object->id)) {
            return false;
        }
        //====================================================================//
        // Verify Stock Is Defined if Required
        // Stock is incremented on validate invoice, we must provide warehouse id
        if (!empty($conf->stock->enabled) && 1 == $conf->global->STOCK_CALCULATE_ON_BILL) {
            if (empty($conf->global->SPLASH_STOCK)) {
                return Splash::log()->errTrace($langs->trans("WarehouseSourceNotDefined"));
            }
        }
        $initialStatut = $this->getInvoiceStatus();
        switch ($fieldData) {
            //====================================================================//
            // Status Draft
            //====================================================================//
            case "Unknown":
            case "PaymentDraft":
                //====================================================================//
                // Whatever => Set Draft
                if ((0 != $this->object->status) && (!$this->setStatusDraft())) {
                    return false;
                }
                $this->setInvoiceStatus(Facture::STATUS_DRAFT);

                break;
                //====================================================================//
                // Status Validated
                //====================================================================//
            case "PaymentDue":
            case "PaymentDeclined":
            case "PaymentPastDue":
                //====================================================================//
                // If Already Paid => Set Draft
                // If Already Canceled => Set Draft
                $draftStatuses = array(Facture::STATUS_ABANDONED, Facture::STATUS_CLOSED);
                if (in_array((int) $this->object->status, $draftStatuses, false)) {
                    if (!$this->setStatusDraft()) {
                        return false;
                    }
                }
                //====================================================================//
                // If Not Validated => Set Validated
                if ((Facture::STATUS_VALIDATED != $this->getInvoiceStatus())) {
                    if (1 != $this->object->validate($user, "", $conf->global->SPLASH_STOCK)) {
                        return $this->catchDolibarrErrors();
                    }
                    //====================================================================//
                    // Mark Main Document Download Url as Updated
                    $this->setDownloadUrlsUpdated();
                }
                $this->object->paye = 0;
                $this->setInvoiceStatus(Facture::STATUS_VALIDATED);

                break;
                //====================================================================//
                // Status Paid
                //====================================================================//
            case "PaymentComplete":
                //====================================================================//
                // If Previously Canceled => Locked
                if ((Facture::STATUS_ABANDONED == $this->getInvoiceStatus())) {
                    return Splash::log()->err("You cannot Validate a Canceled Invoice!");
                }
                //====================================================================//
                // If Draft => Set Validated
                if (Facture::STATUS_DRAFT == $this->getInvoiceStatus()) {
                    if (1 != $this->object->validate($user, "", $conf->global->SPLASH_STOCK)) {
                        return $this->catchDolibarrErrors();
                    }
                    //====================================================================//
                    // Mark Main Document Download Url as Updated
                    $this->setDownloadUrlsUpdated();
                }
                //====================================================================//
                // If Validated => Set Paid
                if ((Facture::STATUS_VALIDATED == $this->getInvoiceStatus()) && (1 != $this->setStatusPaid())) {
                    return $this->catchDolibarrErrors();
                }
                $this->object->paye = 1;
                $this->setInvoiceStatus(Facture::STATUS_CLOSED);

                break;
                //====================================================================//
                // Status Canceled
                //====================================================================//
            case "PaymentCanceled":
                //====================================================================//
                // Never cancel an Invoice that already carries money
                if ($this->isCancelForbidden()) {
                    break;
                }
                //====================================================================//
                // Whatever => Set Canceled
                if ((Facture::STATUS_ABANDONED != $this->getInvoiceStatus()) && (!$this->setStatusCancel())) {
                    return $this->catchDolibarrErrors();
                }
                $this->object->paye = 0;
                $this->setInvoiceStatus(Facture::STATUS_ABANDONED);

                break;
        }
        if ($initialStatut != $this->object->status) {
            $this->needUpdate();
        }

        return true;
    }

    /**
     * Update Invoice Status with Version Detection
     * Since Dolibarr V19, Uses status instead of statut
     * Both properties available since V13
     */
    protected function getInvoiceStatus(): int
    {
        return Invoice::getInvoiceStatusStatic($this->object);
    }

    /**
     * Update Invoice Status with Version Detection
     * Since Dolibarr V19, Uses status instead of statut
     * Both properties available since V13
     */
    protected function setInvoiceStatus(int $status): void
    {
        $this->object->status = $status;
        //====================================================================//
        // Manage Deprecation of status property
        if (property_exists($this->object, "statut")) {
            $this->object->statut = $status;
        }
    }

    /**
     * Check if the Invoice must NOT be cancelled by the Sync
     *
     * When the source cancels its order, the connector abandons the invoice
     * whatever its state. If money was already received, that abandon drops
     * a settled sale from the books while the payment stays in the bank, with
     * nothing left to match it.
     *
     * Seen in production (Dolibarr 24, Splash 2.23.4, WooCommerce): a B2B
     * customer cancelled from his shop account an order that had been
     * delivered in April and paid by bank transfer in May. The shop still
     * showed it as awaiting payment, so it offered a Cancel button. The paid
     * invoice was abandoned by the sync. It was the third time for that
     * customer.
     *
     * Undoing money that was received goes through a credit note, decided by
     * a human. So a settled invoice, or one that carries at least one
     * payment, keeps its status: the request is ignored and logged.
     * Draft and validated invoices without any payment are cancelled exactly
     * as before: an unpaid order cancelled before shipping is a real cancel.
     *
     * @return bool True if the cancel request must be ignored
     */
    private function isCancelForbidden(): bool
    {
        global $db;

        $status = $this->getInvoiceStatus();
        if (Facture::STATUS_ABANDONED == $status) {
            return false;
        }
        $reason = null;
        if (Facture::STATUS_CLOSED == $status) {
            $reason = "the invoice is already settled";
        } else {
            //====================================================================//
            // Supplier invoices keep their payment links in another table.
            $isSupplier = is_a($this, Local::CLASS_SUPPLIER_INVOICE);
            $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX
                .($isSupplier ? "paiementfourn_facturefourn" : "paiement_facture")
                ." WHERE ".($isSupplier ? "fk_facturefourn" : "fk_facture")." = ".((int) $this->object->id);
            $res = $db->query($sql);
            $obj = $res ? $db->fetch_object($res) : null;
            if ($obj && ((int) $obj->nb) > 0) {
                $reason = ((int) $obj->nb)." real payment(s) recorded";
            }
        }
        if (null === $reason) {
            return false;
        }
        dol_syslog(
            "splash: invoice ".$this->object->ref." kept, ".$reason
            .", cancel request from the source ignored",
            LOG_WARNING
        );
        Splash::log()->war(sprintf(
            "Invoice %s was not cancelled: %s. Use a credit note to undo it.",
            $this->object->ref ?? "?",
            $reason
        ));

        return true;
    }

    /**
     * Set Invoice State as Draft
     *
     * @return bool
     */
    private function setStatusDraft(): bool
    {
        global $conf, $user;

        if (method_exists($this->object, "setDraft")
            && (1 != $this->object->setDraft($user, $conf->global->SPLASH_STOCK))) {
            return $this->catchDolibarrErrors();
        }
        if (method_exists($this->object, "set_draft")
                && (1 != $this->object->set_draft($user, $conf->global->SPLASH_STOCK))) {
            return $this->catchDolibarrErrors();
        }

        return true;
    }

    /**
     * Set Invoice State as Draft
     *
     * @return bool
     */
    private function setStatusPaid(): bool
    {
        global $user;

        if (method_exists($this->object, "setPaid")
            && (1 != $this->object->setPaid($user))) {
            return $this->catchDolibarrErrors();
        }
        if (method_exists($this->object, "set_paid")
            && (1 != $this->object->set_paid($user))) {
            return $this->catchDolibarrErrors();
        }

        return true;
    }

    /**
     * Set Invoice State as Cancelled
     *
     * @return bool
     */
    private function setStatusCancel(): bool
    {
        global $user;

        if (method_exists($this->object, "setCanceled")) {
            if (1 != $this->object->setCanceled($user)) {
                return $this->catchDolibarrErrors();
            }

            return true;
        }
        if (method_exists($this->object, "set_canceled")) {
            if (1 != $this->object->set_canceled($user)) {
                return $this->catchDolibarrErrors();
            }

            return true;
        }

        return false;
    }
}
