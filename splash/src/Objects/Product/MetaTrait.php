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

namespace   Splash\Local\Objects\Product;

/**
 * Dolibarr Products MataData Fields
 */
trait MetaTrait
{
    /**
     * Build Meta Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMetaFields()
    {
        global $langs;

        //====================================================================//
        // On Sell
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("status_buy")
            ->Name($langs->trans("Status").' ('.$langs->trans("Buy").')')
            ->MicroData("http://schema.org/Product", "ordered")
            ->Group("Meta")
            ->isListed();

        //====================================================================//
        // On Buy
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("status")
            ->Name($langs->trans("Status").' ('.$langs->trans("Sell").')')
            ->MicroData("http://schema.org/Product", "offered")
            ->Group("Meta")
            ->isListed();
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
            // OTHERS INFORMATIONS
            //====================================================================//
            case 'status':
            case 'status_buy':
                $this->getSimpleBool($fieldName);

                break;
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
            case 'status_buy':
                $this->setSimple($fieldName, (bool) $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
