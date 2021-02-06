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

use Splash\Core\SplashCore as Splash;

/**
 * Dolibarr Products Core Fields (Required)
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCoreFields()
    {
        global $langs;

        //====================================================================//
        // Setup Default Language of Fields Factory
        $this->fieldsFactory()->setDefaultLanguage($langs->getDefaultLang());
        //====================================================================//
        // Setup Some Labels Translation
        $groupName = $langs->trans("Description");
        $withVariants = self::isVariantEnabled() ? (" (+".$langs->trans("VariantAttributes").")"): "";

        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("ref")
            ->Name($langs->trans("ProductRef"))
            ->isListed()
            ->MicroData("http://schema.org/Product", "model")
            ->isLogged()
            ->isRequired();

        foreach ($this->getAvailableLanguages() as $isoCode) {
            //====================================================================//
            // Full Name (Label with Options)
            $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("label")
                ->Name($langs->trans("ProductLabel").$withVariants)
                ->isListed(self::isDefaultLanguage($isoCode))
                ->isLogged()
                ->Group($groupName)
                ->MicroData("http://schema.org/Product", "name")
                ->setMultilang($isoCode)
                //====================================================================//
                // If Product Variation Module is Active => Read Only
                ->isReadOnly(self::isVariantEnabled())
                //====================================================================//
                // If Product Variation Module is Active => Required in Default Language
                ->isRequired(!self::isVariantEnabled() && self::isDefaultLanguage($isoCode));

            //====================================================================//
            // Product Description
            $this->fieldsFactory()
                ->Create(SPL_T_VARCHAR)
                ->Identifier("description")
                ->Name($langs->trans("Description"))
                ->isListed(self::isDefaultLanguage($isoCode))
                ->isLogged()
                ->Group($groupName)
                ->MicroData("http://schema.org/Product", "description")
                ->setMultilang($isoCode);
        }

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
            // Direct Writings
            case 'ref':
                // Update Path of Object Documents In Database
                $this->updateFilesPath("produit", $this->object->ref, $fieldData);
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'label':
                if (self::isVariantEnabled()) {
                    Splash::log()->war("ErrLocalTpl", __CLASS__, __FUNCTION__, Splash::trans("ProductLabelIsRo"));

                    return;
                }
                $this->setSimple($fieldName, $fieldData);
                $this->setMultilangContent($fieldName, $langs->getDefaultLang(), $fieldData);

                break;
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
