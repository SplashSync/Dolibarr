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
        // Since V20, ThirdParty has Mobile Phone
        $hasMobilePhone = (Local::dolVersionCmp("20.0.0") >= 0);

        //====================================================================//
        // Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->identifier("phone")
            ->name($langs->trans("Phone"))
            ->isLogged()
            ->microData(
                $hasMobilePhone ? "http://schema.org/PostalAddress" : "http://schema.org/Person",
                "telephone"
            )
            ->isIndexed()
            ->isListed()
        ;
        //====================================================================//
        // Mobile Phone (Since V20)
        if ($hasMobilePhone) {
            $this->fieldsFactory()->create(SPL_T_PHONE)
                ->identifier("phone_mobile")
                ->name($langs->trans("PhoneMobile"))
                ->isLogged()
                ->microData("http://schema.org/Person", "telephone")
                ->isIndexed()
            ;
        }
        //====================================================================//
        // Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
            ->identifier("email")
            ->name($langs->trans("Email"))
            ->microData("http://schema.org/ContactPoint", "email")
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired(self::isEmailRequired())
            ->isPrimary((bool) Local::getParameter("SOCIETE_EMAIL_UNIQUE"))
            ->isIndexed()
            ->isLogged()
            ->isListed()
        ;
        //====================================================================//
        // WebSite
        $this->fieldsFactory()->create(SPL_T_URL)
            ->identifier("url")
            ->name($langs->trans("PublicUrl"))
            ->microData("http://schema.org/Organization", "url")
        ;
        //====================================================================//
        // VAT Number
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("tva_intra")
            ->name($langs->trans("VATIntra"))
            ->group("ID")
            ->addOption('maxLength', "20")
            ->microData("http://schema.org/Organization", "vatID")
        ;
    }

    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMainIdsFields()
    {
        global $langs;

        //====================================================================//
        // Id Professional 1 SIREN
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("idprof1")
            ->group("ID")
            ->name($langs->trans("ProfId1Short"))
            ->microData("http://schema.org/Organization", "duns")
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired((bool) Local::getParameter("SOCIETE_IDPROF1_MANDATORY"))
            ->isIndexed()
        ;
        //====================================================================//
        // Id Professional 2 SIRET
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("idprof2")
            ->group("ID")
            ->name($langs->trans("ProfId2Short"))
            ->microData("http://schema.org/Organization", "taxID")
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired((bool) Local::getParameter("SOCIETE_IDPROF2_MANDATORY"))
            ->isIndexed()
        ;

        //====================================================================//
        // Id Professional 3 APE
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("idprof3")
            ->group("ID")
            ->name($langs->trans("ProfId3Short"))
            ->microData("http://schema.org/Organization", "naics")
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired((bool) Local::getParameter("SOCIETE_IDPROF3_MANDATORY"))
        ;
        //====================================================================//
        // Id Professional 4 RCS
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("idprof4")
            ->group("ID")
            ->name($langs->trans("ProfId4Short"))
            ->microData("http://schema.org/Organization", "isicV4")
            // Set Required when Set As Mandatory in Dolibarr Config
            ->isRequired((bool) Local::getParameter("SOCIETE_IDPROF4_MANDATORY"))
        ;
        //====================================================================//
        // Id Professional 5
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("idprof5")
            ->group("ID")
            ->name($langs->trans("ProfId5Short"))
        ;
        //====================================================================//
        // Id Professional 6
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("idprof6")
            ->name($langs->trans("ProfId6Short"))
            ->group("ID")
            ->microData("http://splashync.com/schemas", "ObjectId")
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
            case 'phone':
            case 'phone_mobile':
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
    protected function setMainFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
            case 'phone':
            case 'phone_mobile':
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

    /**
     * Check if Customer Email is Required
     *
     * @return bool
     */
    protected static function isEmailRequired(): bool
    {
        return Local::getParameter("SOCIETE_EMAIL_MANDATORY")
            || Local::getParameter("SOCIETE_EMAIL_UNIQUE")
        ;
    }
}
