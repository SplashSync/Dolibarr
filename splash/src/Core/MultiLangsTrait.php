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
 * Access to Dolibarr Multi-lang Fields
 */
trait MultiLangsTrait
{
    /**
     * Check if ISO Code is Default Languages
     *
     * @param string $isoCode ISO Language Code
     *
     * @return bool
     */
    public static function isDefaultLanguage(string $isoCode): bool
    {
        global $langs;

        return ($langs->getDefaultLang() == $isoCode);
    }

    /**
     * Get All Available Languages
     *
     * @return array
     */
    public function getAvailableLanguages(): array
    {
        global $conf, $langs;
        //====================================================================//
        // We Are in Mono-langs Mode
        if (!$conf->global->MAIN_MULTILANGS) {
            return array($langs->getDefaultLang());
        }
        //====================================================================//
        // We Are in Multi-langs Mode
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
    public function getExtraLanguages(): array
    {
        global $conf;

        //====================================================================//
        // We Are in Mono-langs Mode
        if (!$conf->global->MAIN_MULTILANGS) {
            return array();
        }

        //====================================================================//
        // If No Languages Selected
        $extraLangs = unserialize($conf->global->SPLASH_LANGS);
        if (!is_array($extraLangs)) {
            return array();
        }

        return $extraLangs;
    }

    /**
     * Update a Single Multi-langs Field of an Object
     *
     * @param string $fieldName ID of a Multi-langs Contents
     * @param string $isoCode   Language Iso Code
     * @param string $content   Content String
     *
     * @return void
     */
    public function setMultiLangContent(string $fieldName, string $isoCode, string $content): void
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
        // NOT Default language
        if ($isoCode != $langs->getDefaultLang()) {
            return;
        }
        //====================================================================//
        // Duplicate Contents to Default language if needed
        if (property_exists(get_class($this->object), $fieldName)) {
            $this->object->{$fieldName} = $content;
        }
        //====================================================================//
        // Duplicate Contents to Extra languages if not Defined
        foreach ($this->getExtraLanguages() as $isoCode) {
            if (!empty($this->object->multilangs[$isoCode][$fieldName])) {
                continue;
            }
            $this->object->multilangs[$isoCode][$fieldName] = $content;
        }
    }

    /**
     * Read Multi-langs Fields of an Object
     *
     * @param string $fieldName ID of a Multi-langs Contents
     * @param string $isoCode   Language Code
     *
     * @return null|string
     */
    public function getMultiLang(string $fieldName, string $isoCode): ?string
    {
        global $conf;

        //====================================================================//
        // Single Language Descriptions
        if (!$conf->global->MAIN_MULTILANGS) {
            return null;
        }

        //====================================================================//
        // Native Multi-langs Descriptions
        //====================================================================//

        //====================================================================//
        // If Multi-langs Contents doesn't exists
        if (!isset($this->object->multilangs[$isoCode][$fieldName])) {
            return null;
        }

        return (string) $this->object->multilangs[$isoCode][$fieldName];
    }
}
