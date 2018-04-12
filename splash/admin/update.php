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
 *  \Id 	$Id: index.php 492 2016-03-22 13:21:19Z Nanard33 $
 *  \version    $Revision: 492 $
 *  \ingroup    Splash - Dolibarr Synchronisation via WebService
 *  \brief      Page d'administration/configuration du module OsCommerce
*/

//====================================================================//
//   INCLUDES
//====================================================================//
require(is_file("../../main.inc.php") ? "../../main.inc.php" : "../../../main.inc.php");

//====================================================================//
// Splash Module Definitions
include_once(dirname(dirname(__FILE__)) ."/_conf/defines.inc.php");

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
echo $Path . "</br>";

print_r(shell_exec("composer update --ignore-platform-reqs -d " . $Path)  . "</br>");
//echo  shell_exec ( "curl -sS https://getcomposer.org/installer | php") . "</br>";

//====================================================================//
//  Generic Page Footer
llxFooter('$Date: 2016-03-22 14:21:19 +0100 (mar., 22 mars 2016) $ - $Revision: 492 $');
