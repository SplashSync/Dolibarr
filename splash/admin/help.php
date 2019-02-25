<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

//====================================================================//
//   INCLUDES
//====================================================================//
require(is_file("../../main.inc.php") ? "../../main.inc.php" : "../../../main.inc.php");

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
if (!$user->admin) {
    accessforbidden();
}
//====================================================================//
// Generic Page Header
llxHeader($header, $langs->trans("Help"), "");
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
Spl_SHOW_ConfTab($db, 'help', null);
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
