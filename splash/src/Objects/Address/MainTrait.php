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
 * @abstract    Dolibarr Contacts Address Main Fields
 */
trait MainTrait {

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    protected function buildMainFields() {
        global $conf,$langs;
        
        $GroupName = $langs->trans("CompanyAddress");
        //====================================================================//
        // Addess
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("address")
                ->Name($langs->trans("CompanyAddress"))
                ->Group($GroupName)
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","streetAddress");

        //====================================================================//
        // Zip Code
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("zip")
                ->Name( $langs->trans("CompanyZip"))
                ->MicroData("http://schema.org/PostalAddress","postalCode")
                ->Group($GroupName)
                ->AddOption('maxLength' , 18)
                ->isLogged()
                ->isListed();
        
        //====================================================================//
        // City Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("town")
                ->Name($langs->trans("CompanyTown"))
                ->MicroData("http://schema.org/PostalAddress","addressLocality")
                ->Group($GroupName)
                ->isLogged()
                ->isListed();
        
        //====================================================================//
        // Country Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("country")
                ->Name($langs->trans("CompanyCountry"))
                ->ReadOnly()
                ->Group($GroupName)
                ->isListed();
        
        //====================================================================//
        // Country ISO Code
        $this->FieldsFactory()->Create(SPL_T_COUNTRY)
                ->Identifier("country_code")
                ->Name($langs->trans("CountryCode"))
                ->Group($GroupName)
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","addressCountry");

        if (empty($conf->global->SOCIETE_DISABLE_STATE))
        {        
            //====================================================================//
            // State Name
            $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                    ->Identifier("state")
                    ->Name($langs->trans("State"))
                    ->Group($GroupName)
                    ->ReadOnly();

            //====================================================================//
            // State code
            $this->FieldsFactory()->Create(SPL_T_STATE)
                    ->Identifier("state_code")
                    ->Name($langs->trans("State Code"))
                    ->MicroData("http://schema.org/PostalAddress","addressRegion")
                    ->Group($GroupName)
                    ->isLogged()
                    ->NotTested();
        }

        //====================================================================//
        // Phone Pro
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_pro")
                ->Name($langs->trans("PhonePro"))
                ->MicroData("http://schema.org/Organization","telephone")
                ->isLogged()
                ->isListed();

        //====================================================================//
        // Phone Perso
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_perso")
                ->Name($langs->trans("PhonePerso"))
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","telephone");
        
        //====================================================================//
        // Mobile Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_mobile")
                ->Name($langs->trans("PhoneMobile"))
                ->MicroData("http://schema.org/Person","telephone")
                ->isLogged()
                ->isListed();

        //====================================================================//
        // Email
        $this->FieldsFactory()->Create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name($langs->trans("Email"))
                ->MicroData("http://schema.org/ContactPoint","email")
                ->isLogged()
                ->isListed();  
        
        //====================================================================//
        // Active
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("statut")
                ->Name($langs->trans("Active"))
                ->MicroData("http://schema.org/Person","active");             
        
        return;
    }

    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    protected function getMainFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'address':
            case 'zip':
            case 'town':
            case 'state':
            case 'state_code':
            case 'country':
            case 'country_code':
            case 'phone_pro':
            case 'phone_perso':
            case 'phone_mobile':
            case 'email':
                $this->getSimple($FieldName);
                break;
            
            case 'statut':
                $this->getSimpleBool($FieldName);
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
    protected function setMainFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
            case 'address':
            case 'zip':
            case 'town':
            case 'phone_pro':
            case 'phone_perso':
            case 'phone_mobile':
            case 'email':           
                $this->setSimple($FieldName,$Data);
                break;                    
            
            case 'state_code':
                $StateId    =   $this->getStateByCode($Data,$this->Object->country_id);
                $this->setSimple("state_id",$StateId);
                break;                    
            
            case 'country_code':
                $CountryId  =   $this->getCountryByCode($Data);
                $this->setSimple("country_id",$CountryId);
                break;     
            
            case 'statut':
                $this->setSimple($FieldName,$Data);
                break; 
            
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    
}
