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

namespace Splash\Local\Core;

use Splash\Local\Objects\CreditNote;
use Splash\Local\Objects\Invoice;
use Splash\Local\Objects\Order;

/**
 * Direct Access to Dolibarr Documents Public Urls
 */
trait DownloadUrlsTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildDownloadUrlsFields()
    {
        global $langs;

        //====================================================================//
        // Check if Public Download Url is Allowed for Object
        if (!$this->hasDownloadUrl()) {
            return;
        }
        //====================================================================//
        // Public Download Url
        $this->fieldsFactory()->create(SPL_T_URL)
            ->identifier("main_doc_link")
            ->name("Download Link")
            ->description($langs->trans("DirectDownloadLink"))
            ->microData("https://schema.org/DownloadAction", "url")
            ->isReadOnly()
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
    protected function getDownloadUrlsFields(string $key, string $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'main_doc_link':
                $url = $this->object->getLastMainDocLink($this->object->element);
                $this->out[$fieldName] = $url ?: null;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Mark Main Document Download Url as Updated
     * - Force On Save Commit
     *
     * @return void
     */
    protected function setDownloadUrlsUpdated(): void
    {
        //====================================================================//
        // Check if Public Download Url is Allowed for Object
        if (!$this->hasDownloadUrl()) {
            return ;
        }
        //====================================================================//
        // Release Lock for this object
        $this->unLock((string) $this->object->id ?: "new");
    }

    /**
     * Check if Object Share Main Download Url
     *
     * @return bool
     */
    private function hasDownloadUrl(): bool
    {
        global $conf;

        //====================================================================//
        // Allowed for Orders
        if (($this instanceof Order) && !empty($conf->global->ORDER_ALLOW_EXTERNAL_DOWNLOAD)) {
            return true;
        }
        //====================================================================//
        // Allowed for Invoices
        if (($this instanceof Invoice) && !empty($conf->global->INVOICE_ALLOW_EXTERNAL_DOWNLOAD)) {
            return true;
        }
        //====================================================================//
        // Allowed for CreditNote
        if (($this instanceof CreditNote) && !empty($conf->global->INVOICE_ALLOW_EXTERNAL_DOWNLOAD)) {
            return true;
        }

        return false;
    }
}
