<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

//====================================================================//
// PHP CS Overrides
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable PSR1.Methods.CamelCapsMethodName
// phpcs:disable Squiz.Classes.ValidClassName

include_once(DOL_DOCUMENT_ROOT."/core/modules/DolibarrModules.class.php");

//====================================================================//
// Splash Module Definitions
dol_include_once("/splash/_conf/defines.inc.php");

/**
 * Splash Module For Dolibarr
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class modSplash extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param mixed $db
     */
    public function __construct($db)
    {
        global $langs;
        parent::__construct($db);

        //====================================================================//
        // Load traductions files required by by page
        $langs->load("admin");
        $langs->load("splash@splash");

        //====================================================================//
        // Module Editor Infos
        $this->editor_name = "Splash Sync";
        $this->editor_url = "www.splashsync.com";

        //====================================================================//
        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = SPL_MOD_ID;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = SPL_MOD_NAME;
        // It is used to group modules in module setup page
        $this->family = SPL_MOD_CATEGORIE;
        // Module label (no space allowed), used if translation string
        // 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = (string) preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc'
        // not found (where XXX is value of numeric property 'numero' of module)
        $this->description = $langs->trans("SPL_Short_Desc");
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = SPL_MOD_VERSION;
        // Key used in llx_const table to save module status enabled/disabled
        // (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        $this->picto = SPL_MOD_PICTO;

        //====================================================================//
        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /mymodule/core/modules/barcode)
        // for specific css file (eg: /mymodule/css/mymodule.css.php)
        $this->module_parts = array(
            'triggers' => 1,            // Set this to 1 if module has its own trigger directory
            'login' => 0,               // Set this to 1 if module has its own login method directory
            'substitutions' => 0,       // Set this to 1 if module has its own substitution function file
            'menus' => 0,               // Set this to 1 if module has its own menus handler directory
            'barcode' => 0,             // Set this to 1 if module has its own barcode directory
            'models' => 0,              // Set this to 1 if module has its own models directory
            'css' => '',                 // Set this to relative path of css if module has its own css file
            'hooks' => '',              // Set here all hooks context managed by module
            'workflow' => ''            // Set here all workflow context managed by module
        );

        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        $this->config_page_url = array("index.php@".SPL_MOD_NAME);

        //====================================================================//
        // Dependencies
        // List of modules id that must be enabled if this module is enabled
        $this->depends = array(
            "modCommande","modProduct","modCategorie","modStock","modBanque","modSociete","modFacture");
        // List of modules id to disable if this one is disabled
        $this->requiredby = array();
        $this->phpmin = array(7,1);                                 // Min version of PHP required by module
        $this->need_dolibarr_version = array(7,0);                  // Min version of Dolibarr required by module
        $this->langfiles = array(SPL_MOD_NAME."@".SPL_MOD_NAME);

        //====================================================================//
        // Constants
        $this->const = $this->getConstants();

        //====================================================================//
        // Permissions
        $this->rights = $this->getRights();

        //====================================================================//
            // Main menu entries
            $this->menu = array();         // List of menus to add
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions
     * and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories.
     *
     * @param mixed $options
     *
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $langs;

        // Module Init
        $sql = array();
        $result = $this->_init($sql, $options);

        if ($result) {
            // Display Welcome Message
            setEventMessage($langs->trans("SPL_Welcome", SPL_MOD_VERSION), 'mesgs');
        }

        return $result;
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted.
     *
     * @param mixed $options
     *
     * @return int 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }

    /**
     * Create tables, keys and data required by module
     * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * and create data commands must be stored in directory /mymodule/sql/
     * This function is called by this->init.
     *
     * @return int 1 if OK, 0 if KO
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function load_tables()
    {
        return 1;
    }

    /**
     * Get Splash Module Configuration Contants
     *
     * @return array
     */
    private function getConstants()
    {
        //====================================================================//
        // Constants
        return array(
            //====================================================================//
            // Splash Core Parameters
            array('SPLASH_WS_ID',   'chaine',   '', 'Identifier on Splash Server',                      0),
            array('SPLASH_WS_KEY',  'chaine',   '',  'Encryption Key for Splash Server communications',  0),
            array('SPLASH_WS_EXPERT',  'int',   '',   'Use Expert Mode or Not',  0),
            array('SPLASH_WS_METHOD',  'chaine',   'NuSOAP',  'Communication Method to Use',  0),
            array('SPLASH_WS_HOST', 'chaine',   "https://www.splashsync.com/ws/soap",   'Splash Server Address',0),
            array('SPLASH_SMART_NOTIFY', 'int',  '',  'Smart Notifications', 0),
            //====================================================================//
            // Splash Locals Parameters
            array('SPLASH_LANG',    'chaine',   '',  'Local Language to use for Splash Server Transactions', 0),
            array(
                'SPLASH_LANGS',
                'chaine',
                serialize(array()),
                'Others Languages to use for Splash Server Transactions',
                0
            ),
            array('SPLASH_USER',    'chaine',   '',  'Local User to use for Splash Server Transactions', 0),
            array(
                'SPLASH_STOCK',
                'chaine',
                '',
                'Local Warehouse to use for Splash Server Transactions',
                0
            ),
            array('SPLASH_MULTISTOCK',   'chaine',   '',  'Manage Independant Stocks for Each Warehouse', 0),
            array('SPLASH_PRODUCT_STOCK','chaine',   '',  'Default Warehouse to setup for New/Updated Products', 0),
            array('SPLASH_MULTIPRICE_LEVEL','chaine', '1', 'Local Default Multiprice Level to Use', 0),
            //====================================================================//
            // Splash Order & Invoices Parameters
            array('SPLASH_DETECT_TAX_NAME', 'chaine', '0', 'Use Tax Names to detect Vat Types', 0),
            array('SPLASH_BANK',    'chaine',   '',  'Local Default Bank Account Id', 0),
            array('SPLASH_DEFAULT_PAYMENT', 'chaine', 'CHQ', 'Local Default Payment Method', 0),
            array('SPLASH_GUEST_ORDERS_ALLOW', 'chaine', '', 'Allow Import of Guests Orders & Invoices', 0),
            array('SPLASH_GUEST_ORDERS_CUSTOMER', 'chaine', '', 'Select Guest Orders Customer', 0),
            array('SPLASH_GUEST_ORDERS_EMAIL', 'chaine', '', 'Try to detect Customer Using Email ', 0),
            array('SPLASH_DECTECT_ITEMS_BY_SKU', 'chaine', '', 'Try to detect Products Items by SKU', 0),
        );
    }

    /**
     * Get Splash Module Rights Array
     *
     * @return array
     */
    private function getRights()
    {
        //====================================================================//
        // Permissions
        $rights = array();      // Permission array used by this module
        $index = 0;

        $rights[$index][0] = 9201; // id de la permission
            $rights[$index][1] = 'Lire les Données'; // libelle de la permission
            $rights[$index][2] = 'r'; // type de la permission (deprecie a ce jour)
            $rights[$index][3] = 1; // La permission est-elle une permission par defaut
            $rights[$index][4] = 'lire';
        $index++;

        $rights[$index][0] = 9202; // id de la permission
            $rights[$index][1] = 'Creer/modifier des données'; // libelle de la permission
            $rights[$index][2] = 'w'; // type de la permission (deprecie a ce jour)
            $rights[$index][3] = 0; // La permission est-elle une permission par defaut
            $rights[$index][4] = 'creer';
        $index++;

        $rights[$index][0] = 9203; // id de la permission
            $rights[$index][1] = 'Modifier les paramètres du Module'; // libelle de la permission
            $rights[$index][2] = 'w'; // type de la permission (deprecie a ce jour)
            $rights[$index][3] = 0; // La permission est-elle une permission par defaut
            $rights[$index][4] = 'creer';
        $index++;

        $rights[$index][0] = 9204; // id de la permission
            $rights[$index][1] = 'Supprimer des données'; // libelle de la permission
            $rights[$index][2] = 'd'; // type de la permission (deprecie a ce jour)
            $rights[$index][3] = 0; // La permission est-elle une permission par defaut
            $rights[$index][4] = 'supprimer';
        $index++;

        return $rights;
    }
}
