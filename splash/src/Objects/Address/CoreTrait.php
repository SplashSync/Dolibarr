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

namespace   Splash\Local\Objects\Address;

/**
 * @abstract    Dolibarr Contacts Address Fields (Required) 
 */
trait CoreTrait {

    /**
     *  @abstract     Build Core Fields using FieldFactory
     */
    protected function buildCoreFields()   {
        global $langs;

        //====================================================================//
        // Firstname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name($langs->trans("Firstname"))
                ->MicroData("http://schema.org/Person","familyName")
                ->isListed()
                ->isLogged()
                ->isRequired();
        
        //====================================================================//
        // Lastname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name($langs->trans("Lastname"))
                ->MicroData("http://schema.org/Person","givenName")        
                ->isLogged()
                ->isListed();
                
        //====================================================================//
        // Customer
        $this->FieldsFactory()->Create(self::Objects()->Encode( "ThirdParty" , SPL_T_ID))
                ->Identifier("socid")
                ->Name($langs->trans("Company"))
                ->MicroData("http://schema.org/Organization","ID");        
        
        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref_ext")
                ->Name($langs->trans("CustomerCode"))
                ->Description($langs->trans("CustomerCodeDesc"))
                ->isListed()
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","name");
        
    }    
    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    protected function getCoreFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Contact ThirdParty Id 
            case 'socid':
                $this->Out[$FieldName] = self::Objects()->Encode( "ThirdParty" , $this->Object->socid);
                break;
            
            //====================================================================//
            // Direct Readings
            case 'name':
            case 'firstname':
            case 'lastname':
            case 'ref_ext':
                $this->getSimple($FieldName);
                break;
            default:
                return;
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
    protected function setCoreFields($FieldName,$Data) 
    {

        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Contact Company Id 
            case 'socid':
                $this->setSimple($FieldName, self::Objects()->Id( $Data ));
                break;       

            //====================================================================//
            // Direct Writtings
            case 'name':
            case 'firstname':
            case 'lastname':
                $this->setSimple($FieldName, $Data);
                break;       
                
            case 'ref_ext':
                if ( $this->Object->$FieldName != $Data ) {
                    $this->setDatabaseField("ref_ext", $Data);
                }  
                break;       
               
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }    
    
}
