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

namespace Splash\Local\Objects\ThirdParty;

/**
 * Dolibarr ThirdParty Address Fields
 */
trait AddressTrait
{
    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildAddressFields()
    {
        global $langs;

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
            ->Group($groupName)
            ->MicroData("http://schema.org/PostalAddress", "postalCode")
            ->AddOption('maxLength', "18")
            ->isLogged()
            ->isListed();

        //====================================================================//
        // City Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("town")
            ->Name($langs->trans("CompanyTown"))
            ->Group($groupName)
            ->isLogged()
            ->MicroData("http://schema.org/PostalAddress", "addressLocality");

        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("country")
            ->Name($langs->trans("CompanyCountry"))
            ->Group($groupName)
            ->isReadOnly()
            ->isListed();

        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->create(SPL_T_COUNTRY)
            ->Identifier("country_code")
            ->Name($langs->trans("CountryCode"))
            ->Group($groupName)
            ->isLogged()
            ->MicroData("http://schema.org/PostalAddress", "addressCountry");

        //====================================================================//
        // State Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("state")
            ->Group($groupName)
            ->Name($langs->trans("State"))
            ->isReadOnly();

        //====================================================================//
        // State code
        $this->fieldsFactory()->create(SPL_T_STATE)
            ->Identifier("state_code")
            ->Name($langs->trans("State Code"))
            ->Group($groupName)
            ->MicroData("http://schema.org/PostalAddress", "addressRegion")
            ->isNotTested();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getAddressFields($key, $fieldName)
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
    protected function setAddressFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'address':
            case 'zip':
            case 'town':
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'country_code':
                $this->setSimple("country_id", $this->getCountryByCode($fieldData));

                break;
            case 'state_code':
                //====================================================================//
                // If Country Also Changed => Update First
                if (isset($this->in["country_code"])) {
                    $this->setSimple("country_id", $this->getCountryByCode($this->in["country_code"]));
                }
                $stateId = $this->getStateByCode($fieldData, $this->object->country_id);
                $this->setSimple("state_id", $stateId);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
