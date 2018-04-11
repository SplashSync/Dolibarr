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

/**
 * @abstract    Dolibarr Contacts Address Meta Fields 
 */
trait MetaDatesTrait {


    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaDatesFields() {
        global $langs;
        
        //====================================================================//
        // TMS - Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date_modification")
                ->Name($langs->trans("DateLastModification"))
                ->Group("Meta")
                ->MicroData("http://schema.org/DataFeedItem","dateModified")
                ->isReadOnly();
        
        //====================================================================//
        // datec - Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date_creation")
                ->Name($langs->trans("DateCreation"))
                ->Group("Meta")
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->isReadOnly();       
   
    }   

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMetaDatesFields($Key,$FieldName) {

        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Last Modifictaion Date
            case 'date_creation':
            case 'date_modification':
                if (!$this->infoloaded)  {
                    $this->Object->info($this->Object->id);
                    $this->infoloaded = True;
                }
                $this->Out[$FieldName] = dol_print_date($this->Object->$FieldName,'dayrfc');
                break;              

            default:
                return;
        }
        
        unset($this->In[$Key]);
    }    
    
}
