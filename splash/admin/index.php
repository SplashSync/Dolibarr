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

use Splash\Client\Splash;

global $db, $user, $langs, $header;

//====================================================================//
//   INCLUDES
//====================================================================//
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];
$tmp2 = (string) realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i], $tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include(substr($tmp, 0, ($i + 1))."/main.inc.php");
}
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php");
}
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) {
    $res = @include("../../main.inc.php");
}
if (! $res && file_exists("../../../main.inc.php")) {
    $res = @include("../../../main.inc.php");
}
if (! $res && file_exists("../../../../main.inc.php")) {
    $res = @include("../../../../main.inc.php");
}
if (! $res) {
    die("Include of main fails");
}

//====================================================================//
// Splash Module Definitions
include_once(dirname(dirname(__FILE__))."/_conf/defines.inc.php");

//====================================================================//
// Functions Dolibarr
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

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
$node = GETPOST("node");
$ObjectType = GETPOST("ObjectType");
$action = GETPOST("action");
$show = GETPOST("show");

// Protection if not admin user
if (!$user->admin) {
    accessforbidden();
}

//====================================================================//
// Create Dolibarr Form Class
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
$form = new Form($db);

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
// Update of Products Parameters
include("UpdateProducts.php");
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
$title = $langs->trans("SPL_Name")." - ".$langs->trans("Setup");
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php'.'" >'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($title, $linkback, 'setup');

//====================================================================//
// Display Module Description
print "</br>".$langs->trans("SPL_Full_Desc")."</br></br>";

//====================================================================//
// Display Module Main Configuration Block
include("ConfigMain.php");
//====================================================================//
// Display Module Local Configuration Block
include("ConfigLocal.php");
include("ConfigProducts.php");
include("ConfigOrders.php");
include("ConfigPayments.php");
//====================================================================//
// Display Module Self Tests
include("ServerTests.php");

//====================================================================//
//  Generic Page Footer
llxFooter('$Date: 2016-03-22 14:21:19 +0100 (mar., 22 mars 2016) $ - $Revision: 492 $');
