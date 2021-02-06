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
 * Dolibarr ThirdParty Meta Fields
 */
trait MetaTrait
{
    /**
     * Build Meta Fields using FieldFactory
     *
     * @return void
     */
    private function buildMetaFields()
    {
        global $langs;
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("status")
            ->Name($langs->trans("Active"))
            ->Group("Meta")
            ->MicroData("http://schema.org/Organization", "active")
            ->isListed();

        if (Local::dolVersionCmp("3.6.0") >= 0) {
            //====================================================================//
            // isProspect
            $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("prospect")
                ->Name($langs->trans("Prospect"))
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization", "prospect");
        }

        //====================================================================//
        // isCustomer
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("client")
            ->Name($langs->trans("Customer"))
            ->Group("Meta")
            ->MicroData("http://schema.org/Organization", "customer");

        //====================================================================//
        // isSupplier
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("fournisseur")
            ->Name($langs->trans("Supplier"))
            ->Group("Meta")
            ->MicroData("http://schema.org/Organization", "supplier");

        //====================================================================//
        // isVAT
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("tva_assuj")
            ->Name($langs->trans("VATIsUsed"))
            ->Group("Meta")
            ->MicroData("http://schema.org/Organization", "UseVAT");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getMetaFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // STRUCTURAL INFORMATIONS
            //====================================================================//

            case 'status':
            case 'tva_assuj':
            case 'fournisseur':
                $this->getSimpleBool($fieldName);

                break;
            case 'client':
                $this->getSimpleBit('client', 0);

                break;
            case 'prospect':
                $this->object->prospect = $this->object->client;
                $this->getSimpleBit('prospect', 1);

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
    private function setMetaFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writtings
            case 'status':
            case 'tva_assuj':
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'fournisseur':
                $this->setSimple($fieldName, $fieldData);
                //====================================================================//
                // Empty Code => Ask to for a New One
                if (!empty($fieldData) && empty($this->object->code_fournisseur)) {
                    $this->setSimple("code_fournisseur", -1);
                }

                break;
            case 'client':
                $this->setSimpleBit('client', 0, $fieldData);

                break;
            case 'prospect':
                $this->setSimpleBit('client', 1, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
