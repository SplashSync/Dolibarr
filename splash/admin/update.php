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
// Splash Module Definitions
include_once(dirname(dirname(__FILE__))."/_conf/defines.inc.php");

//====================================================================//
// Functions Dolibarr
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
//====================================================================//
// Classes OsConnect
//require_once(OSC_CLASS."/osc_shop.class.php");

//====================================================================//
// Load traductions files required by by page
$langs->load("admin");
$langs->load("errors");
$langs->load("splash@splash");

//====================================================================//
//====================================================================//
//   INITIALISATION
//====================================================================//
//====================================================================//

// Protection if not admin user
if (!$user->admin) {
    accessforbidden();
}

//====================================================================//
// *******************************************************************//
// ACTIONS
// *******************************************************************//
//====================================================================//

//====================================================================//
//====================================================================//
//   SHOW PAGE
//====================================================================//
//====================================================================//

//====================================================================//
// Display Page Header
llxHeader($header, $langs->trans("Update"));

$Path = dirname(dirname(dirname(__FILE__)));
echo $Path."</br>";

print_r(shell_exec("composer update --ignore-platform-reqs -d ".$Path)."</br>");
//echo  shell_exec ( "curl -sS https://getcomposer.org/installer | php") . "</br>";

//====================================================================//
//  Generic Page Footer
llxFooter('$Date: 2016-03-22 14:21:19 +0100 (mar., 22 mars 2016) $ - $Revision: 492 $');
