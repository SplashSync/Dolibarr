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

    
    private $ExtraFields            =   Null; 
    private $ExtraPrefix            =   "options_"; 
        
    private static $TestedExtraTypes   =   array(
        "varchar"   => "phpunit_varchar",
        "text"      => "phpunit_text",
        "int"       => "phpunit_int",
        "bool"      => "phpunit_bool",
        "double"    => "phpunit_double",
        "price"     => "phpunit_price",
        "mail"      => "phpunit_mail",
        "phone"     => "phpunit_phone",
        "url"       => "phpunit_url",
        "date"      => "phpunit_date",
    );

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
            // Skip Incompatibles Types
            if ( empty($this->getSplashType($Type)) || $this->getIsHidden($Id)) {
                continue;
            } 
            //====================================================================//
            // Create Extra Field Definition
            $this->FieldsFactory()
                    ->Create($this->getSplashType( $Type ))
                    ->Identifier( $this->ExtraPrefix . $Id )
                    ->Name($this->getLabel($Id))
                    ->Group("Extra")
                    ->AddOption('maxLength' , 14)
                    ->MicroData("http://meta.schema.org/additionalType",$Id);    
            
            if ( $this->getIsRequired($Id) ) {
                $this->FieldsFactory()->isRequired();
            } 
            if ( $this->getIsReadOnly($Id) ) {
                $this->FieldsFactory()->ReadOnly();
            } 
            
        }
        
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
        if ( isset($this->Object->array_options) && array_key_exists($FieldName, $this->Object->array_options) ) {
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
            case SPL_T_PHONE:
            case SPL_T_URL:
            case SPL_T_EMAIL:
                $this->Out[$FieldName]  = $FieldData;
                break;            
            
            case SPL_T_DATE:
            case SPL_T_DATETIME:
                if ( !is_numeric($FieldData) ) {
                    $this->Out[$FieldName]  = $FieldData;
                } else {
                    $this->Out[$FieldName]  = dol_print_date($FieldData,'dayrfc');
                }
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
    protected function setExtraFields($FieldName,$Data) 
    {
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
            case SPL_T_PHONE:
            case SPL_T_URL:
            case SPL_T_EMAIL:
            case SPL_T_INT:
            case SPL_T_DOUBLE:
            case SPL_T_BOOL:
                if ( $CurrentData != $Data ) {
                    $this->Object->array_options[$FieldName] = $Data;
                    $this->needUpdate();
                } 
                break;            
                                
            case SPL_T_DATETIME:
                if ( $CurrentData != $Data ) {
                    date_default_timezone_set('UTC');
                    $this->Object->array_options[$FieldName] = $Data;
                    $this->needUpdate();
                } 
                break;         
            
            case SPL_T_PRICE:
                $PriceHT  = PricesTrait::Prices()->TaxExcluded( $Data );
                if ( $CurrentData != $PriceHT ) {
                    $this->Object->array_options[$FieldName] = $PriceHT;
                    $this->needUpdate();
                } 
                break;              
            
            default:
                return;
        }
        
        unset($this->In[$FieldName]);
    }
       
    /**
     *  @abstract     Load ExtraFields Definition
     */
    private function loadExtraFields($ElementType = Null)   {
        global $db;
        //====================================================================//
        // Load ExtraFields List        
        if( $this->ExtraFields == Null ) {
            require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $this->ExtraFields = new ExtraFields($db);
            $Key    =    is_null($ElementType) ? static::$ExtraFieldsType : $ElementType;
            $this->ExtraFields->fetch_name_optionals_label($Key, 1);      
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
        if ( empty($this->ExtraFields->attribute_type) ) {
            return array();
        }
        return $this->ExtraFields->attribute_type;
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
        
        $Id   =   $this->decodeType($FieldName);
        $Type =   $this->ExtraFields->attribute_type[$Id];        
        return $this->getSplashType($Type);
    }
    
    /**
     *  @abstract     Get ExtraField Label
     */
    private function getLabel( $Type )   {

        $this->loadExtraFields();
        return $this->ExtraFields->attribute_label[$Type];
    }
    
    /**
     *  @abstract     Get ExtraField Required Flag
     */
    private function getIsRequired( $Type )   {

        $this->loadExtraFields();
        return $this->ExtraFields->attribute_required[$Type];
    }    
    
    /**
     *  @abstract     Get ExtraField ReadOnly Flag
     */
    private function getIsReadOnly( $Type )   {

        $this->loadExtraFields();
        if ( isset($this->ExtraFields->attribute_computed) ) {
            return !empty($this->ExtraFields->attribute_computed[$Type]);
        }
        return False;
    }  
    
    /**
     *  @abstract     Get ExtraField Hidden Flag
     */
    private function getIsHidden( $Type )   {

        $this->loadExtraFields();
        return (bool) $this->ExtraFields->attribute_hidden[$Type];
    }  
    

    //====================================================================//
    // PhpUnit Tests Functions
    //====================================================================//
    
    /**
     *  @abstract     Create & Enable All Possible Extra Fields on Object Type
     * 
     *  @param        string    $ElementType            Object Type Identifier
     *  @param        bool      $Visible                ExtraField Visible / Hidden
     * 
     *  @return         none
     */
    protected static function configurePhpUnitExtraFields($ElementType, $Visible = True) 
    {
        global $db;
        //====================================================================//
        // Load ExtraFields List        
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $ExtraFields = new ExtraFields($db);
        //====================================================================//
        // Load array of extrafields for elementype = $this->table_element
        $ExtraFields->fetch_name_optionals_label($ElementType);
        
        //====================================================================//
        // Load Existing Types for this Element
        $ExistingTypes  =    $ExtraFields->attribute_type['type'];
        if ( empty($ExistingTypes) ) {
            $ExistingTypes  =     array();
        }

        //====================================================================//
        // Setup all Testing ExtraTypes
        foreach ( static::$TestedExtraTypes as $ExtraFieldType => $ExtraFieldName ) {
            
            //====================================================================//
            // ExtraField Already Exist => Update
            if ( in_array($ExtraFieldName , array_keys($ExistingTypes)) ) {
                $ExtraFields->update(
                        $ExtraFieldName, 
                        ucwords($ExtraFieldName, "_"), 
                        $ExtraFieldType, 
                        255,  
                        $ElementType, 
                        0, 0, 0, '', 1, '', 0, 
                        ($Visible ? 0:1));
            //====================================================================//
            // ExtraField Not Found = Create
            } else {
                $ExtraFields->addExtraField(
                        $ExtraFieldName, 
                        ucwords($ExtraFieldName, "_"), 
                        $ExtraFieldType, 
                        0, 255, 
                        $ElementType, 
                        0, 0, '', 0, 1, '', 0, 
                        ($Visible ? 0:1));
            }
        }

    }
    
}
