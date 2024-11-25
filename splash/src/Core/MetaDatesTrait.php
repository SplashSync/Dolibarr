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

namespace Splash\Local\Core;

/**
 * Dolibarr Contacts Address Meta Fields
 */
trait MetaDatesTrait
{
    /**
     * @var bool
     */
    private bool $infoLoaded = false;

    /**
     * Build Meta Fields using FieldFactory
     *
     * @return void
     */
    private function buildMetaDatesFields(): void
    {
        global $langs;

        //====================================================================//
        // TMS - Last Change Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier("date_modification")
            ->name($langs->trans("DateLastModification"))
            ->group("Meta")
            ->microData("http://schema.org/DataFeedItem", "dateModified")
            ->isReadOnly()
        ;
        //====================================================================//
        // datec - Creation Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier("date_creation")
            ->name($langs->trans("DateCreation"))
            ->group("Meta")
            ->microData("http://schema.org/DataFeedItem", "dateCreated")
            ->isReadOnly()
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
    private function getMetaDatesFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Last Modification Date
            case 'date_creation':
            case 'date_modification':
                if (!$this->infoLoaded) {
                    $this->object->info($this->object->id);
                    $this->infoLoaded = true;
                }
                $this->out[$fieldName] = dol_print_date($this->object->{$fieldName}, 'dayrfc');

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }
}
