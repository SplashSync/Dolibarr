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

use Splash\Models\Fields\FieldsManagerTrait;

/**
 * Dolibarr Products Multilang Fields
 *
 * @see Multilang Field Definition Already done by CoreTrait
 */
trait MultilangTrait
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
    protected function getMultilangFields($key, $fieldName)
    {
        //====================================================================//
        // Read Multilang Label
        if (0 === strpos($fieldName, 'label_')) {
            $langCode = substr($fieldName, strlen('label_'));
            $this->out[$fieldName] = $this->getMultilang("label", $langCode);
            unset($this->in[$key]);
        }

        //====================================================================//
        // Read Multilang Description
        if (0 === strpos($fieldName, 'description_')) {
            $langCode = substr($fieldName, strlen('description_'));
            $this->out[$fieldName] = $this->getMultilang('description', $langCode);
            unset($this->in[$key]);
        }
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setMultilangFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Write Multilang Label
        if (0 === strpos($fieldName, 'label_')) {
            $langCode = substr($fieldName, strlen('label_'));
            $this->setMultilangContent("label", $langCode, $fieldData);
            unset($this->in[$fieldName]);
        }

        //====================================================================//
        // Write Multilang Description
        if (0 === strpos($fieldName, 'description_')) {
            $langCode = substr($fieldName, strlen('description_'));
            $this->setMultilangContent('description', $langCode, $fieldData);
            unset($this->in[$fieldName]);
        }
    }
}
