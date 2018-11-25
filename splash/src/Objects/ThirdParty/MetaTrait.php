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

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr ThirdParty Meta Fields
 */
trait MetaTrait
{

    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaFields()
    {
        global $langs;
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("status")
                ->Name($langs->trans("Active"))
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization", "active")
                ->isListed();
        
        if (Splash::local()->dolVersionCmp("3.6.0") >= 0) {
            //====================================================================//
            // isProspect
            $this->fieldsFactory()->create(SPL_T_BOOL)
                    ->Identifier("prospect")
                    ->Name($langs->trans("Prospect"))
                    ->Group("Meta")
                    ->MicroData("http://schema.org/Organization", "prospect");
        }

        //====================================================================//
        // isCustomer
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("client")
                ->Name($langs->trans("Customer"))
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization", "customer");

        //====================================================================//
        // isSupplier
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("fournisseur")
                ->Name($langs->trans("Supplier"))
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization", "supplier");

        
        //====================================================================//
        // isVAT
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("tva_assuj")
                ->Name($langs->trans("VATIsUsed"))
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization", "UseVAT");
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMetaFields($Key, $FieldName)
    {

        
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // STRUCTURAL INFORMATIONS
            //====================================================================//

            case 'status':
            case 'tva_assuj':
            case 'fournisseur':
                $this->getSimpleBool($FieldName);
                break;

            case 'client':
                $this->getSimpleBit('client', 0);
                break;

            case 'prospect':
                $this->object->prospect     =   $this->object->client;
                $this->getSimpleBit('prospect', 1);
                break;

            default:
                return;
        }
        
        unset($this->in[$Key]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setMetaFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Writtings
            case 'status':
            case 'tva_assuj':
            case 'fournisseur':
                $this->setSimple($FieldName, $Data);
                break;
                
            case 'client':
                $this->setSimpleBit('client', 0, $Data);
                break;

            case 'prospect':
                $this->setSimpleBit('client', 1, $Data);
                break;
                
            default:
                return;
        }
        unset($this->in[$FieldName]);
    }
}
