<?php
/*
 * Copyright (C) 2011 Bernard Paquier       <bernard.paquier@gmail.com>
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
 *  \Id 	$Id: help.php 345 2014-09-24 15:20:11Z Nanard33 $
 *  \version    $Revision: 345 $
 *  \ingroup    Splash - Dolibarr Synchronisation via WebService
 *  \brief      Page d'administration/configuration du module OsCommerce
*/

//====================================================================//
//   INCLUDES 
//====================================================================//
require( is_file("../../main.inc.php") ? "../../main.inc.php" : "../../../main.inc.php" );

//====================================================================//
// Root Module Include
require_once(DOL_DOCUMENT_ROOT."/splash/_conf/main.inc.php");
//====================================================================//
// Load traductions files required by by page
$langs->load("admin");
$langs->load("users");
$langs->load("errors");
$langs->load("install");
$langs->load("spl-admin@splash");
$langs->load("spl-help@splash");
//====================================================================//
//====================================================================//
//   INITIALISATION 
//====================================================================//
//====================================================================//
    
//====================================================================//
// Protection if not admin user
if (!$user->admin)	accessforbidden();
//====================================================================//
// Generic Page Header
llxHeader($header ,$langs->trans("Help"),"");
//====================================================================//
// Specific Styles For this Page
// NOTHING TO DO
//====================================================================//
//====================================================================//
//   ACTIONS 
//====================================================================//
//====================================================================//
// NOTHING TO DO
//====================================================================//
// Live Debug Information Display
// NOTHING TO DO
//====================================================================//
//====================================================================//
//   SHOW PAGE
//====================================================================//
//====================================================================//

//====================================================================//
//  Main Setup Tab
Spl_SHOW_ConfTab($db,'help',NULL);
//====================================================================//
//  Display Module Help Frame
//====================================================================//
//====================================================================//
//  Main Help Contents
print_titre($langs->trans("Help"));  
print _Html::Br(1);
print $langs->trans("SPL_Help1", SPL_WEB_URL, SPL_WEB_URL);
print $langs->trans("SPL_Help2", SPL_HELP_URL, SPL_HELP_URL, SPL_FAQ_URL, SPL_FAQ_URL);
print $langs->trans("SPL_Help3", SPL_BUG_URL, SPL_BUG_URL, SPL_UPDATE_URL, SPL_UPDATE_URL);
print _Html::Br(2);
//====================================================================//
//  Module Licence
print_titre($langs->trans("License"));  
print _Html::Br(1);
print $langs->trans("SPL_License", SPL_WEB_URL, SPL_HELP_URL, SPL_FAQ_URL, SPL_BUG_URL, SPL_UPDATE_URL);
print _Html::DivEnd();
print _Html::Br(1);
//====================================================================//
//  Post User Messages
Splash_Post_LogMessages($master->log);
//====================================================================//
//  OsWs Core Module Id Card
//====================================================================//
//  Dolibarr Addict Framework Id Card
print _Html::Br(3);
print _DA::LibIdCard(DOL_URL_ROOT."/dolibarraddict/core/da/");
print _Html::Br(1);
//====================================================================//
//  Generic Page Footer
llxFooter('$Date: 2014-09-24 17:20:11 +0200 (mer. 24 sept. 2014) $ - $Revision: 345 $');
?>
