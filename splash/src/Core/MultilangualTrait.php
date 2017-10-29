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
trait MultilangualTrait {
    
    /**
     *      @abstract       Update Multilangual Fields of an Object
     * 
     *      @param          array       $FieldName        Id of a Multilangual Contents
     *      @param          array       $Data       New Multilangual Contents
     * 
     *      @return         self
     */
    public function setMultilang($FieldName=Null,$Data=Null)
    {
        global $langs,$conf;
        //====================================================================//        
        // We Are in Monolangual Mode
        if (!$conf->global->MAIN_MULTILANGS) {        
            return $this->setSimple($FieldName, $Data);
        }
        //====================================================================//        
        // Safety Check 
        //====================================================================//        
        if ( is_null($Data) ) {
            return $this;
        }
        if ( !is_array($Data) && !is_a($Data,"ArrayObject") ) {
            return $this;
        }
        //====================================================================//        
        // Update Native Multilangs Descriptions
        //====================================================================//        

        //====================================================================//        
        // Create or Update Multilangs Fields
        foreach ($Data as $IsoCode => $Content) {
            //====================================================================//        
            // Create This Translation if empty
            if ( !isset ($this->Object->multilangs[$IsoCode]) ) {
                $this->Object->multilangs[$IsoCode] = array();
            }
            //====================================================================//        
            // Update Contents
            //====================================================================//        
            if ( $this->Object->multilangs[$IsoCode][$FieldName] !== $Content) {             
                $this->Object->multilangs[$IsoCode][$FieldName] = $Content;
                $this->needUpdate();
            }
            //====================================================================//        
            // Duplicate Contents to Default language if needed
            if ( ($IsoCode == $langs->getDefaultLang()) && property_exists(get_class($this->Object),$FieldName)) {
                $this->Object->$FieldName = $Content;
                $this->needUpdate();
            }
        }
        return $this;        
    }  
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *      @param          array       $FieldName        Id of a Multilangual Contents
     *      @return         self
     */
    public function getMultilang($FieldName = Null)
    {
        global $langs,$conf;

        //====================================================================//        
        // Single Language Descriptions
        if ( !$conf->global->MAIN_MULTILANGS ) {
            return $this->getSimple($FieldName);     
        }        
        //====================================================================//        
        // Native Multilangs Descriptions
        //====================================================================//                
        $Data = array(); 
        //====================================================================//        
        // If Multilang Contents doesn't exists
        if ( empty($this->Object->multilangs) )    {
            // Get Default Language
            $DfLang = $langs->getDefaultLang();
            $Data = array( $DfLang => trim($this->Object->$FieldName) );
        } else {
            //====================================================================//        
            // Read Multilang contents 
            foreach ($this->Object->multilangs as $IsoCode => $Content) {
                if (isset ($Content[$FieldName] )) {
                    $Data[$IsoCode] = $Content[$FieldName];
                }
            }
        }
        $this->Out[$FieldName] = $Data;
        return $this;    
    }    
    
}
