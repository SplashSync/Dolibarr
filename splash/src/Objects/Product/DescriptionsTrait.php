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
 * @abstract    Dolibarr Products Descriptions Fields
 */
trait DescriptionsTrait
{
    
    /**
    *   @abstract     Build Description Fields using FieldFactory
    */
    protected function buildDescFields()
    {
        global $conf,$langs;
        
        $GroupName  =   $langs->trans("Description");
        
        //====================================================================//
        // Description
        $this->fieldsFactory()
                ->Create($conf->global->MAIN_MULTILANGS ? SPL_T_MVARCHAR : SPL_T_VARCHAR)
                ->Identifier("description")
                ->Name($langs->trans("Description"))
                ->isListed()
                ->isLogged()
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "description");

        //====================================================================//
        // Note
            $this->fieldsFactory()->create(SPL_T_TEXT)
                ->Identifier("note")
                ->Name($langs->trans("Note"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "privatenote");
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getDescFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'description':
                $this->getMultilang($FieldName);
                break;
            
            case 'note':
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
    protected function setDescFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            case 'description':
                $this->setMultilang($FieldName, $Data);
                break;
                
            case 'note':
                $this->setSimple($FieldName, $Data);
                break;
            
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
