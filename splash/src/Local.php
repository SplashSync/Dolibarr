<?php
/*
 * Copyright (C) 2011-2014  Bernard Paquier       <bernard.paquier@gmail.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * 
 *  \Id 	$Id: osws-local-Main.class.php 136 2014-10-12 22:33:28Z Nanard33 $
 *  \version    $Revision: 136 $
 *  \date       $LastChangedDate: 2014-10-13 00:33:28 +0200 (lun. 13 oct. 2014) $ 
 *  \ingroup    Splash - OpenSource Synchronisation Service
 *  \brief      Core Local Server Definition Class
 *  \class      SplashLocal
 *  \remarks	Designed for Splash Module - Dolibar ERP Version  
*/

namespace Splash\Local;

use Splash\Core\SplashCore      as Splash;

use User;
use ArrayObject;

//====================================================================//
//  INCLUDES
//====================================================================//


//====================================================================//
//  CONSTANTS DEFINITION
//====================================================================//

//====================================================================//
//  CLASS DEFINITION
//====================================================================//

//====================================================================//
// *******************************************************************//
// *******************************************************************//
//====================================================================//
// 
//  MAIN CORE FUNCTION
//  
//  This Class includes all commons Local functions
//    
//====================================================================//
// *******************************************************************//
// *******************************************************************//
//====================================================================//
    
 /**
 *	\class      SplashLocal
 *	\brief      Local Core Management Class
 */
class Local 
{
    //====================================================================//
    // General Class Variables	
    // Place Here Any SPECIFIC Variable for your Core Module Class
    //====================================================================//

    //====================================================================//
    // Class Constructor
    //====================================================================//
        
    /**
     *      @abstract       Class Constructor (Used only if localy necessary)
     *      @return         int                     0 if KO, >0 if OK
     */
    function __construct()
    {
        //====================================================================//
        // Place Here Any SPECIFIC Initialisation Code
        //====================================================================//
        
        return True;
    }

//====================================================================//
// *******************************************************************//
//  MANDATORY CORE MODULE LOCAL FUNCTIONS
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract       Return Local Server Parameters as Array
     *                      
     *      THIS FUNCTION IS MANDATORY 
     * 
     *      This function called on each initialization of the module
     * 
     *      Result must be an array including mandatory parameters as strings
     *         ["WsIdentifier"]         =>>  Name of Module Default Language
     *         ["WsEncryptionKey"]      =>>  Name of Module Default Language
     *         ["DefaultLanguage"]      =>>  Name of Module Default Language
     * 
     *      @return         array       $parameters
     */
    public static function Parameters()
    {
        global $langs;
        
        $Parameters       =     array();        
        //====================================================================//
        // Server Identification Parameters
        $Parameters["WsIdentifier"]         =   self::getParameter("SPLASH_WS_ID");
        $Parameters["WsEncryptionKey"]      =   self::getParameter("SPLASH_WS_KEY");
        //====================================================================//
        // If Debug Mode => Allow Overide of Server Host Address
        if ( (self::getParameter("SPLASH_WS_EXPERT")) && !empty(self::getParameter("SPLASH_WS_HOST")) ) {
            $Parameters["WsHost"]           =   self::getParameter("SPLASH_WS_HOST");
        }
        //====================================================================//
        // Overide Module Parameters with Local User Selected Lang
        if ( self::getParameter("SPLASH_LANG") ) {
            $Parameters["DefaultLanguage"]      =   self::getParameter("SPLASH_LANG");
        //====================================================================//
        // Overide Module Parameters with Local Default System Lang
        } elseif ( ($langs) && $langs->getDefaultLang() ) {
            $Parameters["DefaultLanguage"]      =   $langs->getDefaultLang();
        } 
        //====================================================================//
        // Overide Module Local Name in Logs
        $Parameters["localname"]        =   self::getParameter("MAIN_INFO_SOCIETE_NOM");
        
        return $Parameters;
    }    
    
    /**
     *      @abstract       Include Local Includes Files
     * 
     *      Include here any local files required by local functions. 
     *      This Function is called each time the module is loaded 
     * 
     *      There may be differents scenarios depending if module is 
     *      loaded as a library or as a NuSOAP Server. 
     * 
     *      This is triggered by global constant SPLASH_SERVER_MODE.
     * 
     *      @return         bool                     
     */
    public function Includes()
    {

        //====================================================================//
        // When Library is called in server mode ONLY
        //====================================================================//
        if ( SPLASH_SERVER_MODE )
        {
            define('NOCSRFCHECK',1);	// This is Webservice Access. We must be able to go on it from outside. 
        }

        //====================================================================//
        // When Library is called in client mode ONLY
        //====================================================================//
        else
        {
            // NOTHING TO DO 
        }


        //====================================================================//
        // When Library is called in both clinet & server mode
        //====================================================================//

        // NOTHING TO DO         
        
        if (!defined("DOL_DOCUMENT_ROOT")) {
        
            global $db,$langs,$conf,$user,$hookmanager;

            //====================================================================//
            // Initiate Dolibarr Global Envirement Variables
            require_once( $this->getDolibarrRoot() . "/master.inc.php");           
           
            //====================================================================//
            // Splash Modules Constant Definition
            require_once(DOL_DOCUMENT_ROOT.'/splash/_conf/defines.inc.php'); 
            
            //====================================================================//
            // Load Default Language
            $this->LoadDefaultLanguage();      
            
            //====================================================================//
            // Load Default User
            $this->LoadLocalUser();           
        
        }
        
        return True;
    }      
           
    /**
     *      @abstract       Return Local Server Self Test Result
     *                      
     *      THIS FUNCTION IS MANDATORY 
     * 
     *      This function called during Server Validation Process
     * 
     *      We recommand using this function to validate all functions or parameters
     *      that may be required by Objects, Widgets or any other module specific action.
     * 
     *      Use Module Logging system & translation tools to return test results Logs
     * 
     *      @return         bool    global test result
     */
    public static function SelfTest()
    {
        global $conf,$langs;

        //====================================================================//
        //  Load Local Translation File
        Splash::Translator()->Load("main@local");          
        $langs->load("errors");
        
        //====================================================================//
        //  Verify - Server Identifier Given
        if ( !isset($conf->global->SPLASH_WS_ID) || empty($conf->global->SPLASH_WS_ID) ) {
            return Splash::Log()->Err("ErrSelfTestNoWsId");
        }        
                
        //====================================================================//
        //  Verify - Server Encrypt Key Given
        if ( !isset($conf->global->SPLASH_WS_KEY) || empty($conf->global->SPLASH_WS_KEY) ) {
            return Splash::Log()->Err("ErrSelfTestNoWsKey");
        }        
        
        //====================================================================//
        //  Verify - User Selected
        if ( !isset($conf->global->SPLASH_USER) || ($conf->global->SPLASH_USER <= 0) ) {
            return Splash::Log()->Err("ErrSelfTestNoUser");
        }        
        
        //====================================================================//
        //  Verify - Stock Selected
        if ( !isset($conf->global->SPLASH_STOCK) || ($conf->global->SPLASH_STOCK <= 0) ) {
            return Splash::Log()->Err("ErrSelfTestNoStock");
        }        
        
        //====================================================================//
        // Check if company name is defined (first install)
        if (empty($conf->global->MAIN_INFO_SOCIETE_NOM) || empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY))
        {
            return Splash::Log()->Err($langs->trans("WarningMandatorySetupNotComplete"));
        }

        Splash::Log()->Msg("MsgSelfTestOk");
        return True;
    }       
    
    /**
     *  @abstract   Update Server Informations with local Data
     * 
     *  @param     arrayobject  $Informations   Informations Inputs
     * 
     *  @return     arrayobject
     */
    public function Informations($Informations)
    {
        //====================================================================//
        // Init Response Object
        $Response = $Informations;

        //====================================================================//
        // Company Informations
        $Response->company          =   self::getParameter("MAIN_INFO_SOCIETE_NOM", "...");
        $Response->address          =   self::getParameter("MAIN_INFO_SOCIETE_ADDRESS", "...");
        $Response->zip              =   self::getParameter("MAIN_INFO_SOCIETE_ZIP", "...");
        $Response->town             =   self::getParameter("MAIN_INFO_SOCIETE_TOWN", "...");
        $Response->country          =   self::getParameter("MAIN_INFO_SOCIETE_COUNTRY", "...");
        $Response->www              =   self::getParameter("MAIN_INFO_SOCIETE_WEB", "...");
        $Response->email            =   self::getParameter("MAIN_INFO_SOCIETE_MAIL", "...");
        $Response->phone            =   self::getParameter("MAIN_INFO_SOCIETE_TEL", "...");
        
        //====================================================================//
        // Server Logo & Images
        $Response->icoraw           =   Splash::File()->ReadFileContents(DOL_DOCUMENT_ROOT . "/favicon.ico");
        $Response->logourl          =   "http://www.dolibarr.org/images/stories/dolibarr_256x256.png";
        
        //====================================================================//
        // Server Informations
        $Response->servertype       =   "Dolibarr ERP";
        $Response->serverurl        =   DOL_MAIN_URL_ROOT;
        
        return $Response;
    }    
    
    
    
    

    

    
    
    /**
     *      @abstract       Return lost of all active langues code
     *
     *      @return     array       $list           List Of Available Languages
     *                              $list["name"]   Language Name	
     *                              $list["code"]   Language code (en_US, en_AU, fr_FR, ...)
     */    
    public function LangsList()
    {
        global $langs;
        //====================================================================//        
        // Read Native Multilangs Descriptions
        $Orginal = $langs->get_available_languages();
        //====================================================================//
        // Encode Language Code & Names
        $OsWs_Langs = array();
        foreach($Orginal as $key => $lang) {
            $OsWs_Langs[] =   array( "name" => $lang , "code" => $key);
        }
        return $OsWs_Langs;
    }         
    
//====================================================================//
// *******************************************************************//
// Place Here Any SPECIFIC or COMMON Local Functions
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract       Initiate Local Request User if not already defined
     *      @param          array       $cfg       Loacal Parameters Array
     *      @return         int                     0 if KO, >0 if OK
     */
    public function LoadLocalUser()
    {
        global $conf,$db,$user;
        
        //====================================================================//
        // CHECK USER ALREADY LOADED
        //====================================================================//
        if ( isset($user->id) && !empty($user->id) )
        {
            return True;
        }
        
        //====================================================================//
        // LOAD USER FROM DATABASE
        //====================================================================//
        
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

        //====================================================================//
        // Read Local Configuration
        $userId = isset($conf->global->SPLASH_USER)?$conf->global->SPLASH_USER:Null;
        if ( empty($userId) ) {
            return Splash::Log()->Err("Local - Dolibarr Error : No Local User Defined.");
        }
        //====================================================================//
        // Load Local User

        $user = new User($db);
        if ($user->fetch($userId) != 1) {
            Splash::Log()->Err("Local : Unable to Load Local User");
            return Splash::Log()->Err("Local - Dolibarr Error : " . $user->error );
        }
        
        //====================================================================//
        // Load Local User Rights
        if (!$user->all_permissions_are_loaded) {
            $user->getrights(); 
        }
    }
    
    /**
     *      @abstract       Initiate Local Request User if not already defined
     *      @param          array       $cfg       Loacal Parameters Array
     *      @return         int                     0 if KO, >0 if OK
     */
    public function LoadDefaultLanguage()
    {
        global $langs;
        //====================================================================//
        // Load Default Language
        //====================================================================//
        if ( !empty(Splash::Configuration()->DefaultLanguage) ) {
            $langs->setDefaultLang(Splash::Configuration()->DefaultLanguage);
        }
    }
    
    /**
     *      @abstract       Update Multilangual Fields of an Object
     * 
     *      @param          object      $Object     Pointer to Dolibarr Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @param          array       $Data       New Multilangual Contents
     * 
     *      @return         bool                    Is Object Update needed or Not
     */
    public function setMultilang($Object=Null,$key=Null,$Data=Null)
    {
        global $langs,$conf;
        //====================================================================//        
        // Safety Check 
        //====================================================================//        
        if ( is_null($Data) ) {
            return False;
        }
        if ( !is_array($Data) && !is_a($Data,"ArrayObject") ) {
            return False;
        }
        //====================================================================//        
        // Single Language Descriptions
        if (!$conf->global->MAIN_MULTILANGS) {
            if ( $Object->$key !== $Data) {             
                $Object->$key = $Data; 
                return True;
            }
            return False;
        }
        //====================================================================//        
        // Update Native Multilangs Descriptions
        //====================================================================//        
        $UpdateRequired = False;
        //====================================================================//        
        // Create or Update Multilangs Fields
        foreach ($Data as $IsoCode => $Content) {
            //====================================================================//        
            // Create This Translation if empty
            if ( !isset ($Object->multilangs[$IsoCode]) ) {
                $Object->multilangs[$IsoCode] = array();
            }
            //====================================================================//        
            // Update Contents
            //====================================================================//        
            if ( $Object->multilangs[$IsoCode][$key] !== $Content) {             
                $Object->multilangs[$IsoCode][$key] = $Content;
                $UpdateRequired = True;
            }
            //====================================================================//        
            // Duplicate Contents to Default language if needed
            if ( ($IsoCode == $langs->getDefaultLang()) && property_exists(get_class($Object),$key)) {
                $Object->$key = $Content;
            }
        }
        return $UpdateRequired;
    }
    
    /**
     *      @abstract       Update Default Values of a Multilangual Fields of an Object
     *      @param          object      $Object     Pointer to Dolibarr Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @param          array       $Text       New Contents
     *      @return         int                     0 if no update needed, 1 if update needed
     */
    public function setDefaultlang($Object=Null,$key=Null,$Text=Null)
    {
        global $langs,$conf,$update;
        //====================================================================//        
        // Native Multilangs Descriptions
        if ($conf->global->MAIN_MULTILANGS) {
            // Get Default Language
            $DfLang = $langs->getDefaultLang();
            // If Needed, Update Fields
            if ( $Object->multilangs[$DfLang][$key] !== $Text) {             
                $Object->multilangs[$DfLang][$key] = $Text;
                $update++;
            }
        }
        
        if ( $Object->$key !== $Text) {             
            $Object->$key = $Text; 
            $update++;
        }
        return $Object;
    }    
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *      @param          object      $Object     Pointer to Dolibarr Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @return         int                     0 if KO, 1 if OK
     */
    public function getMultilang(&$Object=Null,$key=Null)
    {
        global $langs,$conf;
        
        //====================================================================//        
        // Native Multilangs Descriptions
        if ($conf->global->MAIN_MULTILANGS) {
            
            //====================================================================//        
            // If Multilang Contents doesn't exists
            if (count($Object->multilangs) == 0)    {
                // Get Default Language
                $DfLang = $langs->getDefaultLang();
                return array( $DfLang => $Object->$key );    
            }
            
            //====================================================================//        
            /// Else read Multilang contents 
            $Data = array(); 
            foreach ($Object->multilangs as $IsoCode => $Content) {
                if (isset ($Content[$key] )) {
                    $Data[$IsoCode] = $Content[$key];
                }
            }
            
        //====================================================================//        
        // Single Language Descriptions
        } else {
            $Data = $Object->$key;     
        }
         return $Data;
    }
        
    /**
    *   @abstract     Return Product Image Array from Dolibarr Object Class
    *   @param        array   $Object           Dolibarr Products Class  
    *   @param        array   $list             Return complete List or first Image Only    
    */
    public function getObjectImgArray($Object,$list=0) 
    {
        global $conf;
        //====================================================================//
        // Load Images Directory
        $ObjectElement      =  $Object->element; 
        $ObjectEntity       =  $Object->entity; 
        
        $ObjectIntDir       =  $conf->$ObjectElement->multidir_output[$ObjectEntity];
        $ObjectExtDir       = '/' . get_exdir($Object->id,2) . $Object->id ."/photos/";
        if (empty($ObjectIntDir)) {
            return OSWS_KO;
        } else {
            $ObjectImagesDir = $ObjectIntDir . $ObjectExtDir;
        }
        // Create External Image View Url
        $ObjectUrlDir = DOL_MAIN_URL_ROOT.'/viewimage.php?modulepart='.$ObjectElement.'&entity='.$ObjectEntity.'&file=';
        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   $Object->liste_photos($ObjectImagesDir,0);

        //====================================================================//
        // Create Images List
        if ( count ($ObjectImagesList) ) {
            $Images = array();
            foreach ($ObjectImagesList as $ObjectImage) {
                $Image = array();
                // Image Full Path
                $Image["path"]          = $ObjectImagesDir;
                // Image Name
                $info = pathinfo($ObjectExtDir.$ObjectImage["photo"]);
                if (! is_array($info) ) {
                    $Image["name"]          = "Product Picture " . (count ($Images)+1);
                } else {
                    $Image["name"]          = $info["filename"];
                }
                // Image Filename
                $Image["filename"]      = $ObjectImage["photo"];
                // Image Publics Url
                $Image["url"]           = $ObjectUrlDir . urlencode($ObjectExtDir.$ObjectImage["photo"]);
                $Image["t_url"]         = $ObjectUrlDir . urlencode($ObjectExtDir.$ObjectImage["photo_vignette"]);
                // Images Informations
                $Dim                    = dol_getImageSize($ObjectImagesDir.$ObjectImage["photo"]);
                if ( !empty($Dim)) {
                    $Image["width"]     = $Dim["width"];
                    $Image["height"]    = $Dim["height"];
                } else {
                    $Image["width"]     = $Image["height"] = "Unknown";
                }
                $Image["md5"]           = md5_file($ObjectImagesDir.$ObjectImage["photo"]);
                $Image["size"]          = filesize($ObjectImagesDir.$ObjectImage["photo"]);
                //====================================================================//
                // If NOT in Images List Mode, Return First Image
                if ( $list == 0 ) {
                    return $Image;
                }
                $Images[] = array( 'picture'  => $Image );  
            }
            return $Images;
        }
        return Null;
    }
     
    /**
    *   @abstract     Return Product Image Array from Dolibarr Object Class
    *   @param        array   $Object           Dolibarr Object Class  
    *   @param        array   $InList           Input Image List for Update    
    */
    public function setObjectImgArray($Object,$In=0) 
    {
        global $conf;
        //====================================================================//
        // Safety Check
        if (is_nan($Object->id) && ($Object->id > 0)) {
            return Splash::Log()->Err("WrongObjectId");
        }
        //====================================================================//
        // Load Images Directory
        $ObjectElement      =  $Object->element; 
        $ObjectEntity       =  $Object->entity; 
        $ObjectIntDir       =  $conf->$ObjectElement->multidir_output[$ObjectEntity];
        $ObjectExtDir       = '/' . get_exdir($Object->id,2) . $Object->id ."/photos/";
        if (empty($ObjectIntDir)) {
            return OSWS_KO;
        } else {
            $ObjectImagesDir = $ObjectIntDir . $ObjectExtDir;
        }
        //====================================================================//
        // Load Object Images List
        $ImageList = $this->getObjectImgArray($Object,1);
        //====================================================================//
        // UPDATE IMAGES LIST
        //====================================================================//
        // If Given List Is Empty => Clear Local List
        if ( empty($In) && is_array($ImageList) ) {
            foreach ($ImageList as $Image) {
                $Object->delete_photo($Image["path"].$Image["filename"]);
            }
            return OSWS_OK;
        }
        //====================================================================//
        // Safety Check
        if (!is_array($In)) {
            return Splash::Log()->Err("WrongImageList");
        }
        //====================================================================//
        // Given List Is Not Empty
        //====================================================================//
        // Walk Through Input List
        foreach ($In as $InValue) {
            $InImage = $InValue["picture"];
            $found = 0;
            //====================================================================//
            // Skip empty image In Current List
            if ( empty ($InImage) )     { continue; } 
            //====================================================================//
            // Search For Image In Current List
            if ( !empty ($ImageList) ) {
                foreach ($ImageList as $key => $Value) {
                    $Image = $Value["picture"];
                    // If Object Found, Unset from Current List
                    if ( $InImage["md5"] === $Image["md5"] ) {
                        unset ($ImageList[$key]);
                        $found = 1;
                        break;
                    }
                }
            }
            //====================================================================//
            // If Not found, Add this object to list
            if ( !$found ) {
                $newfile    =   Splash::ReadFile($InImage["path"],$InImage["filename"],OSWS_DO_NOW);
                if ( $newfile != OSWS_KO ) {
                    Splash::WriteFile($ObjectImagesDir,$newfile["filename"],$newfile["md5"],$newfile["raw"],OSWS_DO_LOCAL);
                    $Object->add_thumb($ObjectImagesDir.$newfile["filename"]);
                } else {
                    Splash::Log()->Err("FileNotUploaded");
                }
            }
        }
        // Remove All remaining Images In Current List
        if ( !is_array($ImageList) ) {         
            return OSWS_OK;
        }
        foreach ($ImageList as $key => $Value) {
            $Image = $Value["picture"];           
            $Object->delete_photo($Image["path"].$Image["filename"]);
        }
        return OSWS_OK;
    }
       
    /**
    *   @abstract     Return Product Image Array from Dolibarr Object Class
    *   @param        array   $Object           Dolibarr Object Class  
    *   @param        array   $InList           Input Image List for Update    
    *   @param        array   $LocalList        Local Image List tO Update    
    */
    public static function SyncImg($Object,$InList=0,$LocalList=0) 
    {
        global $conf;
        //====================================================================//
        // Load Images Directory
        $ObjectElement      =  $Object->element; 
        $ObjectEntity       =  $Object->entity; 
        
        $ObjectIntDir       =  $conf->$ObjectElement->multidir_output[$ObjectEntity];
        $ObjectExtDir       = '/' . get_exdir($Object->id,2) . $Object->id ."/photos/";
        if (empty($ObjectIntDir)) {
            return OSWS_KO;
        } else {
            $ObjectImagesDir = $ObjectIntDir . $ObjectExtDir;
        }
        // Create External Image View Url
        $ObjectUrlDir = DOL_MAIN_URL_ROOT.'/viewimage.php?modulepart='.$ObjectElement.'&entity='.$ObjectEntity.'&file=';
        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   $Object->liste_photos($ObjectImagesDir,0);

        //====================================================================//
        // Create Images List
        $Images = array();
        if ( count ($ObjectImagesList) ) {
            foreach ($ObjectImagesList as $ObjectImage) {
                $Image = array();
                // Image Full Path
                $Image["path"]        = $ObjectImagesDir;
                // Image Name
                $Image["name"]        = "Product Picture " . (count ($Images)+1);
                // Image Filename
                $Image["filename"]    = $ObjectImage["photo"];
                // Image Publics Url
                $Image["url"]         = $ObjectUrlDir . urlencode($ObjectExtDir.$ObjectImage["photo"]);
                $Image["t_url"]       = $ObjectUrlDir . urlencode($ObjectExtDir.$ObjectImage["photo_vignette"]);
                // Images Informations
                $Dim                = dol_getImageSize($ObjectImagesDir.$ObjectImage["photo"]);
                if ( !empty($Dim)) {
                    $Image["width"]       = $Dim["width"];
                    $Image["height"]      = $Dim["height"];
                } else {
                    $Image["width"] = $Image["height"] = "Unknown";
                }
                $Image["size"]        = filesize($ObjectImagesDir.$ObjectImage["photo"]);
                //====================================================================//
                // If NOT in Images List Mode, Return First Image
                if ( $list == 0 ) {
                    return $Image;
                }
                $Images[] = array( 'picture'  => $Image );  
            }
        }
        return $Images;
    }
     
    /**
     *      @abstract   Compare Two Float Value and 
     *      @return     string  $code         Country Iso Code
     *      @return     int                   Country Dolibarr Id, else 0
     */
    function getCountryByCode($code)
    {   
        global $db;
        if (self::DolVersionCmp("3.7.0") >= 0) {
            require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';
            $pays = new \Ccountry($db);
            if ( $pays->fetch(Null,$code) > 0 ) {
                return $pays->id; 
            }
        } else {
            require_once DOL_DOCUMENT_ROOT.'/core/class/cpays.class.php';
            $pays = new \Cpays($db);
            if ( $pays->fetch(Null,$code) > 0 ) {
                return $pays->id; 
            }
        }
        return False;
    }
    
    /**
     *      @abstract   Search For State Dolibarr Id using State Code & Country Id
     *      @param      string  $StateCode          State Iso Code
     *      @return     string  $CountryId          Country Dolibarr Id
     * 
     *      @return     int                   State Dolibarr Id, else 0
     */  


    function getStateByCode($StateCode,$CountryId)
    {   
        global $db;
        
        if ( empty($CountryId) ) {
            return False;
        } 
        
        //====================================================================//
        // Select State Id &œ Code
        $sql = "SELECT d.rowid as id, d.code_departement as code";
        $sql .= " FROM ".MAIN_DB_PREFIX ."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_pays as p";
        //====================================================================//
        // Search by Country & State Code
        $sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid";
        $sql .= " AND p.rowid = '".$CountryId."'";
        $sql .= " AND d.code_departement = '".$StateCode."'";
        
        //====================================================================//
        // Execute final request
        $resql = $db->query($sql);        
        if (empty($resql))  {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__, $db->lasterror());
        }
        
        if ( $db->num_rows($resql) == 1 ) {
            return $db->fetch_object($resql)->id;
        }
                
//        Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__," SQL : " . $sql);
//        Splash::Log()->www(" RESP : " , $db->fetch_object($resql)->id );
        return False;
    }    
    
//====================================================================//
//  UNIT CONVERTER
//====================================================================//

/**
    *  Convert Weight form all units to kg. 
    * 
    *  @param      float    $weight     Weight Value
    *  @param      int      $unit       Weight Unit
    *  @return     float                Weight Value in kg
*/
    public static function C_Weight($weight,$unit)
    { 		
        if ( $unit      == "-6")    return $weight * 1e-6;               // mg
        elseif ( $unit  == "-3")    return $weight * 1e-3;               // g
        elseif ( $unit  == "0")     return $weight;                     // kg
        elseif ( $unit  == "3")     return $weight * 1e3;               // Tonne
        elseif ( $unit  == "99")    return $weight * 0.45359237;        // livre
        return 0;
    }

/**
    *  Return Normalized Weight form raw kg value. 
    * 
    *  @param      float    $weight     Weight Raw Value
    *  @return     arrayobject          $r->weight , $r->weight_units , $r->print, $r->raw
*/
    public static function N_Weight($weight)
    { 		
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

        $r = new ArrayObject();
        if ( $weight    >= 1e3 )    {    
            $r->weight              =   $weight * 1e-3;                 // Tonne
            $r->weight_units        =   "3";                            // Tonne
        }
        elseif ( $weight    >= 1 )    {    
            $r->weight              =   $weight;                        // kg
            $r->weight_units        =   "0";                            // kg
        }
        elseif ( $weight    >= 1e-3 )    {    
            $r->weight              =   $weight * 1e3;                  // g
            $r->weight_units        =   "-3";                           // g
        }
        elseif ( $weight    >= 1e-6 )    {    
            $r->weight              =   $weight * 1e6;                  // mg
            $r->weight_units        =   "-6";                           // mg
        }
        $r->print = $r->weight." ".measuring_units_string($r->weight_units,"weight");
        $r->raw =   $weight;
        return $r;
    }

    
/**
    *  Convert Lenght form all units to m. 
    * 
    *  @param      float    $length     Length Value
    *  @param      int      $unit       Length Unit
    *  @return     float                Length Value in m
*/
    public static function C_Length($length,$unit)
    { 		
        if ( $unit      == "-3")    return $length / 1e3;              // mm
        elseif ( $unit  == "-2")    return $length / 1e2;               // cm
        elseif ( $unit  == "-1")    return $length / 10;                // dm
        elseif ( $unit  == "0")     return $length;                     // m
        elseif ( $unit  == "98")    return $length * 0.3048;            // foot
        elseif ( $unit  == "99")    return $length * 0.0254;            // inch
        return 0;
    }
    
/**
    *  Return Normalized Length form raw m value. 
    * 
    *  @param      float    $length     Length Raw Value
    *  @return     arrayobject          $r->length , $r->length_units , $r->print, $r->raw
*/
    public static function N_Length($length)
    { 		
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');
        
        $r = new ArrayObject();
        if ( $length    >= 1 )    {    
            $r->length              =   $length;                        // m
            $r->length_units        =   "0";                            // m
        }
        elseif ( $length    >= 1e-1 )    {    
            $r->length              =   $length * 1e1;                  // dm
            $r->length_units        =   "-1";                            // dm
        }
        elseif ( $length    >= 1e-2 )    {    
            $r->length              =   $length * 1e2;                  // g
            $r->length_units        =   "-2";                           // g
        }
        elseif ( $length    >= 1e-3 )    {    
            $r->length              =   $length * 1e3;                  // mg
            $r->length_units        =   "-3";                           // mg
        }
        $r->print = $r->length." ".measuring_units_string($r->length_units,"size");
        $r->raw =   $length;
        return $r;
    }
    
/**
    *  Convert Surface form all units to m². 
    * 
    *  @param      float    $surface    Surface Value
    *  @param      int      $unit       Surface Unit
    *  @return     float                Surface Value in m²
*/
    public static function C_Surface($surface,$unit)
    { 		
        if ( $unit      == "-6")    return $surface / 1e6;             // mm²
        elseif ( $unit  == "-4")    return $surface / 1e4;             // cm²
        elseif ( $unit  == "-2")    return $surface / 1e2;             // dm²
        elseif ( $unit  == "0")     return $surface;                    // m²
        elseif ( $unit  == "98")    return $surface * 0.092903;         // foot²
        elseif ( $unit  == "99")    return $surface * 0.00064516;       // inch²
        return 0;
    }
    
/**
    *  Return Normalized Surface form raw m2 value. 
    * 
    *  @param      float    $length     Surface Raw Value
    *  @return     arrayobject          $r->surface , $r->surface_units , $r->print, $r->raw
*/
    public static function N_Surface($surface)
    { 		
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

        $r = new ArrayObject();
        if ( $surface    >= 1 )    {    
            $r->surface              =   $surface;                      // m2
            $r->surface_units        =   "0";                           // m2
        }
        elseif ( $surface    >= 1e-2 )    {    
            $r->surface              =   $surface * 1e2;                // dm2
            $r->surface_units        =   "-2";                          // dm2
        }
        elseif ( $surface    >= 1e-4 )    {    
            $r->surface              =   $surface * 1e4;                // cm2
            $r->surface_units        =   "-4";                          // cm2
        }
        elseif ( $surface    >= 1e-6 )    {    
            $r->surface              =   $surface * 1e6;                // mm2
            $r->surface_units        =   "-6";                          // mm2
        }
        $r->print = $r->surface." ".measuring_units_string($r->surface_units,"surface");
        $r->raw =   $surface;
        return $r;
    }
    
/**
    *  Convert Volume form all units to m3. 
    * 
    *  @param      float    $volume    Volume Value
    *  @param      int      $unit       Volume Unit
    *  @return     float                Volume Value in m3
*/
    public static function C_Volume($volume,$unit)
    { 		
        if ( $unit      == "-9")    return $volume * 1e-9;              // mm²
        elseif ( $unit  == "-6")    return $volume * 1e-6;              // cm²
        elseif ( $unit  == "-3")    return $volume * 1e-3;              // dm²
        elseif ( $unit  == "0")     return $volume;                     // m²
        elseif ( $unit  == "88")    return $volume * 0.0283168;         // foot²
        elseif ( $unit  == "89")    return $volume * 1.6387e-5;         // inch²
        elseif ( $unit  == "97")    return $volume * 2.9574e-5;         // ounce
        elseif ( $unit  == "98")    return $volume * 1e-3;              // litre
        elseif ( $unit  == "99")    return $volume * 0.00378541;         // gallon
        return 0;
    }
    
/**
    *  Return Normalized Volume form raw m3 value. 
    * 
    *  @param      float    $length     Volume Raw Value
    *  @return     arrayobject          $r->volume , $r->volume_units , $r->print, $r->raw
*/
    public static function N_Volume($volume)
    { 		
        // Include Needed Dolibarr Functions Libraries
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

        $r = new ArrayObject();
        if ( $volume    >= 1 )    {    
            $r->volume              =   $volume;                        // m3
            $r->volume_units        =   "0";                            // m3
        }
        elseif ( $volume    >= 1e-3 )    {    
            $r->volume              =   $volume * 1e3;                  // dm3
            $r->volume_units        =   "-3";                           // dm3
        }
        elseif ( $volume    >= 1e-6 )    {    
            $r->volume              =   $volume * 1e6;                  // cm2
            $r->volume_units        =   "-6";                           // cm2
        }
        elseif ( $volume    >= 1e-9 )    {    
            $r->volume              =   $volume * 1e9;                  // mm2
            $r->volume_units        =   "-9";                           // mm2
        }
        $r->print = $r->volume." ".measuring_units_string($r->volume_units,"volume");
        $r->raw =   $volume;
        return $r;
    }    
    
//====================================================================//
//  Dolibarr Specific Tools
//====================================================================//
    
    /**
    *  Compare Dolibarr version is lower/greater then version given. 
    * 
    *  @param      string       $version        Dolibarr Version to compare (ie : 3.3.3)
    *  @return     boot         int             -1 if given version is lower then current version
    *                                           0 if given version is egal to current version
    *                                           1 if given version is above current version
    */
    public static function DolVersionCmp($version)
    { 	
        $current    = explode('.',DOL_VERSION);
        $cmp        = explode('.',$version);
        
        if ( ( $current["0"] == $cmp ["0"]) && ( $current["1"] == $cmp ["1"]) &&  ( $current["2"]   == $cmp ["2"]) )    return 0;
        else if ( ( $current["0"] > $cmp ["0"])  )  return 1;
        else if ( ( $current["0"] < $cmp ["0"])  )  return -1;
        else if ( ( $current["1"] > $cmp ["1"])  )  return 1;
        else if ( ( $current["1"] < $cmp ["1"])  )  return -1;
        else if ( ( $current["2"] > $cmp ["2"])  )  return 1;
        else if ( ( $current["2"] < $cmp ["2"])  )  return -1;
        return 0;
    }  
    
    /**
     *      @abstract       Search for Dolibarr Root Folder in upper folders - Maximum 5 Levels
     * 
     *      @return         string
     */
    private function getDolibarrRoot() 
    {
        //====================================================================//
        // Search for Dolibarr Root Folder & Store Module Root URL - Maximum 5 Levels
        //====================================================================//

        //====================================================================//
        // Start From Folder Above this module
        $RootFolder = dirname(dirname(dirname(__FILE__)));
        for ( $i=0 ; $i < 5 ; $i++ ) {
            
            //====================================================================//
            // Check if main.inc.phpo file exist
            if (is_file($RootFolder . "/main.inc.php")) {                            
                return $RootFolder;
            }
            
            //====================================================================//
            // Move one folder above
            $RootFolder = dirname($RootFolder);
        }

        return dirname(dirname(dirname(__FILE__)));
    }
    
    /**
     *      @abstract       Update Single Dolibarr Entity Field Data 
     * 
     *      @param      string  $Table      Entity Table Name Without Prefix
     *      @param      int     $RowId      Entity RowId
     *      @param      string  $Name       Field Name
     *      @param      mixed  $Value      Field Data
     * 
     *      @return         string
     */
    public function setSingleField($Table, $RowId, $Name, $Value) 
    {
        global $db;
        
        //====================================================================//
        // Prepare SQL Request
        //====================================================================//
        $sql  = "UPDATE ".MAIN_DB_PREFIX.$Table;
        $sql .= " SET " . $Name . "='".$db->escape($Value)."'";
        $sql .= " WHERE rowid=".$db->escape($RowId);
                
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," SQL : " . $sql);
                
        //====================================================================//
        // Execute SQL Query
        //====================================================================//
        $result = $db->query($sql);
        if (empty($result))  {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__, $db->lasterror());
        }        

        return True;
    }
    
//====================================================================//
//  VARIOUS LOW LEVEL FUNCTIONS
//====================================================================//

    /**
     *      @abstract       Safe Get of A Global Parameter
     * 
     *      @param      string  $Key      Global Parameter Key
     *      @param      string  $Default  Default Parameter Value
     * 
     *      @return     string
     */
    private static function getParameter($Key, $Default = Null) 
    {
        global $conf;
        return isset($conf->global->$Key)  ? $conf->global->$Key : $Default;
    }
    
}

?>
