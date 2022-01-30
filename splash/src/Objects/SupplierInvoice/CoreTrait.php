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

namespace Splash\Local\Objects\SupplierInvoice;

use DateTime;
use Exception;

/**
 * Dolibarr Supplier Invoice Fields (Required)
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCoreFields(): void
    {
        global $langs;

        //====================================================================//
        // Order Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier("date")
            ->name($langs->trans("InvoiceDateCreation"))
            ->microData("http://schema.org/Order", "orderDate")
            ->isRequired()
            ->isListed()
        ;
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ref")
            ->name($langs->trans("InvoiceRef"))
            ->microData("http://schema.org/Invoice", "name")
            ->isReadOnly()
            ->isListed()
        ;
        //====================================================================//
        // Customer Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ref_supplier")
            ->name($langs->trans("RefSupplier"))
            ->microData("http://schema.org/Invoice", "confirmationNumber")
            ->isRequired()
            ->isListed()
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
    protected function getCoreFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
            case 'ref_supplier':
                $this->getSimple($fieldName);

                break;
            //====================================================================//
            // Order Official Date
            case 'date':
                $date = $this->object->date;
                $this->out[$fieldName] = !empty($date)?dol_print_date($date, '%Y-%m-%d'):null;

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
     * @throws Exception
     *
     * @return void
     */
    protected function setCoreFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
            case 'ref_supplier':
                $this->setSimple($fieldName, $fieldData);

                break;
            //====================================================================//
            // Order Official Date
            case 'date':
                $dateTime = new DateTime($fieldData);
                $this->setSimple('date', $dateTime->getTimestamp());

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
