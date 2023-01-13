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
    protected function buildMainFields(): void
    {
        global $conf,$langs;

        $groupName = $langs->trans("CompanyAddress");
        //====================================================================//
        // Address
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("address")
            ->name($langs->trans("CompanyAddress"))
            ->group($groupName)
            ->microData("http://schema.org/PostalAddress", "streetAddress")
            ->isLogged()
        ;
        //====================================================================//
        // Zip Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("zip")
            ->name($langs->trans("CompanyZip"))
            ->microData("http://schema.org/PostalAddress", "postalCode")
            ->group($groupName)
            ->addOption('maxLength', "18")
            ->isLogged()
            ->isListed()
        ;
        //====================================================================//
        // City Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("town")
            ->name($langs->trans("CompanyTown"))
            ->microData("http://schema.org/PostalAddress", "addressLocality")
            ->group($groupName)
            ->isLogged()
            ->isListed()
        ;
        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("country")
            ->name($langs->trans("CompanyCountry"))
            ->group($groupName)
            ->isReadOnly()
            ->isListed()
        ;
        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->create(SPL_T_COUNTRY)
            ->identifier("country_code")
            ->name($langs->trans("CountryCode"))
            ->group($groupName)
            ->microData("http://schema.org/PostalAddress", "addressCountry")
            ->isLogged()
        ;

        if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
            //====================================================================//
            // State Name
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("state")
                ->name($langs->trans("State"))
                ->group($groupName)
                ->isReadOnly()
            ;
            //====================================================================//
            // State code
            $this->fieldsFactory()->create(SPL_T_STATE)
                ->identifier("state_code")
                ->name($langs->trans("State Code"))
                ->microData("http://schema.org/PostalAddress", "addressRegion")
                ->group($groupName)
                ->isLogged()
                ->isNotTested()
            ;
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
            ->identifier("phone_pro")
            ->name($langs->trans("PhonePro"))
            ->microData("http://schema.org/Organization", "telephone")
            ->isLogged()
            ->isListed()
        ;
        //====================================================================//
        // Phone Perso
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->identifier("phone_perso")
            ->name($langs->trans("PhonePerso"))
            ->isLogged()
            ->microData("http://schema.org/PostalAddress", "telephone")
        ;
        //====================================================================//
        // Mobile Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->identifier("phone_mobile")
            ->name($langs->trans("PhoneMobile"))
            ->microData("http://schema.org/Person", "telephone")
            ->isLogged()
            ->isListed()
        ;
        //====================================================================//
        // Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
            ->identifier("email")
            ->name($langs->trans("Email"))
            ->microData("http://schema.org/ContactPoint", "email")
            ->isLogged()
            ->isListed()
        ;
        //====================================================================//
        // Active
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("statut")
            ->name($langs->trans("Active"))
            ->microData("http://schema.org/Person", "active")
        ;
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
    protected function getMainFields(string $key, string $fieldName): void
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
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setMainFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
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
                $stateId = $this->getStateByCode((string) $fieldData, $this->object->country_id);
                $this->setSimple("state_id", $stateId);

                break;
            case 'country_code':
                $countryId = $this->getCountryByCode((string) $fieldData);
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
