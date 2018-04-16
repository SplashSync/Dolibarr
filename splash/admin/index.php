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

use Splash\Client\Splash;

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

$form = new Form($db);
        
//====================================================================//
// Get parameters
$node       = GETPOST("node");
$ObjectType = GETPOST("ObjectType");
$action     = GETPOST("action");
$show       = GETPOST("show");
    
// Protection if not admin user
if (!$user->admin) {
    accessforbidden();
}

//====================================================================//
// Create Dolibarr Form Class
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
$form   =   new Form($db);

//====================================================================//
// *******************************************************************//
// ACTIONS
// *******************************************************************//
//====================================================================//

//====================================================================//
// Update Main Parameters
include("UpdateMain.php");
//====================================================================//
// Update of Local Module Parameters
include("UpdateLocal.php");
//====================================================================//
// Update of Orders & Invoices Parameters
include("UpdateOrders.php");
//====================================================================//
// Update of Payments Parameters
include("UpdatePayments.php");



//====================================================================//
//====================================================================//
//   SHOW PAGE
//====================================================================//
//====================================================================//

//====================================================================//
// Display Page Header
llxHeader($header, $langs->trans("Setup"));

//====================================================================//
// Display Page Title
$title = $langs->trans("SPL_Name") . " - ". $langs->trans("Setup");
$linkback= '<a href="' . DOL_URL_ROOT.'/admin/modules.php' . '" >' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($title, $linkback, 'setup');

//====================================================================//
// Display Module Description
print "</br>" . $langs->trans("SPL_Full_Desc") . "</br></br>";

//====================================================================//
// Display Module Main Configuration Block
include("ConfigMain.php");
//====================================================================//
// Display Module Local Configuration Block
include("ConfigLocal.php");
include("ConfigOrders.php");
include("ConfigPayments.php");
//====================================================================//
// Display Module Self Tests
include("ServerTests.php");

//====================================================================//
//  Generic Page Footer
llxFooter('$Date: 2016-03-22 14:21:19 +0100 (mar., 22 mars 2016) $ - $Revision: 492 $');
