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

use Splash\Core\SplashCore as Splash;
use Splash\Local\Services\ProductIdentifier;

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
    protected function buildCoreFields(): void
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
            ->identifier("ref")
            ->name($langs->trans("ProductRef"))
            ->microData("http://schema.org/Product", "model")
            ->isListed()
            ->isLogged()
            ->isRequired()
            ->isPrimary()
        ;

        foreach ($this->getAvailableLanguages() as $isoCode) {
            //====================================================================//
            // Full Name (Label with Options)
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("label")
                ->name($langs->trans("ProductLabel").$withVariants)
                ->group($groupName)
                ->microData("http://schema.org/Product", "name")
                ->setMultilang($isoCode)
                ->isListed(self::isDefaultLanguage($isoCode))
                ->isLogged()
                //====================================================================//
                // If Product Variation Module is Active => Read Only
                ->isReadOnly(self::isVariantEnabled())
                //====================================================================//
                // If Product Variation Module is Active => Required in Default Language
                ->isRequired(!self::isVariantEnabled() && self::isDefaultLanguage($isoCode))
            ;

            //====================================================================//
            // Product Description
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("description")
                ->name($langs->trans("Description"))
                ->group($groupName)
                ->microData("http://schema.org/Product", "description")
                ->setMultilang($isoCode)
                ->isListed(self::isDefaultLanguage($isoCode))
                ->isLogged()
            ;
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
    protected function getCoreFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
            case 'label':
            case 'description':
            case 'note':
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
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setCoreFields(string $fieldName, ?string $fieldData): void
    {
        global $langs;

        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
            case 'ref':
                //====================================================================//
                // Normalize Reference the way Dolibarr Stores it
                // Otherwise, "A/B" never equals the stored "A_B": the product is
                // updated on each sync and its documents path is renamed to a
                // folder that does not exist.
                $ref = is_null($fieldData) ? null : ProductIdentifier::normalizeRef($fieldData);
                if (!is_null($fieldData) && ($ref !== $fieldData)) {
                    Splash::log()->war(sprintf(
                        "Product reference '%s' was normalized to '%s', as Dolibarr stores it.",
                        $fieldData,
                        $ref
                    ));
                }
                // Update Path of Object Documents In Database
                $this->updateFilesPath("produit", (string) $this->object->ref, (string) $ref);
                $this->setSimple($fieldName, $ref);

                break;
            case 'label':
                if (self::isVariantEnabled()) {
                    Splash::log()->war("ErrLocalTpl", __CLASS__, __FUNCTION__, Splash::trans("ProductLabelIsRo"));

                    return;
                }
                $this->setSimple($fieldName, $fieldData);
                $this->setMultiLangContent($fieldName, $langs->getDefaultLang(), (string) $fieldData);

                break;
            case 'description':
                $this->setSimple($fieldName, $fieldData);
                $this->setMultiLangContent($fieldName, $langs->getDefaultLang(), (string) $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
