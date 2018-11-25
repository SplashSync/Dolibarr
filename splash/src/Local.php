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
 *  \remarks    Designed for Splash Module - Dolibar ERP Version
*/

namespace Splash\Local;

use Splash\Core\SplashCore      as Splash;

use User;
use ArrayObject;
    
use Splash\Local\Core\MultiCompanyTrait;
use Splash\Models\LocalClassInterface;

/**
 * @abstract    Local Core Management Class
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Local implements LocalClassInterface
{
    use MultiCompanyTrait;
    
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
    public function parameters()
    {
        global $langs;
        
        $Parameters       =     array();
        //====================================================================//
        // Server Identification Parameters
        $Parameters["WsIdentifier"]         =   self::getParameter("SPLASH_WS_ID");
        $Parameters["WsEncryptionKey"]      =   self::getParameter("SPLASH_WS_KEY");
        //====================================================================//
        // If Expert Mode => Allow Overide of Server Host Address
        if ((self::getParameter("SPLASH_WS_EXPERT")) && !empty(self::getParameter("SPLASH_WS_HOST"))) {
            $Parameters["WsHost"]           =   self::getParameter("SPLASH_WS_HOST");
        }
        //====================================================================//
        // If Expert Mode => Allow Update of Communication Protocol
        if ((self::getParameter("SPLASH_WS_EXPERT")) && !empty(self::getParameter("SPLASH_WS_METHOD"))) {
            $Parameters["WsMethod"]           =   self::getParameter("SPLASH_WS_METHOD");
        }
        //====================================================================//
        // Overide Module Parameters with Local User Selected Lang
        if (self::getParameter("SPLASH_LANG")) {
            $Parameters["DefaultLanguage"]      =   self::getParameter("SPLASH_LANG");
        //====================================================================//
        // Overide Module Parameters with Local Default System Lang
        } elseif (($langs) && $langs->getDefaultLang()) {
            $Parameters["DefaultLanguage"]      =   $langs->getDefaultLang();
        }
        //====================================================================//
        // Overide Module Local Name in Logs
        $Parameters["localname"]        =   self::getParameter("MAIN_INFO_SOCIETE_NOM");
        //====================================================================//
        // Overide Webserver Path if MultiCompany Module Is Active
        if (Splash::local()->isMultiCompanyChildEntity()) {
            $Parameters["ServerPath"]        =   Splash::local()->getMultiCompanyServerPath();
        }
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
     *
     *  @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function includes()
    {

        //====================================================================//
        // When Library is called in server mode ONLY
        //====================================================================//
        if (SPLASH_SERVER_MODE) {
            define('NOCSRFCHECK', 1);    // This is Webservice Access. We must be able to go on it from outside.
        //====================================================================//
        // When Library is called in client mode ONLY
        //====================================================================//
        } else {
            // NOTHING TO DO
        }


        //====================================================================//
        // When Library is called in both client & server mode
        //====================================================================//

        if (!defined("DOL_DOCUMENT_ROOT")) {
            global $db,$langs,$conf,$user,$hookmanager, $dolibarr_main_url_root;

            //====================================================================//
            // Initiate Dolibarr Global Envirement Variables
            require_once($this->getDolibarrRoot() . "/master.inc.php");
           
            //====================================================================//
            // Splash Modules Constant Definition
            dol_include_once("/splash/_conf/defines.inc.php");
            //====================================================================//
            // Load Default Language
            $this->loadDefaultLanguage();
            
            //====================================================================//
            // Load Default User
            $this->loadLocalUser();
            
            //====================================================================//
            // Manage MultiCompany
            //====================================================================//
            $this->setupMultiCompany();
        }
        
        
        
        return true;
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
    public function selfTest()
    {
        global $langs;

        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->load("main@local");
        $langs->load("errors");
        
        //====================================================================//
        //  Verify - Server Core Infos
        if (!self::selfTestCore()) {
            return false;
        }
        //====================================================================//
        //  Verify - User Config
        if (!self::selfTestConfig()) {
            return false;
        }
        
        Splash::log()->msg("MsgSelfTestOk");
        return true;
    }
    

    private static function selfTestCore()
    {
        global $conf;

        //====================================================================//
        //  Verify - Server Identifier Given
        if (!isset($conf->global->SPLASH_WS_ID) || empty($conf->global->SPLASH_WS_ID)) {
            return Splash::log()->err("ErrSelfTestNoWsId");
        }
                
        //====================================================================//
        //  Verify - Server Encrypt Key Given
        if (!isset($conf->global->SPLASH_WS_KEY) || empty($conf->global->SPLASH_WS_KEY)) {
            return Splash::log()->err("ErrSelfTestNoWsKey");
        }
        
        return true;
    }

    private static function selfTestConfig()
    {
        global $conf,$langs;

        //====================================================================//
        //  Verify - User Selected
        if (!isset($conf->global->SPLASH_USER) || ($conf->global->SPLASH_USER <= 0)) {
            return Splash::log()->err("ErrSelfTestNoUser");
        }
        
        //====================================================================//
        //  Verify - Stock Selected
        if (!isset($conf->global->SPLASH_STOCK) || ($conf->global->SPLASH_STOCK <= 0)) {
            return Splash::log()->err("ErrSelfTestNoStock");
        }
        
        //====================================================================//
        // Check if company name is defined (first install)
        if (empty($conf->global->MAIN_INFO_SOCIETE_NOM) || empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY)) {
            return Splash::log()->err($langs->trans("WarningMandatorySetupNotComplete"));
        }
        
        //====================================================================//
        // Check Version is Above 4.0
        if (Splash::local()->dolVersionCmp("4.0.0") < 0) {
            return Splash::log()->err(
                "Splash Module for Dolibarr require Dolibarr Version Above 4.0. "
                    . "Please update your system before using Splash."
            );
        }
        
        return true;
    }
    
    /**
     *  @abstract   Update Server Informations with local Data
     *
     *  @param      ArrayObject  $Informations   Informations Inputs
     *
     *  @return     ArrayObject
     */
    public function informations($Informations)
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
        $Response->icoraw           =   Splash::file()->readFileContents(DOL_DOCUMENT_ROOT . "/favicon.ico");
        $Response->logourl          =   "http://www.dolibarr.org/images/stories/dolibarr_256x256.png";
        
        //====================================================================//
        // Server Informations
        $Response->servertype       =   "Dolibarr ERP";
        $Response->serverurl        =   DOL_MAIN_URL_ROOT;
        
        //====================================================================//
        // Current Module Version
        $Response->moduleversion    =   SPL_MOD_VERSION;
        
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
    public static function testSequences($Name = null)
    {
        global $db, $conf;
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
                
        switch ($Name) {
            case "Monolangual":
                dolibarr_set_const($db, "MAIN_MULTILANGS", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "PRODUIT_MULTIPRICES", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "MAIN_MODULE_MULTICOMPANY", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "SPLASH_DETECT_TAX_NAME", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_ALLOW", 0, 'chaine', 0, '', $conf->entity);
                
                ExtraFieldsTrait::configurePhpUnitExtraFields("societe", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("socpeople", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("product", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("commande", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("facture", false);
                return;
                
            case "Multilangual":
                dolibarr_set_const($db, "MAIN_MULTILANGS", 1, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "PRODUIT_MULTIPRICES", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "MAIN_MODULE_MULTICOMPANY", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_ALLOW", 0, 'chaine', 0, '', $conf->entity);
                return;
            
            case "MultiPrices":
                dolibarr_set_const($db, "MAIN_MULTILANGS", 1, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "PRODUIT_MULTIPRICES", 1, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", 3, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "SPLASH_MULTIPRICE_LEVEL", rand(1, 3), 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "MAIN_MODULE_MULTICOMPANY", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_ALLOW", 0, 'chaine', 0, '', $conf->entity);
                
                ExtraFieldsTrait::configurePhpUnitExtraFields("societe", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("socpeople", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("product", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("commande", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("facture", false);
                return;

            case "ExtraFields":
                dolibarr_set_const($db, "MAIN_MULTILANGS", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "PRODUIT_MULTIPRICES", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "MAIN_MODULE_MULTICOMPANY", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_ALLOW", 0, 'chaine', 0, '', $conf->entity);
                
                ExtraFieldsTrait::configurePhpUnitExtraFields("societe", true);
                ExtraFieldsTrait::configurePhpUnitExtraFields("socpeople", true);
                ExtraFieldsTrait::configurePhpUnitExtraFields("product", true);
                ExtraFieldsTrait::configurePhpUnitExtraFields("commande", true);
                ExtraFieldsTrait::configurePhpUnitExtraFields("facture", true);
                return;

            case "GuestOrders":
                dolibarr_set_const($db, "MAIN_MULTILANGS", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "PRODUIT_MULTIPRICES", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "MAIN_MODULE_MULTICOMPANY", 0, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_ALLOW", 1, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_CUSTOMER", 1, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_EMAIL", 1, 'chaine', 0, '', $conf->entity);
                
                ExtraFieldsTrait::configurePhpUnitExtraFields("societe", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("socpeople", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("product", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("commande", false);
                ExtraFieldsTrait::configurePhpUnitExtraFields("facture", false);
                return;

                
            case "List":
                return array("Monolangual", "Multilangual", "MultiPrices", "GuestOrders", "ExtraFields" );
//                return array("Monolangual");
//                return array("Multilangual");
//                return array("MultiPrices");
//                return array( "GuestOrders" );
//                return array( "ExtraFields" );
        }
    }
    
    /**
     *  @abstract       Return Local Server Test Parameters as Array
     *
     *      THIS FUNCTION IS OPTIONNAL - USE IT ONLY IF REQUIRED
     *
     *      This function called on each initialisation of module's tests sequences.
     *      It's aim is to overide general Tests settings to be adjusted to local system.
     *
     *      Result must be an array including parameters as strings or array.
     *
     *  @see Splash\Tests\Tools\ObjectsCase::settings for objects tests settings
     *
     *  @return         array       $parameters
     */
    public function testParameters()
    {
        //====================================================================//
        // Init Parameters Array
        return array();
        // CHANGE SOMETHING
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
    private function loadLocalUser()
    {
        global $conf,$db,$user;
        
        //====================================================================//
        // CHECK USER ALREADY LOADED
        //====================================================================//
        if (isset($user->id) && !empty($user->id)) {
            return true;
        }
        
        //====================================================================//
        // LOAD USER FROM DATABASE
        //====================================================================//
        
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

        //====================================================================//
        // Read Local Configuration
        $userId = isset($conf->global->SPLASH_USER)?$conf->global->SPLASH_USER:null;
        if (empty($userId)) {
            return Splash::log()->err("Local - Dolibarr Error : No Local User Defined.");
        }
        //====================================================================//
        // Load Local User

        $user = new User($db);
        if ($user->fetch($userId) != 1) {
            Splash::log()->err("Local : Unable to Load Local User");
            return Splash::log()->err("Local - Dolibarr Error : " . $user->error);
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
    private function loadDefaultLanguage()
    {
        global $langs;
        //====================================================================//
        // Load Default Language
        //====================================================================//
        if (!empty(Splash::configuration()->DefaultLanguage)) {
            $langs->setDefaultLang(Splash::configuration()->DefaultLanguage);
        }
    }
    
//====================================================================//
//  Dolibarr Specific Tools
//====================================================================//
    
    /**
    *  Compare Dolibarr version is lower/greater then version given.
    *
    *  @param      string       $version        Dolibarr Version to compare (ie : 3.3.3)
    *  @return     int                          -1 if given version is lower then current version
    *                                           0 if given version is egal to current version
    *                                           1 if given version is above current version
    *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
    */
    public static function dolVersionCmp($version)
    {
        $current    = explode('.', DOL_VERSION);
        $cmp        = explode('.', $version);
        
        if (( $current["0"] == $cmp ["0"]) && ( $current["1"] == $cmp ["1"]) &&  ( $current["2"]   == $cmp ["2"])) {
            return 0;
        } elseif (( $current["0"] > $cmp ["0"])) {
            return 1;
        } elseif (( $current["0"] < $cmp ["0"])) {
            return -1;
        } elseif (( $current["1"] > $cmp ["1"])) {
            return 1;
        } elseif (( $current["1"] < $cmp ["1"])) {
            return -1;
        } elseif (( $current["2"] > $cmp ["2"])) {
            return 1;
        } elseif (( $current["2"] < $cmp ["2"])) {
            return -1;
        }
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
        for ($i=0; $i < 5; $i++) {
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
    private static function getParameter($Key, $Default = null)
    {
        global $conf;
        return isset($conf->global->$Key)  ? $conf->global->$Key : $Default;
    }
}
