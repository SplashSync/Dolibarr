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

use DateTime;
use Exception;

/**
 * Dolibarr Customer Orders Fields (Required)
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
            ->name($langs->trans("RefOrder"))
            ->microData("http://schema.org/Order", "name")
            ->isReadOnly()
            ->isListed()
        ;
        //====================================================================//
        // Customer Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ref_client")
            ->name($langs->trans("RefCustomerOrder"))
            ->microData("http://schema.org/Order", "orderNumber")
            ->isListed()
        ;
        //====================================================================//
        // External Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ref_ext")
            ->name($langs->trans("RefExt"))
            ->microData("http://schema.org/Order", "alternateName")
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
     * @throws Exception
     *
     * @return void
     */
    protected function setCoreFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
            case 'ref_client':
            case 'ref_ext':
                $this->setSimple($fieldName, $fieldData);

                break;
                //====================================================================//
                // Order Official Date
            case 'date':
                if ($fieldData) {
                    $dateTime = new DateTime($fieldData);
                    $this->setSimple('date', $dateTime->getTimestamp());
                    $this->setSimple('date_commande', $dateTime->getTimestamp());
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
