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

namespace   Splash\Local\Core;

/**
 * Dolibarr Orders & Invoices Notes Fields
 */
trait NotesTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildNotesFields(): void
    {
        global $langs;

        //====================================================================//
        // Public Note
        $this->fieldsFactory()->create(SPL_T_TEXT)
            ->identifier("note_public")
            ->name($langs->trans("NotePublic"))
            ->group("Notes")
        ;
        //====================================================================//
        // Private Note
        $this->fieldsFactory()->create(SPL_T_TEXT)
            ->identifier("note_private")
            ->name($langs->trans("NotePrivate"))
            ->group("Notes")
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
    protected function getNotesFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'note_public':
            case 'note_private':
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
     * @param null|scalar $fieldData Field Data
     *
     * @return void
     */
    protected function setNotesFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'note_public':
            case 'note_private':
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);
    }
}
