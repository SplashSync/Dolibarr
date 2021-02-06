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
 * Dolibarr Contacts Address Main Fields
 */
trait MainTrait
{
    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMainFields()
    {
        global $conf,$langs;

        $groupName = $langs->trans("CompanyAddress");
        //====================================================================//
        // Addess
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("address")
            ->Name($langs->trans("CompanyAddress"))
            ->Group($groupName)
            ->isLogged()
            ->MicroData("http://schema.org/PostalAddress", "streetAddress");

        //====================================================================//
        // Zip Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("zip")
            ->Name($langs->trans("CompanyZip"))
            ->MicroData("http://schema.org/PostalAddress", "postalCode")
            ->Group($groupName)
            ->AddOption('maxLength', "18")
            ->isLogged()
            ->isListed();

        //====================================================================//
        // City Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("town")
            ->Name($langs->trans("CompanyTown"))
            ->MicroData("http://schema.org/PostalAddress", "addressLocality")
            ->Group($groupName)
            ->isLogged()
            ->isListed();

        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("country")
            ->Name($langs->trans("CompanyCountry"))
            ->isReadOnly()
            ->Group($groupName)
            ->isListed();

        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->create(SPL_T_COUNTRY)
            ->Identifier("country_code")
            ->Name($langs->trans("CountryCode"))
            ->Group($groupName)
            ->isLogged()
            ->MicroData("http://schema.org/PostalAddress", "addressCountry");

        if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
            //====================================================================//
            // State Name
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Name($langs->trans("State"))
                ->Group($groupName)
                ->isReadOnly();

            //====================================================================//
            // State code
            $this->fieldsFactory()->create(SPL_T_STATE)
                ->Identifier("state_code")
                ->Name($langs->trans("State Code"))
                ->MicroData("http://schema.org/PostalAddress", "addressRegion")
                ->Group($groupName)
                ->isLogged()
                ->isNotTested();
        }
    }

    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMain2Fields()
    {
        global $langs;

        //====================================================================//
        // Phone Pro
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->Identifier("phone_pro")
            ->Name($langs->trans("PhonePro"))
            ->MicroData("http://schema.org/Organization", "telephone")
            ->isLogged()
            ->isListed();

        //====================================================================//
        // Phone Perso
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->Identifier("phone_perso")
            ->Name($langs->trans("PhonePerso"))
            ->isLogged()
            ->MicroData("http://schema.org/PostalAddress", "telephone");

        //====================================================================//
        // Mobile Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->Identifier("phone_mobile")
            ->Name($langs->trans("PhoneMobile"))
            ->MicroData("http://schema.org/Person", "telephone")
            ->isLogged()
            ->isListed();

        //====================================================================//
        // Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
            ->Identifier("email")
            ->Name($langs->trans("Email"))
            ->MicroData("http://schema.org/ContactPoint", "email")
            ->isLogged()
            ->isListed();

        //====================================================================//
        // Active
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("statut")
            ->Name($langs->trans("Active"))
            ->MicroData("http://schema.org/Person", "active");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getMainFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'address':
            case 'zip':
            case 'town':
            case 'state':
            case 'state_code':
            case 'country':
            case 'country_code':
            case 'phone_pro':
            case 'phone_perso':
            case 'phone_mobile':
            case 'email':
                $this->getSimple($fieldName);

                break;
            case 'statut':
                $this->getSimpleBool($fieldName);

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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setMainFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writtings
            case 'address':
            case 'zip':
            case 'town':
            case 'phone_pro':
            case 'phone_perso':
            case 'phone_mobile':
            case 'email':
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'state_code':
                $stateId = $this->getStateByCode($fieldData, $this->object->country_id);
                $this->setSimple("state_id", $stateId);

                break;
            case 'country_code':
                $countryId = $this->getCountryByCode($fieldData);
                $this->setSimple("country_id", $countryId);

                break;
            case 'statut':
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
