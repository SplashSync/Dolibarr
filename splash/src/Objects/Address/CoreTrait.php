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

namespace   Splash\Local\Objects\Address;

use Splash\Local\Local;

/**
 * Dolibarr Contacts Address Fields (Required)
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
        // Firstname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("firstname")
            ->name($langs->trans("Firstname"))
            ->microData("http://schema.org/Person", "familyName")
            ->isListed()
            ->isLogged()
            ->isRequired()
        ;
        //====================================================================//
        // Lastname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("lastname")
            ->name($langs->trans("Lastname"))
            ->microData("http://schema.org/Person", "givenName")
            ->isLogged()
            ->isListed()
        ;
        //====================================================================//
        // Job Title
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("poste")
            ->name($langs->trans("PostOrFunction"))
            ->description("The job title of the person (for example, Financial Manager).")
            ->microData("http://schema.org/Person", "jobTitle")
            ->isLogged()
        ;
        //====================================================================//
        // Customer
        $this->fieldsFactory()->create((string) self::objects()->encode("ThirdParty", SPL_T_ID))
            ->identifier("socid")
            ->name($langs->trans("Company"))
            ->microData("http://schema.org/Organization", "ID")
        ;
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ref_ext")
            ->name($langs->trans("CustomerCode"))
            ->description($langs->trans("CustomerCodeDesc"))
            ->microData("http://schema.org/PostalAddress", "name")
            ->isListed()
            ->isLogged()
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
            // Contact ThirdParty Id
            case 'socid':
                $this->out[$fieldName] = self::objects()
                    ->encode("ThirdParty", (string) $this->object->socid)
                ;

                break;
                //====================================================================//
                // Direct Readings
            case 'name':
            case 'firstname':
            case 'lastname':
            case 'poste':
            case 'ref_ext':
                $this->getSimple($fieldName);

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
    protected function setCoreFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Contact Company Id
            case 'socid':
                $this->setSimple($fieldName, self::objects()->id((string) $fieldData));

                break;
                //====================================================================//
                // Direct Writings
            case 'name':
            case 'firstname':
            case 'lastname':
            case 'poste':
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'ref_ext':
                //====================================================================//
                // Update of ref_ext added to Update Func in V14
                if ((Local::dolVersionCmp("14.0.0") < 0) && ($this->object->{$fieldName} != $fieldData)) {
                    $this->setDatabaseField("ref_ext", $fieldData);
                }
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
