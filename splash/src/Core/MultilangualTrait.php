<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace   Splash\Local\Core;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Acces to Dolibarr Multilang Fields
 */
trait MultilangualTrait
{
    
    /**
     *      @abstract       Update Multilangual Fields of an Object
     *
     *      @param          array       $FieldName        Id of a Multilangual Contents
     *      @param          array       $Data       New Multilangual Contents
     *
     *      @return         self
     */
    public function setMultilang($FieldName = null, $Data = null)
    {
        global $conf;
        
        //====================================================================//
        // We Are in Monolangual Mode
        if (!$conf->global->MAIN_MULTILANGS) {
            return $this->setSimple($FieldName, $Data);
        }
        
        //====================================================================//
        // Safety Check
        //====================================================================//
        if (is_null($Data)) {
            return $this;
        }
        if (!is_array($Data) && !is_a($Data, "ArrayObject")) {
            return $this;
        }
        
        //====================================================================//
        // Update Native Multilangs Descriptions
        //====================================================================//

        //====================================================================//
        // Create or Update Multilangs Fields
        foreach ($Data as $IsoCode => $Content) {
            $this->setMultilangContent($FieldName, $IsoCode, $Content);
        }
        
        //====================================================================//
        // For Safety => Push First Value to Default Lang if Empty + Warning
        $this->setMultilangFallBack($FieldName, $Data);
        
        return $this;
    }
    
    /**
     *      @abstract       Update a Single Multilangual Field of an Object
     *
     *      @param          array       $FieldName      Id of a Multilangual Contents
     *      @param          string      $IsoCode        Language Iso Code
     *      @param          string      $Content        Content String
     *
     *      @return         void
     */
    public function setMultilangContent($FieldName, $IsoCode, $Content)
    {
        global $langs;
        
        //====================================================================//
        // Create This Translation if empty
        if (!isset($this->Object->multilangs[$IsoCode])) {
            $this->Object->multilangs[$IsoCode] = array();
        }
        //====================================================================//
        // Update Contents
        //====================================================================//
        if ($this->Object->multilangs[$IsoCode][$FieldName] !== $Content) {
            $this->Object->multilangs[$IsoCode][$FieldName] = $Content;
            $this->needUpdate();
        }
        //====================================================================//
        // Duplicate Contents to Default language if needed
        if (($IsoCode == $langs->getDefaultLang()) && property_exists(get_class($this->Object), $FieldName)) {
            $this->Object->$FieldName = $Content;
            $this->needUpdate();
        }
    }
    
    /**
     *      @abstract       Ensure Dolibarr Default Language was filled
     *
     *      @param          array       $FieldName  Id of a Multilangual Contents
     *      @param          array       $Data       New Multilangual Contents
     *
     *      @return         self
     */
    public function setMultilangFallBack($FieldName = null, $Data = null)
    {
        global $langs;
        
        //====================================================================//
        // For Safety => Push First Value to Default Lang if Empty + Warning
        if (property_exists(get_class($this->Object), $FieldName) && empty($this->Object->$FieldName)) {
            $this->Object->$FieldName    =   array_shift($Data);
            Splash::log()->war(
                "Value for default Dolibarr language is missing in received Multilangual Contents. "
                    . "Please check configuration of all your sites to use the same default Language. "
                . "Current Default Language is : " . $langs->getDefaultLang()
            );
        }
        
        return $this;
    }
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *      @param          array       $FieldName        Id of a Multilangual Contents
     *      @return         self
     */
    public function getMultilang($FieldName = null)
    {
        global $langs,$conf;

        //====================================================================//
        // Single Language Descriptions
        if (!$conf->global->MAIN_MULTILANGS) {
            return $this->getSimple($FieldName);
        }
        //====================================================================//
        // Native Multilangs Descriptions
        //====================================================================//
        
        //====================================================================//
        // If Multilang Contents doesn't exists
        if (empty($this->Object->multilangs)) {
            $this->Out[$FieldName] = array(
                        $langs->getDefaultLang() => trim($this->Object->$FieldName)
                    );
            return $this;
        }
            
        $Data = array();
        
        //====================================================================//
        // Read Multilang contents
        foreach ($this->Object->multilangs as $IsoCode => $Content) {
            //====================================================================//
            // Give Priority to Default language
            if (($IsoCode == $langs->getDefaultLang()) && property_exists(get_class($this->Object), $FieldName)) {
                $Data[$IsoCode] = $this->Object->$FieldName;
            //====================================================================//
            // Extract from Multilang Array
            } elseif (isset($Content[$FieldName])) {
                $Data[$IsoCode] = $Content[$FieldName];
            }
        }
            
        $this->Out[$FieldName] = $Data;
        return $this;
    }
}
