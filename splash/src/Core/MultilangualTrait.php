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
 * Access to Dolibarr Multilang Fields
 */
trait MultilangualTrait
{
    /**
     * Check if ISO Code is Default Languages
     *
     * @param string $isoCode ISO Language Code
     *
     * @return bool
     */
    public static function isDefaultLanguage($isoCode)
    {
        global $langs;

        return ($langs->getDefaultLang() == $isoCode);
    }

    /**
     * Get All Available Languages
     *
     * @return array
     */
    public function getAvailableLanguages()
    {
        global $conf, $langs;
        //====================================================================//
        // We Are in Monolangual Mode
        if (!$conf->global->MAIN_MULTILANGS) {
            return array($langs->getDefaultLang());
        }
        //====================================================================//
        // We Are in Multilangual Mode
        return array_merge(
            array($langs->getDefaultLang()),
            $this->getExtraLanguages()
        );
    }

    /**
     * Get Available Extra Languages
     *
     * @return array
     */
    public function getExtraLanguages()
    {
        global $conf;

        //====================================================================//
        // We Are in Monolangual Mode
        if (!$conf->global->MAIN_MULTILANGS) {
            return array();
        }

        //====================================================================//
        // If No Langauges Selected
        $extraLangs = unserialize($conf->global->SPLASH_LANGS);
        if (!is_array($extraLangs)) {
            return array();
        }

        return $extraLangs;
    }

    /**
     * Update a Single Multilangual Field of an Object
     *
     * @param string $fieldName Id of a Multilangual Contents
     * @param string $isoCode   Language Iso Code
     * @param string $content   Content String
     *
     * @return void
     */
    public function setMultilangContent($fieldName, $isoCode, $content)
    {
        global $langs;

        //====================================================================//
        // Create This Translation if empty
        if (!isset($this->object->multilangs[$isoCode])) {
            $this->object->multilangs[$isoCode] = array();
        }
        //====================================================================//
        // Update Contents
        //====================================================================//
        if ($this->object->multilangs[$isoCode][$fieldName] != $content) {
            $this->object->multilangs[$isoCode][$fieldName] = $content;
            $this->needUpdate();
        }
        //====================================================================//
        // Duplicate Contents to Default language if needed
        if (($isoCode == $langs->getDefaultLang()) && property_exists(get_class($this->object), $fieldName)) {
            $this->object->{$fieldName} = $content;
        }
    }

    /**
     * Read Multilangual Fields of an Object
     *
     * @param string $fieldName Id of a Multilangual Contents
     * @param string $isoCode   Language Code
     *
     * @return null|string
     */
    public function getMultilang($fieldName, $isoCode)
    {
        global $conf;

        //====================================================================//
        // Single Language Descriptions
        if (!$conf->global->MAIN_MULTILANGS) {
            return null;
        }

        //====================================================================//
        // Native Multilangs Descriptions
        //====================================================================//

        //====================================================================//
        // If Multilang Contents doesn't exists
        if (!isset($this->object->multilangs[$isoCode][$fieldName])) {
            return null;
        }

        return $this->object->multilangs[$isoCode][$fieldName];
    }
}
