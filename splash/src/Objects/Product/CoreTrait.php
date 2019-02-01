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
 * @abstract    Dolibarr Products Core Fields (Required)
 */
trait CoreTrait
{

    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    protected function buildCoreFields()
    {
        global $conf, $langs;
        $groupName  =   $langs->trans("Description");

        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("ref")
            ->Name($langs->trans("ProductRef"))
            ->isListed()
            ->MicroData("http://schema.org/Product", "model")
            ->isLogged()
            ->isRequired();
        
        //====================================================================//
        // Name (Default Language)
        $this->fieldsFactory()
            ->Create(SPL_T_VARCHAR)
            ->Identifier("label")
            ->Name($langs->trans("ProductLabel"))
            ->isListed()
            ->isLogged()
            ->Group($groupName)
            ->addOption('language', $langs->getDefaultLang())                
            ->MicroData("http://schema.org/Product", "name")
            ->isRequired();
        
        //====================================================================//
        // Description (Default Language)
        $this->fieldsFactory()
            ->Create(SPL_T_VARCHAR)
            ->Identifier("description")
            ->Name($langs->trans("Description"))
            ->isListed()
            ->isLogged()
            ->Group($groupName)
            ->addOption('language', $langs->getDefaultLang())                
            ->MicroData("http://schema.org/Product", "description");

        //====================================================================//
        // Note
        $this->fieldsFactory()->create(SPL_T_TEXT)
            ->Identifier("note")
            ->Name($langs->trans("Note"))
            ->Group($groupName)
            ->addOption('language', $langs->getDefaultLang())                
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
    protected function getCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
                $this->getSimple($FieldName);
                break;
            
            case 'label':
            case 'description':
            case 'note':
                $this->getSimple($FieldName);
                
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
    protected function setCoreFields($FieldName, $Data)
    {
        global $langs;
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Writtings
            case 'ref':
                // Update Path of Object Documents In Database
                $this->updateFilesPath("produit", $this->object->ref, $Data);
                $this->setSimple($FieldName, $Data);
                break;
            
            case 'label':
            case 'description':
            case 'note':
                $this->setSimple($FieldName, $Data);
                $this->setMultilangContent($FieldName, $langs->getDefaultLang(), $Data);
                break;
                
            default:
                return;
        }
        unset($this->in[$FieldName]);
    }
}
