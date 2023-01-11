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

namespace   Splash\Local\Objects\Product;

use Splash\Models\Fields\FieldsManagerTrait;

/**
 * Dolibarr Products Multi-lang Fields
 *
 * @see Multilang Field Definition Already done by CoreTrait
 */
trait MultiLangTrait
{
    use FieldsManagerTrait;

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMultiLangFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Read Multi-lang Label
        if (0 === strpos($fieldName, 'label_')) {
            $langCode = substr($fieldName, strlen('label_'));
            $this->out[$fieldName] = $this->getMultiLang("label", $langCode);
            unset($this->in[$key]);
        }

        //====================================================================//
        // Read Multi-lang Description
        if (0 === strpos($fieldName, 'description_')) {
            $langCode = substr($fieldName, strlen('description_'));
            $this->out[$fieldName] = $this->getMultiLang('description', $langCode);
            unset($this->in[$key]);
        }
    }

    /**
     * Write Given Fields
     *
     * @param string  $fieldName Field Identifier / Name
     * @param ?string $fieldData Field Data
     *
     * @return void
     */
    protected function setMultiLangFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // Write Multi-lang Label
        if (0 === strpos($fieldName, 'label_')) {
            $langCode = substr($fieldName, strlen('label_'));
            $this->setMultiLangContent("label", $langCode, (string) $fieldData);
            unset($this->in[$fieldName]);
        }

        //====================================================================//
        // Write Multi-lang Description
        if (0 === strpos($fieldName, 'description_')) {
            $langCode = substr($fieldName, strlen('description_'));
            $this->setMultiLangContent('description', $langCode, (string) $fieldData);
            unset($this->in[$fieldName]);
        }
    }
}
