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

use Splash\Core\SplashCore as Splash;
use Splash\Local\Local;

/**
 * Dolibarr ThirdParty Core Fields (Required)
 */
trait CoreTrait
{
    /**
     * @var null|string
     */
    private ?string $firstName = null;

    /**
     * @var null|string
     */
    private ?string $lastName = null;

    /**
     * @var null|string
     */
    private ?string $companyName = null;

    /**
     * Build Core Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCoreFields()
    {
        global $langs,$conf;

        //====================================================================//
        // Company
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("name")
            ->name($langs->trans("CompanyName"))
            ->isLogged()
            ->description($langs->trans("CompanyName"))
            ->microData("http://schema.org/Organization", "legalName")
            ->isPrimary(!Local::getParameter("SOCIETE_EMAIL_UNIQUE"))
            ->isRequired()
            ->isIndexed()
            ->isListed()
        ;
        //====================================================================//
        // Firstname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("firstname")
            ->name($langs->trans("Firstname"))
            ->isLogged()
            ->microData("http://schema.org/Person", "familyName")
            ->association("firstname", "lastname")
        ;
        //====================================================================//
        // Lastname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("lastname")
            ->name($langs->trans("Lastname"))
            ->isLogged()
            ->microData("http://schema.org/Person", "givenName")
            ->association("firstname", "lastname")
        ;
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("code_client")
            ->name($langs->trans("CustomerCode"))
            ->description($langs->trans("CustomerCodeDesc"))
            ->microData("http://schema.org/Organization", "alternateName")
            ->isListed()
            ->isIndexed()
        ;
        //====================================================================//
        // Set as Read Only when Auto-Generated by Dolibarr
        if ("mod_codeproduct_leopard" != $conf->global->SOCIETE_CODECLIENT_ADDON) {
            $this->fieldsFactory()->isReadOnly();
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCoreFields(string $key, string $fieldName)
    {
        //====================================================================//
        // Read Company FullName => Firstname, Lastname - Company
        $fullName = $this->decodeFullName($this->object->name);

        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Full Name Readings
            case 'name':
            case 'firstname':
            case 'lastname':
                $this->out[$fieldName] = $fullName[$fieldName] ?? null;

                break;
                //====================================================================//
                // Direct Readings
            case 'code_client':
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
    protected function setCoreFields(string $fieldName, ?string $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Full Name Writing
            case 'name':
                $this->companyName = $fieldData;

                break;
            case 'firstname':
                $this->firstName = $fieldData;

                break;
            case 'lastname':
                $this->lastName = $fieldData;

                break;
            case 'code_client':
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Check FullName Array and update if needed
     *
     * @return void
     */
    protected function updateFullName()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Get Current Values if Not Written
        $currentName = $this->decodeFullName($this->object->name);
        if (empty($this->firstName) && !empty($currentName["firstname"])) {
            $this->firstName = $currentName["firstname"];
        }
        if (empty($this->lastName) && !empty($currentName["lastname"])) {
            $this->lastName = $currentName["lastname"];
        }
        if (empty($this->companyName) && !empty($currentName["name"])) {
            $this->companyName = $currentName["name"];
        }
        //====================================================================//
        // No First or Last Name
        if (empty(trim($this->firstName)) && empty(trim($this->lastName))) {
            $this->setSimple("name", $this->companyName);

            return;
        }
        //====================================================================//
        // Encode Full Name String
        $encodedFullName = $this->encodeFullName($this->firstName, $this->lastName, $this->companyName);
        $this->setSimple("name", $encodedFullName);
    }

    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

    /**
     * Encode Full Name String using Firstname, Lastname & Compagny Name
     *
     * @param null|string $firstname Contact Firstname
     * @param null|string $lastname  Contact Lastname
     * @param null|string $company   Contact Company
     *
     * @return string Contact Full Name
     */
    private static function encodeFullName(?string $firstname, ?string $lastname, ?string $company = ""): string
    {
        //====================================================================//
        // Clean Input Data
        $fullName = (string) preg_replace('/[-,]/', '', trim((string) $firstname));
        $last = preg_replace('/[-,]/', '', trim((string) $lastname));
        $comp = preg_replace('/[-,]/', '', trim((string) $company));
        //====================================================================//
        // Encode Full Name
        if (!empty($last)) {
            $fullName .= ", ".$last;
        }
        if (!empty($comp)) {
            $fullName .= " - ".$comp;
        }

        return $fullName;
    }

    /**
     * Decode Firstname, Lastname & Company Name using Full Name String
     *
     * @param null|string $fullName Contact Full Name
     *
     * @return null|array Contact Firstname, Lastname & Company Name
     */
    private static function decodeFullName(string $fullName = null): ?array
    {
        //====================================================================//
        // Safety Checks
        if (empty($fullName)) {
            return null;
        }

        //====================================================================//
        // Init
        $result = array('name' => "", 'lastname' => "",'firstname' => ""  );

        //====================================================================//
        // Detect Single Company Name
        if ((false === strpos($fullName, ' - ')) && (false === strpos($fullName, ', '))) {
            $result['name'] = $fullName;

            return $result;
        }
        //====================================================================//
        // Detect Company Name
        if (false !== ($pos = strpos($fullName, ' - '))) {
            $result['name'] = substr($fullName, $pos + 3);
            $fullName = substr($fullName, 0, $pos);
        }
        //====================================================================//
        // Detect Last Name
        if (false !== ($pos = strpos($fullName, ', '))) {
            $result['lastname'] = substr($fullName, $pos + 2);
            $fullName = substr($fullName, 0, $pos);
        }
        $result['firstname'] = $fullName;

        return $result;
    }
}
