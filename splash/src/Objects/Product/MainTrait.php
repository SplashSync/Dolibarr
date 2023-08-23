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

namespace   Splash\Local\Objects\Product;

/**
 * Dolibarr Products Fields
 */
trait MainTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMainFields(): void
    {
        global $langs;

        //====================================================================//
        // Customs HS Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("customcode")
            ->name("Customs HS Code")
            ->description($langs->trans("CustomCode"))
            ->microData("http://schema.org/Product", "customsHsCode")
            ->isIndexed()
        ;
        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_COUNTRY)
            ->identifier("country_code")
            ->name($langs->trans("Origin"))
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
    protected function getMainFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'customcode':
                $this->out[$fieldName] = str_replace(" ", "", $this->object->customcode ?? "" ?: "");

                break;
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
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setMainFields(string $fieldName, ?string $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'customcode':
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'country_code':
                $countryId = $this->getCountryByCode((string) $fieldData);
                $this->setSimple("country_id", $countryId);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
