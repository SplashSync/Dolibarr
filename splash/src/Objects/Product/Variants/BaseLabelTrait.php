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
    protected function buildVariantsLabelFields()
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
            $this->fieldsFactory()
                ->Create(SPL_T_VARCHAR)
                ->Identifier("base_label")
                ->Name($langs->trans("ProductLabel"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Product", "alternateName")
                ->setMultilang($isoCode)
                ->isRequired(self::isDefaultLanguage($isoCode))
                ->isLogged();
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
    protected function getVariantsLabelFields($key, $fieldName)
    {
        //====================================================================//
        // Read Default Lang Base Label
        if ('base_label' == $fieldName) {
            $this->out["base_label"] = self::isVariant() ? $this->baseProduct->label : $this->object->label;
            unset($this->in[$key]);
        }

        //====================================================================//
        // Read Multilang Label
        if (0 === strpos($fieldName, 'base_label_')) {
            $langCode = substr($fieldName, strlen('base_label_'));
            $this->out[$fieldName] = $this->getMultilang("label", $langCode);
            unset($this->in[$key]);
        }
    }

    //====================================================================//
    // Fields Setter Functions
    //====================================================================//

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setVariantsLabelFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Write Default Lang Base Label
        if ('base_label' == $fieldName) {
            $this->setSimple('label', $fieldData, self::isVariant() ? 'baseProduct' : "object");
            unset($this->in[$fieldName]);
        }

        //====================================================================//
        // Write Multilang Label
        if (0 === strpos($fieldName, 'base_label_')) {
            $langCode = substr($fieldName, strlen('base_label_'));
            $this->setMultilangContent("label", $langCode, $fieldData);
            unset($this->in[$fieldName]);
        }
    }
}
