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

namespace Splash\Local\Objects\ThirdParty;

/**
 * @abstract    Dolibarr ThirdParty Main Fields
 */
trait MainTrait
{

    
    /**
     *  @abstract     Build Address Fields using FieldFactory
     */
    protected function buildMainFields()
    {
        global $langs;
        
        //====================================================================//
        // Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
                ->Identifier("phone")
                ->Name($langs->trans("Phone"))
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress", "telephone")
                ->isListed();

        //====================================================================//
        // Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name($langs->trans("Email"))
                ->MicroData("http://schema.org/ContactPoint", "email")
                ->isLogged()
                ->isListed();
        
        //====================================================================//
        // WebSite
        $this->fieldsFactory()->create(SPL_T_URL)
                ->Identifier("url")
                ->Name($langs->trans("PublicUrl"))
                ->MicroData("http://schema.org/Organization", "url");

        //====================================================================//
        // Id Professionnal 1 SIREN
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("idprof1")
                ->Group("ID")
                ->Name($langs->trans("ProfId1Short"))
                ->MicroData("http://schema.org/Organization", "duns");
        
        //====================================================================//
        // Id Professionnal 2 SIRET
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("idprof2")
                ->Group("ID")
                ->Name($langs->trans("ProfId2Short"))
                ->MicroData("http://schema.org/Organization", "taxID");

        //====================================================================//
        // Id Professionnal 3 APE
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("idprof3")
                ->Group("ID")
                ->Name($langs->trans("ProfId3Short"))
                ->MicroData("http://schema.org/Organization", "naics");
        
        //====================================================================//
        // Id Professionnal 4 RCS
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("idprof4")
                ->Group("ID")
                ->Name($langs->trans("ProfId4Short"))
                ->MicroData("http://schema.org/Organization", "isicV4");
        
        //====================================================================//
        // Id Professionnal 5
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("idprof5")
                ->Group("ID")
                ->Name($langs->trans("ProfId5Short"));
        
        
        //====================================================================//
        // Id Professionnal 6
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("idprof6")
                ->Name($langs->trans("ProfId6Short"))
                ->Group("ID")
                ->MicroData("http://splashync.com/schemas", "ObjectId");

        //====================================================================//
        // VAT Number
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("tva_intra")
                ->Name($langs->trans("VATIntra"))
                ->Group("ID")
                ->AddOption('maxLength', 20)
                ->MicroData("http://schema.org/Organization", "vatID");
        
        return;
    }
    
        
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     *
     *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getMainFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'phone':
            case 'email':
            case 'url':
            case 'idprof1':
            case 'idprof2':
            case 'idprof3':
            case 'idprof4':
            case 'idprof5':
            case 'idprof6':
            case 'tva_intra':
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
     *
     *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setMainFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Writtings
            case 'phone':
            case 'email':
            case 'url':
            case 'idprof1':
            case 'idprof2':
            case 'idprof3':
            case 'idprof4':
            case 'idprof5':
            case 'idprof6':
            case 'tva_intra':
                $this->setSimple($FieldName, $Data);
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
