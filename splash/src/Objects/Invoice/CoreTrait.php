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

use DateTime;

/**
 * Dolibarr Customer Invoice Fields (Required)
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
            ->name($langs->trans("OrderDate"))
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
            ->identifier("ref_client")
            ->name($langs->trans("RefCustomer"))
            ->microData("http://schema.org/Invoice", "confirmationNumber")
            ->isListed()
        ;
        //====================================================================//
        // External Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ref_ext")
            ->name($langs->trans("ExternalRef"))
            ->microData("http://schema.org/Invoice", "alternateName")
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
            case 'ref_client':
            case 'ref_ext':
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
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function setCoreFields(string $fieldName, ?string $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
            case 'ref_client':
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'ref_ext':
                //====================================================================//
                //  Compare Field Data
                if ($this->object->{$fieldName} != $fieldData) {
                    //====================================================================//
                    //  Update Field Data
                    $this->object->setValueFrom($fieldName, $fieldData);
                    $this->needUpdate();
                }

                break;
                //====================================================================//
                // Order Official Date
            case 'date':
                $dateTime = new DateTime((string) $fieldData);
                $this->setSimple('date', $dateTime->getTimestamp());
                $this->setSimple('date_commande', $dateTime->getTimestamp());

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
