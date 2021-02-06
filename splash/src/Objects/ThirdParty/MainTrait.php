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

use Splash\Local\Local;

/**
 * Dolibarr ThirdParty Main Fields
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
        global $langs;

        //====================================================================//
        // Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->Identifier("phone")
            ->Name($langs->trans("Phone"))
            ->isLogged()
            ->MicroData("http://schema.org/Person", "telephone")
            ->isListed();

        //====================================================================//
        // Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
            ->Identifier("email")
            ->Name($langs->trans("Email"))
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired((bool) Local::getParameter("SOCIETE_EMAIL_MANDATORY"))
            ->MicroData("http://schema.org/ContactPoint", "email")
            ->isLogged()
            ->isListed();

        //====================================================================//
        // WebSite
        $this->fieldsFactory()->create(SPL_T_URL)
            ->Identifier("url")
            ->Name($langs->trans("PublicUrl"))
            ->MicroData("http://schema.org/Organization", "url");

        //====================================================================//
        // Id Professionnal 1 SIREN
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("idprof1")
            ->Group("ID")
            ->Name($langs->trans("ProfId1Short"))
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired((bool) Local::getParameter("SOCIETE_IDPROF1_MANDATORY"))
            ->MicroData("http://schema.org/Organization", "duns");

        //====================================================================//
        // Id Professionnal 2 SIRET
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("idprof2")
            ->Group("ID")
            ->Name($langs->trans("ProfId2Short"))
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired((bool) Local::getParameter("SOCIETE_IDPROF2_MANDATORY"))
            ->MicroData("http://schema.org/Organization", "taxID");

        //====================================================================//
        // Id Professionnal 3 APE
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("idprof3")
            ->Group("ID")
            ->Name($langs->trans("ProfId3Short"))
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired((bool) Local::getParameter("SOCIETE_IDPROF3_MANDATORY"))
            ->MicroData("http://schema.org/Organization", "naics");

        //====================================================================//
        // Id Professionnal 4 RCS
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("idprof4")
            ->Group("ID")
            ->Name($langs->trans("ProfId4Short"))
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired((bool) Local::getParameter("SOCIETE_IDPROF4_MANDATORY"))
            ->MicroData("http://schema.org/Organization", "isicV4");

        //====================================================================//
        // Id Professionnal 5
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("idprof5")
            ->Group("ID")
            ->Name($langs->trans("ProfId5Short"));

        //====================================================================//
        // Id Professionnal 6
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("idprof6")
            ->Name($langs->trans("ProfId6Short"))
            ->Group("ID")
            ->MicroData("http://splashync.com/schemas", "ObjectId");

        //====================================================================//
        // VAT Number
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("tva_intra")
            ->Name($langs->trans("VATIntra"))
            ->Group("ID")
            ->addOption('maxLength', "20")
            ->MicroData("http://schema.org/Organization", "vatID");
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
            case 'phone':
            case 'email':
            case 'url':
            case 'idprof1':
            case 'idprof2':
            case 'idprof3':
            case 'idprof4':
            case 'idprof5':
            case 'idprof6':
            case 'tva_intra':
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
            case 'phone':
            case 'email':
            case 'url':
            case 'idprof1':
            case 'idprof2':
            case 'idprof3':
            case 'idprof4':
            case 'idprof5':
            case 'idprof6':
            case 'tva_intra':
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
