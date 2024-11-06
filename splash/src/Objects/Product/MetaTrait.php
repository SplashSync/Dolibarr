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

namespace Splash\Local\Objects\Product;

use Splash\Client\Splash;

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
            ->identifier("status_buy")
            ->name($langs->trans("Status").' ('.$langs->trans("Buy").')')
            ->microData("http://schema.org/Product", "ordered")
            ->group("Meta")
            ->isListed()
        ;
        //====================================================================//
        // On Buy
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("status")
            ->name($langs->trans("Status").' ('.$langs->trans("Sell").')')
            ->microData("http://schema.org/Product", "offered")
            ->group("Meta")
            ->isListed()
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
    protected function getMetaFields(string $key, string $fieldName): void
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
    protected function setMetaFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
            case 'status':
            case 'status_buy':
                $this->setSimple($fieldName, (bool) $fieldData);
                //====================================================================//
                // Since V14: Force Parent Status.
                // See https://github.com/Dolibarr/dolibarr/pull/19286
                if (Splash::isDebugMode() && $this->isVariant()) {
                    $this->setSimple($fieldName, (bool) $fieldData, 'baseProduct');
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
