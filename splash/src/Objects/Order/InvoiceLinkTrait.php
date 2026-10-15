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

namespace Splash\Local\Objects\Order;

use Facture;
use Splash\Local\Objects\Invoice;
use Splash\Models\Helpers\FilesHelper;

/**
 * Access to Order First Valid Invoice Number & Pdf
 *
 * An Order may be linked to several Invoices: only the first VALID one is
 * exposed. Draft & Abandoned Invoices are ignored.
 */
trait InvoiceLinkTrait
{
    /**
     * First Valid Invoice Linked to this Order
     *
     * @var null|Facture
     */
    private ?Facture $orderInvoice = null;

    /**
     * Order the Invoice Detection was Done for
     *
     * Object Classes are Singletons: Cache must be Dropped when another Order is Loaded.
     *
     * @var null|object
     */
    private ?object $orderInvoiceOwner = null;

    /**
     * Build Fields using FieldFactory
     */
    protected function buildInvoiceLinkFields(): void
    {
        global $langs;

        //====================================================================//
        // Invoice Number
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("invoiceNumber")
            ->name($langs->trans("InvoiceRef"))
            ->description("Number of the order's first invoice")
            ->microData("http://schema.org/Order", "invoiceNumber")
            ->isReadOnly()
        ;
        //====================================================================//
        // Invoice Pdf
        $this->fieldsFactory()->create(SPL_T_STREAM)
            ->identifier("invoicePdf")
            ->name($langs->trans("InvoiceRef")." (PDF)")
            ->description("PDF of the order's first invoice")
            ->microData("http://schema.org/Order", "invoicePdf")
            ->isReadOnly()
        ;
    }

    /**
     * Read requested Field
     */
    protected function getInvoiceLinkFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'invoiceNumber':
                $invoice = $this->getFirstValidInvoice();
                $this->out[$fieldName] = $invoice ? (string) $invoice->ref : null;

                break;
            case 'invoicePdf':
                $this->out[$fieldName] = $this->getInvoicePdfStream();

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Get First Valid Invoice Linked to this Order
     *
     * @return null|Facture
     */
    private function getFirstValidInvoice(): ?Facture
    {
        global $db;

        //====================================================================//
        // Detection Already Done for this Order
        if ($this->orderInvoiceOwner === $this->object) {
            return $this->orderInvoice;
        }
        $this->orderInvoiceOwner = $this->object;
        $this->orderInvoice = null;
        //====================================================================//
        // Ensure Invoice Class is Loaded
        include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
        //====================================================================//
        // Collect Linked Invoices Ids, without loading Objects
        $this->object->fetchObjectLinked(
            $this->object->id,
            'commande',
            null,
            'facture',
            'OR',
            1,
            'sourcetype',
            0
        );
        /** @var array<int|string> $invoiceIds */
        $invoiceIds = $this->object->linkedObjectsIds['facture'] ?? array();
        if (empty($invoiceIds)) {
            return null;
        }
        //====================================================================//
        // Oldest Invoice First
        sort($invoiceIds);
        //====================================================================//
        // Return First Validated Invoice
        foreach ($invoiceIds as $invoiceId) {
            $invoice = new Facture($db);
            if ($invoice->fetch((int) $invoiceId) <= 0) {
                continue;
            }
            if (!self::isValidInvoice($invoice)) {
                continue;
            }

            return $this->orderInvoice = $invoice;
        }

        return null;
    }

    /**
     * Check if an Invoice is Validated, i.e. neither Draft nor Abandoned
     *
     * @param Facture $invoice
     *
     * @return bool
     */
    private static function isValidInvoice(Facture $invoice): bool
    {
        return in_array(
            Invoice::getInvoiceStatusStatic($invoice),
            array(Facture::STATUS_VALIDATED, Facture::STATUS_CLOSED),
            true
        );
    }

    /**
     * Get Streamed Pdf of the Order First Valid Invoice
     *
     * @return null|array
     */
    private function getInvoicePdfStream(): ?array
    {
        global $conf;

        $ttl = 3;
        $invoice = $this->getFirstValidInvoice();
        if (!$invoice) {
            return null;
        }
        //====================================================================//
        // Build Invoice Documents Directory
        $entity = $invoice->entity ?: $conf->entity;
        $baseDir = $conf->facture->multidir_output[$entity] ?? ($conf->facture->dir_output ?? null);
        if (empty($baseDir) || !is_string($baseDir)) {
            return null;
        }
        $safeRef = dol_sanitizeFileName((string) $invoice->ref);
        $fileDir = $baseDir.'/'.$safeRef.'/';
        //====================================================================//
        // Only the Invoice Pdf itself is exposed:
        // any other file may have been attached to the Dolibarr document
        $fileName = $safeRef.'.pdf';
        if (!is_file($fileDir.$fileName)) {
            return null;
        }

        return FilesHelper::stream((string) $invoice->ref, $fileName, $fileDir, $ttl) ?: null;
    }
}
