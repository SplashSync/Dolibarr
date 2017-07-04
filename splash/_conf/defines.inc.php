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
 *  \Id 	$Id: main.lib.php 243 2013-06-02 16:05:41Z u58905340 $
 *  \version    $Revision: 243 $
 *  \date       $LastChangedDate$ 
 *  \ingroup    Splash Server Module For Dolibarr
 *  \brief      Main constant definitions
 *  \remarks	
*/

//====================================================================//
// Splash Module & Dependecies Autoloader
require_once( dirname(dirname(__FILE__)) . "/vendor/autoload.php");

//====================================================================//
//  CONSTANTS DEFINITION
//====================================================================//

//====================================================================//
// Show Debug Messages
define("SPL_SHOW_DEBUG"			,0);
define("SPL_SHOW_DEV"			,0);

//====================================================================//
// Module Version
define("SPL_MOD_VERSION"	,'1.0.1');
define("SPL_MOD_ID"		,9200);
define("SPL_MOD_NAME"           ,'splash');
define("SPL_MOD_CATEGORIE"	,'technic');
define("SPL_MOD_PICTO"          ,'splash@splash');
define("SPL_FULL_NAME"          ,'Splash Server Module for Dolibarr');

//====================================================================//
// Parameters


//====================================================================//
// Databases Names
define('SPL_NODES'	,	'spl_nodes');
define('SPL_LINKS'	,	'spl_links');
define('SPL_LOG'        ,	'spl_log');


//====================================================================//
// Standard Strings
define('SPL_NAME'	,	'Splash Server ');
define('SPL_LOGPREFIX'  ,	SPL_NAME.'- ');
define('SPL_WS'		,       'WebService ');
define('SPL_WSL'		,   SPL_NAME.SPL_WS.'- ');
define('SPL_CHAR_BR'	,	'</br>');
define('SPL_LOGO'	,	"/Splash-ico.png");
define('SPL_WEB_URL'	,	"http://www.dolibarr-addct.com");
define('SPL_HELP_URL'   ,	"http://www.dolibarr-addct.com");
define('SPL_FAQ_URL'	,	"http://www.dolibarr-addct.com");
define('SPL_BUG_URL'	,	"http://www.dolibarr-addct.com");
define('SPL_UPDATE_URL' ,	"http://www.dolistore.com");

//====================================================================//
// Function standard returns 
define('SPL_OK'      ,	1);
define('SPL_KO'      ,	0);
define('SPL_NOK'     ,	0);


//====================================================================//
// Folders Definition for this Module 
define('SPL_FOLDER'	,	"/splash/vendor/splash/phpcore");
define('SPL_ROOT'	,	DOL_DOCUMENT_ROOT.SPL_FOLDER);
define('SPL_CORE'	,	"/core");
define('SPL_CFG'	,	"/_conf");
define('SPL_INC'	,	"/inc");
define('SPL_CLASS'	,	"/class");
define('SPL_PCHART'	,	"/pChart");
define('SPL_NUSOAP'	,	"/Nusoap");
define('SPL_FONTS'	,	"/fonts");
define('SPL_IMG'	,	SPL_FOLDER."/img");
define('SPL_ICO'	,	SPL_FOLDER."/img/ico");
define('SPL_FRAMES'	,	DOL_URL_ROOT.SPL_FOLDER."/frames");
define('SPL_TEMP'	,	DOL_DATA_ROOT.SPL_FOLDER."/temp");
define('SPL_DIRBAK'	,	DOL_DATA_ROOT.SPL_FOLDER."/nodes");

?>
