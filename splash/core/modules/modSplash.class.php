<?php
/* Copyright (C) 2015      Splash Sync <www.splashsync.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 */

/**
 *  \Id 	$Id: main.lib.php 243 2013-06-02 16:05:41Z u58905340 $
 *  \version    $Revision: 243 $
 *  \date       $LastChangedDate$ 
 *  \ingroup    Splash Server - Online Shop Connector for Dolibarr
 *              Webservice Module for ERP to Online Shop synchronisation
 *  \brief      Module Definitions
 *  \remarks	
 */


include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

//====================================================================//
// Splash Module Definitions
include_once(dirname(dirname(dirname(__FILE__))) ."/_conf/defines.inc.php");



/**
 * @abstract Splash Module For Dolibarr
 */
class modSplash extends DolibarrModules
{
	/**
	 *   \brief      Constructor. Define names, constants, directories, boxes, permissions
	 *   \param      DB      Database handler
	 */
	function modSplash($DB)
	{
            global $langs;
            //====================================================================//
            // Load traductions files required by by page
            $langs->load("admin");
            $langs->load("splash@splash");
            
            $this->db = $DB;
            
            //====================================================================//
            // Module Editor Infos
            $this->editor_name = "Splash Sync";
            $this->editor_web = "www.splashsync.com";            
            
            //====================================================================//
            // Id for module (must be unique).
            // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
            $this->numero = SPL_MOD_ID;
            // Key text used to identify module (for permissions, menus, etc...)
            $this->rights_class = SPL_MOD_NAME;
            // It is used to group modules in module setup page
            $this->family = SPL_MOD_CATEGORIE;
            // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
            $this->name = preg_replace('/^mod/i','',get_class($this));
            // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
            $this->description = $langs->trans("SPL_Short_Desc");
            // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
            $this->version = SPL_MOD_VERSION;
            // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
            $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
            // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
            $this->special = 1;
            // Name of image file used for this module.
            // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
            // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
            $this->picto = SPL_MOD_PICTO;

            //====================================================================//
            // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
            // for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
            // for specific path of parts (eg: /mymodule/core/modules/barcode)
            // for specific css file (eg: /mymodule/css/mymodule.css.php)
            $this->module_parts = array('triggers' => 1,            // Set this to 1 if module has its own trigger directory
                                        'login' => 0,               // Set this to 1 if module has its own login method directory
                                        'substitutions' => 0,       // Set this to 1 if module has its own substitution function file
                                        'menus' => 0,               // Set this to 1 if module has its own menus handler directory
                                        'barcode' => 0,             // Set this to 1 if module has its own barcode directory
                                        'models' => 0,              // Set this to 1 if module has its own models directory
                                        'css' => '',                 // Set this to relative path of css if module has its own css file
                                        'hooks' => '',              // Set here all hooks context managed by module
                                        'workflow' => ''            // Set here all workflow context managed by module
                                        );
                
            $r=0;

            // Config pages. Put here list of php page names stored in admmin directory used to setup module.
            $this->config_page_url = array("index.php@".SPL_MOD_NAME);

            //====================================================================//
            // Dependencies
            // List of modules id that must be enabled if this module is enabled
            $this->depends = array("modCommande","modProduct","modCategorie","modStock","modBanque","modSociete","modFacture");		
            // List of modules id to disable if this one is disabled
            $this->requiredby = array();	
            $this->phpmin = array(5,6);                                 // Minimum version of PHP required by module
            $this->need_dolibarr_version = array(4,0);                  // Minimum version of Dolibarr required by module
            $this->langfiles = array(SPL_MOD_NAME."@".SPL_MOD_NAME);

                
            //====================================================================//
            // Constants
            // Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
            //                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
            //                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',0)
            // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
            $this->const = array(
                //====================================================================//
                // Splash Commons Parameters
                array('SPLASH_WS_ID',   'chaine',   '',                     'Identifier on Splash Server',                      0),
                array('SPLASH_WS_KEY',  'chaine',   '',                     'Encryption Key for Splash Server communications',  0),
                array('SPLASH_WS_HOST', 'chaine',   "www.splashsync.com",   'Splash Server Address',0),
                //====================================================================//
                // Splash Locals Parameters
                array('SPLASH_LANG',    'chaine',   '',                     'Local Language to use for Splash Server Transactions', 0),
                array('SPLASH_USER',    'chaine',   '',                     'Local User to use for Splash Server Transactions', 0),
                array('SPLASH_STOCK',   'chaine',   '',                     'Local Warhouse to use for Splash Server Transactions', 0),
            );

            //====================================================================//
            // Permissions
            $this->rights = array();		// Permission array used by this module
            $r=0;

            $this->rights[$r][0] = 9201; // id de la permission
            $this->rights[$r][1] = 'Lire les Données'; // libelle de la permission
            $this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
            $this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
            $this->rights[$r][4] = 'lire';
            $r++;

            $this->rights[$r][0] = 9202; // id de la permission
            $this->rights[$r][1] = 'Creer/modifier des données'; // libelle de la permission
            $this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
            $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
            $this->rights[$r][4] = 'creer';
            $r++;

            $this->rights[$r][0] = 9203; // id de la permission
            $this->rights[$r][1] = 'Modifier les paramètres du Module'; // libelle de la permission
            $this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
            $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
            $this->rights[$r][4] = 'creer';
            $r++;

            $this->rights[$r][0] = 9204; // id de la permission
            $this->rights[$r][1] = 'Supprimer des données'; // libelle de la permission
            $this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
            $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
            $this->rights[$r][4] = 'supprimer';
            $r++;

            //====================================================================//
            // Main menu entries
            $this->menus = array();			// List of menus to add
                
	}

	/**
	 *		\brief      Function called when module is enabled.
	 *					The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *					It also creates data directories.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function init()
	{
                global $langs;

                // Module Init
		$sql = array();
		$result =  $this->_init($sql);
                
                if ($result) {
                    // Display Welcome Message
                    setEventMessage($langs->trans("SPL_Welcome",SPL_MOD_VERSION),'mesgs');       
                }
                
                return $result;
	}

	/**
	 *		\brief		Function called when module is disabled.
	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
	 *					Data directories are not deleted.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function remove()
	{
		$sql = array();
		return $this->_remove($sql);
	}


	/**
	 *		\brief		Create tables, keys and data required by module
	 * 					Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 					and create data commands must be stored in directory /mymodule/sql/
	 *					This function is called by this->init.
	 * 		\return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return 1;
	}
}

?>
