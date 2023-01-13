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

namespace Splash\Local\Objects\Product\Variants;

/**
 * Product Variant Label Function & Data Access
 */
trait BaseLabelTrait
{
    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildVariantsLabelFields(): void
    {
        global $langs;

        //====================================================================//
        // Ensure Product Variation Module is Active
        if (!self::isVariantEnabled()) {
            return;
        }

        //====================================================================//
        // Setup Default Language of Fields Factory
        $this->fieldsFactory()->setDefaultLanguage($langs->getDefaultLang());
        //====================================================================//
        // Setup Some Labels Translation
        $groupName = $langs->trans("Description");

        foreach ($this->getAvailableLanguages() as $isoCode) {
            //====================================================================//
            // Base Name (Label)
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("base_label")
                ->name($langs->trans("ProductLabel"))
                ->group($groupName)
                ->microData("http://schema.org/Product", "alternateName")
                ->setMultilang($isoCode)
                ->isRequired(self::isDefaultLanguage($isoCode))
                ->isLogged()
            ;
        }
    }

    //====================================================================//
    // Fields Getter Functions
    //====================================================================//

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getVariantsLabelFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Read Default Lang Base Label
        if ('base_label' == $fieldName) {
            $this->out["base_label"] = self::getVariant()->label;
            unset($this->in[$key]);
        }

        //====================================================================//
        // Read Multi-langs Label
        if (0 === strpos($fieldName, 'base_label_')) {
            $langCode = substr($fieldName, strlen('base_label_'));
            $this->out[$fieldName] = $this->getMultiLang("label", $langCode);
            unset($this->in[$key]);
        }
    }

    //====================================================================//
    // Fields Setter Functions
    //====================================================================//

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setVariantsLabelFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // Write Default Lang Base Label
        if ('base_label' == $fieldName) {
            $this->setSimple('label', $fieldData, self::isVariant() ? 'baseProduct' : "object");
            unset($this->in[$fieldName]);
        }

        //====================================================================//
        // Write Multi-langs Label
        if (0 === strpos($fieldName, 'base_label_')) {
            $langCode = substr($fieldName, strlen('base_label_'));
            $this->setMultiLangContent("label", $langCode, (string) $fieldData);
            unset($this->in[$fieldName]);
        }
    }
}
