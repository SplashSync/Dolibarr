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
use Splash\Models\Objects\PricesTrait;

use ExtraFields;

/**
 * @abstract    Access to Dolibarr Extra Fields 
 */
trait ExtraFieldsTrait {

    
    private $ExtraFields; 
    private $ExtraPrefix        =   "options_"; 

    /**
     *  @abstract     Build ExtraFields using FieldFactory
     */
    protected function buildExtraFields()   {
        
        //====================================================================//
        // Load ExtraFields List        
        $this->loadExtraFields();
        //====================================================================//
        // Run All ExtraFields List        
        foreach( $this->getExtraTypes() as $Id => $Type) {
            //====================================================================//
            // Skipp Incompatibles Types
            if ( empty($this->getSplashType($Type)) ) {
                continue;
            } 
            //====================================================================//
            // Create Extra Field Definition
            $this->FieldsFactory()
                    ->Create($this->getSplashType( $Type ))
                    ->Identifier( $this->ExtraPrefix . $Id )
                    ->Name($this->getLabel($Id))
                    ->Group("Extra")
                    ->MicroData("http://meta.schema.org/additionalType",$Id);    
            
            if ( $this->getIsRequired($Id) ) {
                $this->FieldsFactory()->isRequired();
            } 
            
        }
        
    }
    
    /**
     *  @abstract     Load ExtraFields Definition
     */
    protected function loadExtraFields()   {
        global $db;
        if( $this->ExtraFields ) {
            return;
        }
        //====================================================================//
        // Load ExtraFields List        
        //====================================================================//
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $this->ExtraFields = new ExtraFields($db);
        //====================================================================//
        // Load array of extrafields for elementype = $this->table_element
        if (empty($this->ExtraFields->attributes[$this->ExtraFieldsType]['loaded']))  {
            $this->ExtraFields->fetch_name_optionals_label($this->ExtraFieldsType);
        }
    }
    
    /**
     *  @abstract     Encode Dolibarr ExtraFields Types Id
     */
    private function encodeType($Type)   {
        return $this->ExtraPrefix . $Type;
    }
    
    /**
     *  @abstract     Decode Dolibarr ExtraFields Types Id
     */
    private function decodeType($Type)   {
        if ( strpos($this->ExtraPrefix, $Type) == 0 ) {
            return substr($Type, strlen($this->ExtraPrefix));
        } 
        return Null;
    }
    
    /**
     *  @abstract     Get Dolibarr ExtraFields Types
     */
    private function getExtraTypes()   {
        $Types  =    $this->ExtraFields->attributes[$this->ExtraFieldsType]['type'];
        if ( empty($Types) ) {
            return array();
        }
        return $Types;
    }
    
    /**
     *  @abstract     Check if is Dolibarr ExtraFields Types
     */
    private function isExtraType($Type)   {

        if ( empty($this->getExtraTypes()) ) {
            return False;
        }
        if ( !in_array($this->decodeType($Type) , array_keys($this->getExtraTypes())) ) {
            return False;
        }
        return True;
    }    
    
    /**
     *  @abstract     Convert Dolibarr Field Type to SPlash Type
     */
    private function getSplashType( $Type )   {
        
        switch ( $Type ) {
            
            case "varchar": 
            case "password": 
                return SPL_T_VARCHAR;
                
            case "text": 
                return SPL_T_TEXT;
                
            case "int": 
                return SPL_T_INT;
                
            case "double": 
                return SPL_T_DOUBLE;
                
            case "date": 
                return SPL_T_DATE;
                
            case "datetime": 
                return SPL_T_DATETIME;
                
            case "boolean": 
            case "radio": 
            case "checkbox": 
                return SPL_T_BOOL;
                
            case "price": 
                return SPL_T_PRICE;
                
            case "phone": 
                return SPL_T_PHONE;
                
            case "mail": 
                return SPL_T_EMAIL;
                
            case "url": 
                return SPL_T_URL;
                
            case "link": 
            case "select": 
            case "sellist": 
            case "chkbxlst": 
            case "separate": 
                return Null;
                
        }
        
        return SPL_T_VARCHAR;
    }
    
    /**
     *  @abstract     Get Splash Type from ExtraFields Id
     */
    private function getSplashTypeFromId( $FieldName )   {
        
        $ExtraFieldId   =   $this->decodeType($FieldName);
        $ExtraFieldType =   $this->ExtraFields->attributes[$this->ExtraFieldsType]['type'][$ExtraFieldId];        
        return $this->getSplashType($ExtraFieldType);
    }
    
    /**
     *  @abstract     Get ExtraField Label
     */
    private function getLabel( $Type )   {

        $this->loadExtraFields();
        
        return $this->ExtraFields->attributes[$this->ExtraFieldsType]['label'][$Type];
    }
    
    /**
     *  @abstract     Get ExtraField Required Flag
     */
    private function getIsRequired( $Type )   {

        $this->loadExtraFields();
        
        return $this->ExtraFields->attributes[$this->ExtraFieldsType]['required'][$Type];
    }    
    
    /**
     *  @abstract     Get ExtraField ReadOnly Flag
     */
    private function getIsReadOnly( $Type )   {

        $this->loadExtraFields();
        
        return !empty($this->ExtraFields->attributes[$this->ExtraFieldsType]['computed'][$Type]);
    }  
    
    //====================================================================//
    // Fields Reading Functions
    //====================================================================//
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    protected function getExtraFields($Key,$FieldName)
    {
        global $conf;
        
        $this->loadExtraFields();
        //====================================================================//
        // Check is Extra Field
        if ( !$this->isExtraType($FieldName) ) {
            return;
        }          
        //====================================================================//
        // Extract Field Data
        if (array_key_existS($FieldName, $this->Object->array_options) ) {
            $FieldData  = $this->Object->array_options[$FieldName];
        } else {
            $FieldData  = Null;
        }
        //====================================================================//
        // READ Field Data
        switch ($this->getSplashTypeFromId($FieldName))
        {
            case SPL_T_VARCHAR:
            case SPL_T_TEXT:
            case SPL_T_DATE:
            case SPL_T_DATETIME:
            case SPL_T_PHONE:
            case SPL_T_URL:
            case SPL_T_EMAIL:
                $this->Out[$FieldName]  = $FieldData;
                break;            
            
            case SPL_T_INT:
                $this->Out[$FieldName]  = (int) $FieldData;
                break;            
                
            case SPL_T_DOUBLE:
                $this->Out[$FieldName]  = (double) $FieldData;
                break;            
                
            case SPL_T_BOOL:
                $this->Out[$FieldName]  = (bool) $FieldData;
                break;            
         
            case SPL_T_PRICE:
                $this->Out[$FieldName]  = PricesTrait::Prices()->Encode( (double) $FieldData, (double) 0 , Null, $conf->global->MAIN_MONNAIE);
                break;              
            
            default:
                return;
        }
        
        
        unset($this->In[$Key]);
    }
        
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//
      
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setExtraFields($FieldName,$Data) 
    {
        global $db;
        $this->loadExtraFields();
        //====================================================================//
        // Check is Extra Field
        if ( !$this->isExtraType($FieldName) ) {
            return;
        }          
        //====================================================================//
        // Extract Field Data
        if ( !empty($this->Object->array_options) && array_key_existS($FieldName, $this->Object->array_options) ) {
            $CurrentData  = $this->Object->array_options[$FieldName];
        } else {
            $CurrentData  = Null;
        }
        //====================================================================//
        // READ Field Data
        switch ($this->getSplashTypeFromId($FieldName))
        {
            case SPL_T_VARCHAR:
            case SPL_T_TEXT:
            case SPL_T_DATE:
//            case SPL_T_DATETIME:
            case SPL_T_PHONE:
            case SPL_T_URL:
            case SPL_T_EMAIL:
            case SPL_T_INT:
            case SPL_T_DOUBLE:
            case SPL_T_BOOL:
                if ( $CurrentData != $Data ) {
                    $this->Object->array_options[$FieldName] = $Data;
                    $this->Object->updateExtraField($this->decodeType($FieldName));
                } 
                break;            
                                
            case SPL_T_DATETIME:
                if ( $CurrentData != $Data ) {
                    date_default_timezone_set('UTC');
                    $this->Object->array_options[$FieldName] = $Data;
                    $this->Object->updateExtraField($this->decodeType($FieldName));
                } 
                break;         
            
            case SPL_T_PRICE:
                $PriceHT  = PricesTrait::Prices()->TaxExcluded( $Data );
                if ( $CurrentData != $PriceHT ) {
                    $this->Object->array_options[$FieldName] = $PriceHT;
                    $this->Object->updateExtraField($this->decodeType($FieldName));
                } 
                break;              
            
            default:
                return;
        }
        
        unset($this->In[$FieldName]);
    }
    
}
