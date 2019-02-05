<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
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
 * Dolibarr Products Core Fields (Required)
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     */
    protected function buildCoreFields()
    {
        global $langs;
        
        $groupName  =   $langs->trans("Description");

        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("ref")
            ->Name($langs->trans("ProductRef"))
            ->isListed()
            ->MicroData("http://schema.org/Product", "model")
            ->isLogged()
            ->isRequired();
        
        //====================================================================//
        // Name (Default Language)
        $this->fieldsFactory()
            ->Create(SPL_T_VARCHAR)
            ->Identifier("label")
            ->Name($langs->trans("ProductLabel"))
            ->isListed()
            ->isLogged()
            ->Group($groupName)
            ->addOption('language', $langs->getDefaultLang())
            ->MicroData("http://schema.org/Product", "name")
            ->isRequired();
        
        //====================================================================//
        // Description (Default Language)
        $this->fieldsFactory()
            ->Create(SPL_T_VARCHAR)
            ->Identifier("description")
            ->Name($langs->trans("Description"))
            ->isListed()
            ->isLogged()
            ->Group($groupName)
            ->addOption('language', $langs->getDefaultLang())
            ->MicroData("http://schema.org/Product", "description");

        //====================================================================//
        // Note
        $this->fieldsFactory()->create(SPL_T_TEXT)
            ->Identifier("note")
            ->Name($langs->trans("Note"))
            ->Group($groupName)
            ->addOption('language', $langs->getDefaultLang())
            ->MicroData("http://schema.org/Product", "privatenote");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCoreFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
                $this->getSimple($fieldName);

                break;
            case 'label':
            case 'description':
            case 'note':
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
     */
    protected function setCoreFields($fieldName, $fieldData)
    {
        global $langs;
        
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writtings
            case 'ref':
                // Update Path of Object Documents In Database
                $this->updateFilesPath("produit", $this->object->ref, $fieldData);
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'label':
            case 'description':
            case 'note':
                $this->setSimple($fieldName, $fieldData);
                $this->setMultilangContent($fieldName, $langs->getDefaultLang(), $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
