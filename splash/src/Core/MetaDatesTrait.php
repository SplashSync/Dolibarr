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

namespace   Splash\Local\Core;

/**
 * Dolibarr Contacts Address Meta Fields
 */
trait MetaDatesTrait
{
    /**
     * @var bool
     */
    private $infoloaded;

    /**
     * Build Meta Fields using FieldFactory
     *
     * @return void
     */
    private function buildMetaDatesFields()
    {
        global $langs;

        //====================================================================//
        // TMS - Last Change Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->Identifier("date_modification")
            ->Name($langs->trans("DateLastModification"))
            ->Group("Meta")
            ->MicroData("http://schema.org/DataFeedItem", "dateModified")
            ->isReadOnly();

        //====================================================================//
        // datec - Creation Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->Identifier("date_creation")
            ->Name($langs->trans("DateCreation"))
            ->Group("Meta")
            ->MicroData("http://schema.org/DataFeedItem", "dateCreated")
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getMetaDatesFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Last Modifictaion Date
            case 'date_creation':
            case 'date_modification':
                if (!$this->infoloaded) {
                    $this->object->info($this->object->id);
                    $this->infoloaded = true;
                }
                $this->out[$fieldName] = dol_print_date($this->object->{$fieldName}, 'dayrfc');

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }
}
