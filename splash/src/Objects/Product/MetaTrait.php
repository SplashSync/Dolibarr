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

namespace   Splash\Local\Objects\Product;

/**
 * @abstract    Dolibarr Products MataData Fields 
 */
trait MetaTrait {

    
    /**
     * @abstract     Build Meta Fields using FieldFactory
     */
   protected function buildMetaFields() {
        global $langs;
        
        //====================================================================//
        // On Sell
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("status_buy")
                ->Name($langs->trans("Status").' ('.$langs->trans("Buy").')')
                ->MicroData("http://schema.org/Product","ordered")
                ->Group("Meta")
                ->isListed();        
        
        //====================================================================//
        // On Buy
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("status")
                ->Name($langs->trans("Status").' ('.$langs->trans("Sell").')')
                ->MicroData("http://schema.org/Product","offered")
                ->Group("Meta")
                ->isListed();                  
        
    }   

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMetaFields($Key,$FieldName) {

        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // OTHERS INFORMATIONS
            //====================================================================//
            case 'status':
            case 'status_buy':
                $this->getSimpleBool($FieldName);
                break;                
            
        }
        
        unset($this->In[$Key]);
    }    
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMetaFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
            case 'status':
            case 'status_buy':
                $this->setSimple($FieldName, (bool) $Data);
                break; 
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }


    
}
