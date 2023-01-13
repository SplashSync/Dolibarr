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

/**
 * Dolibarr Customer Orders/Invoices Address Fields
 */
trait ContactsTrait
{
    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildContactsFields(): void
    {
        global $langs;

        //====================================================================//
        // Billing Address
        $this->fieldsFactory()->create((string) self::objects()->encode("Address", SPL_T_ID))
            ->identifier("BILLING")
            ->name($langs->trans("TypeContact_commande_external_BILLING"))
            ->microData("http://schema.org/Order", "billingAddress")
        ;
        //====================================================================//
        // Shipping Address
        $this->fieldsFactory()->create((string) self::objects()->encode("Address", SPL_T_ID))
            ->identifier("SHIPPING")
            ->name($langs->trans("TypeContact_commande_external_SHIPPING"))
            ->microData("http://schema.org/Order", "orderDelivery")
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
    protected function getContactsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'SHIPPING':
            case 'BILLING':
                $contactsArray = $this->object->liste_contact(-1, 'external', 1, $fieldName);
                if (is_array($contactsArray) && !empty($contactsArray)) {
                    /** @var int[] $contactsArray  */
                    $this->out[$fieldName] = self::objects()
                        ->encode("Address", (string) array_shift($contactsArray))
                    ;
                } else {
                    $this->out[$fieldName] = null;
                }

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
     * @return void
     */
    protected function setContactsFields(string $fieldName, ?string $fieldData)
    {
        if (!in_array($fieldName, array('SHIPPING', 'BILLING'), true)) {
            return;
        }
        unset($this->in[$fieldName]);

        //====================================================================//
        // Load Current Contact
        $contactsArray = $this->object->liste_contact(-1, 'external', 0, $fieldName);
        if (is_array($contactsArray) && !empty($contactsArray)) {
            /** @var array[] $contactsArray  */
            $current = array_shift($contactsArray);
        } else {
            $current = null;
        }
        //====================================================================//
        // Compare to Expected
        $expected = self::objects()->id((string) $fieldData);
        if ($current && ($current["id"] == $expected)) {
            return;
        }
        //====================================================================//
        // Delete if Changed
        if ($current && ($current["id"] != $expected)) {
            $this->object->delete_contact($current["rowid"]);
        }
        //====================================================================//
        // If Contact was Deleted
        if (false == $expected) {
            return;
        }
        //====================================================================//
        // Add New Contact
        $this->object->add_contact((int) $expected, $fieldName, 'external');
        $this->needUpdate();
    }
}
