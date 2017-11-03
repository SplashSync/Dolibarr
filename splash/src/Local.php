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
    
use Splash\Local\Core\ExtraFieldsTrait;

/**
 *	\class      SplashLocal
 *	\brief      Local Core Management Class
 */
class Local 
{
    use ExtraFieldsTrait;
    
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
        // If Expert Mode => Allow Overide of Server Host Address
        if ( (self::getParameter("SPLASH_WS_EXPERT")) && !empty(self::getParameter("SPLASH_WS_HOST")) ) {
            $Parameters["WsHost"]           =   self::getParameter("SPLASH_WS_HOST");
        }
        //====================================================================//
        // If Expert Mode => Allow Update of Communication Protocol
        if ( (self::getParameter("SPLASH_WS_EXPERT")) && !empty(self::getParameter("SPLASH_WS_METHOD")) ) {
            $Parameters["WsMethod"]           =   self::getParameter("SPLASH_WS_METHOD");
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
        
        //====================================================================//
        // Check Version is Above 4.0
        if (Splash::Local()->DolVersionCmp("4.0.0") < 0) {
            return Splash::Log()->Err("Splash Module for Dolibarr require Dolibarr Version Above 4.0. Please update your system before using Splash.");
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
    
//====================================================================//
// *******************************************************************//
//  OPTIONNAl CORE MODULE LOCAL FUNCTIONS
// *******************************************************************//
//====================================================================//
    

    /**
     *      @abstract       Return Local Server Test Sequences as Aarray
     *                      
     *      THIS FUNCTION IS OPTIONNAL - USE IT ONLY IF REQUIRED
     * 
     *      This function called on each initialization of module's tests sequences.
     *      It's aim is to list different configurations for testing on local system.
     * 
     *      If Name = List, Result must be an array including list of Sequences Names.
     * 
     *      If Name = ASequenceName, Function will Setup Sequence on Local System.
     * 
     *      @return         array       $Sequences
     */    
    public static function TestSequences($Name = Null)
    {
        global $db, $conf;
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
                
        switch($Name) {
            
            case "Monolangual":
                dolibarr_set_const($db,"MAIN_MULTILANGS"            ,0,'chaine',0,'',$conf->entity);              
                dolibarr_set_const($db,"PRODUIT_MULTIPRICES"        ,0,'chaine',0,'',$conf->entity);          
                
                self::configurePhpUnitExtraFields("societe",    False);
                self::configurePhpUnitExtraFields("socpeople",  False);
                self::configurePhpUnitExtraFields("product",    False);
                self::configurePhpUnitExtraFields("commande",   False);                
                self::configurePhpUnitExtraFields("facture",    False);
                return;
                
            case "Multilangual":
                dolibarr_set_const($db,"MAIN_MULTILANGS"            ,1,'chaine',0,'',$conf->entity);              
                dolibarr_set_const($db,"PRODUIT_MULTIPRICES"        ,0,'chaine',0,'',$conf->entity);              
                return;
            
            case "MultiPrices":
                dolibarr_set_const($db,"MAIN_MULTILANGS"            ,1,'chaine',0,'',$conf->entity);              
                dolibarr_set_const($db,"PRODUIT_MULTIPRICES"        ,1,'chaine',0,'',$conf->entity);              
                dolibarr_set_const($db,"PRODUIT_MULTIPRICES_LIMIT"  ,3,'chaine',0,'',$conf->entity);              
                dolibarr_set_const($db,"SPLASH_MULTIPRICE_LEVEL"    ,rand(1,3),'chaine',0,'',$conf->entity);              
                
                self::configurePhpUnitExtraFields("societe",    False);
                self::configurePhpUnitExtraFields("socpeople",  False);
                self::configurePhpUnitExtraFields("product",    False);
                self::configurePhpUnitExtraFields("commande",   False);                
                self::configurePhpUnitExtraFields("facture",    False);
                return;

            case "ExtraFields":
                dolibarr_set_const($db,"MAIN_MULTILANGS"            ,0,'chaine',0,'',$conf->entity);              
                dolibarr_set_const($db,"PRODUIT_MULTIPRICES"        ,0,'chaine',0,'',$conf->entity);              
                
                self::configurePhpUnitExtraFields("societe",    True);
                self::configurePhpUnitExtraFields("socpeople",  True);
                self::configurePhpUnitExtraFields("product",    True);
                self::configurePhpUnitExtraFields("commande",   True);
                self::configurePhpUnitExtraFields("facture",    True);
                return;

                
            case "List":
                return array("Monolangual", "Multilangual", "MultiPrices", "ExtraFields" );
//                return array("Monolangual");
//                return array( "ExtraFields" );
                
        }
    }
    
//====================================================================//
// *******************************************************************//
// Place Here Any SPECIFIC or COMMON Local Functions
// *******************************************************************//
//====================================================================//
    
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
