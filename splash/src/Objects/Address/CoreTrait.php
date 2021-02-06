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

namespace   Splash\Local\Objects\Address;

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
    protected function buildCoreFields()
    {
        global $langs;

        //====================================================================//
        // Firstname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("firstname")
            ->Name($langs->trans("Firstname"))
            ->MicroData("http://schema.org/Person", "familyName")
            ->isListed()
            ->isLogged()
            ->isRequired();

        //====================================================================//
        // Lastname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("lastname")
            ->Name($langs->trans("Lastname"))
            ->MicroData("http://schema.org/Person", "givenName")
            ->isLogged()
            ->isListed();

        //====================================================================//
        // Job Title
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("poste")
            ->Name($langs->trans("PostOrFunction"))
            ->description("The job title of the person (for example, Financial Manager).")
            ->MicroData("http://schema.org/Person", "jobTitle")
            ->isLogged();

        //====================================================================//
        // Customer
        $this->fieldsFactory()->create((string) self::objects()->Encode("ThirdParty", SPL_T_ID))
            ->Identifier("socid")
            ->Name($langs->trans("Company"))
            ->MicroData("http://schema.org/Organization", "ID");

        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("ref_ext")
            ->Name($langs->trans("CustomerCode"))
            ->Description($langs->trans("CustomerCodeDesc"))
            ->isListed()
            ->isLogged()
            ->MicroData("http://schema.org/PostalAddress", "name");
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
            // Contact ThirdParty Id
            case 'socid':
                $this->out[$fieldName] = self::objects()->Encode("ThirdParty", $this->object->socid);

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
            // Contact Company Id
            case 'socid':
                $this->setSimple($fieldName, self::objects()->Id($fieldData));

                break;
            //====================================================================//
            // Direct Writtings
            case 'name':
            case 'firstname':
            case 'lastname':
            case 'poste':
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'ref_ext':
                if ($this->object->{$fieldName} != $fieldData) {
                    $this->setDatabaseField("ref_ext", $fieldData);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
