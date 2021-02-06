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

namespace Splash\Local\Objects\Order;

use DateTime;

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
    protected function buildCoreFields()
    {
        global $langs;

        //====================================================================//
        // Order Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->Identifier("date")
            ->Name($langs->trans("OrderDate"))
            ->MicroData("http://schema.org/Order", "orderDate")
            ->isRequired()
            ->isListed();

        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("ref")
            ->Name($langs->trans("RefOrder"))
            ->MicroData("http://schema.org/Order", "name")
            ->isReadOnly()
            ->isListed();

        //====================================================================//
        // Customer Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("ref_client")
            ->Name($langs->trans("RefCustomerOrder"))
            ->isListed()
            ->MicroData("http://schema.org/Order", "orderNumber");

        //====================================================================//
        // Internal Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("ref_int")
            ->Name($langs->trans("InternalRef"))
            ->MicroData("http://schema.org/Order", "description");

        //====================================================================//
        // External Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("ref_ext")
            ->Name($langs->trans("RefExt"))
            ->isListed()
            ->MicroData("http://schema.org/Order", "alternateName");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCoreFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
            case 'ref_client':
            case 'ref_int':
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
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setCoreFields($fieldName, $fieldData)
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
            case 'ref_int':
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
                $dateTime = new DateTime($fieldData);
                $this->setSimple('date', $dateTime->getTimestamp());
                $this->setSimple('date_commande', $dateTime->getTimestamp());

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
