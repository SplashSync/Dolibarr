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
require( is_file("../../main.inc.php") ? "../../main.inc.php" : "../../../main.inc.php" ); 

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
if (!$user->admin)	accessforbidden();

//====================================================================//
// *******************************************************************//
// ACTIONS
// *******************************************************************//
//====================================================================//

//====================================================================//
// Update of Main Module Parameters
if ($action == 'UpdateMode')
{
    //====================================================================//
    // Update Server Expert Mode
    $WsExpert = GETPOST('WsExpert')?1:0;
    dolibarr_set_const($db,"SPLASH_WS_EXPERT",$WsExpert,'int',0,'',$conf->entity);
    if ( !$WsExpert ) {
        dolibarr_set_const($db,"SPLASH_WS_HOST","",'chaine',0,'',$conf->entity);
    }
    header("location:" . filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update of Main Module Parameters
if ($action == 'UpdateMain')
{
    //====================================================================//
    // Init DB Transaction
    $db->begin();
    
    $errors = 0;
    //====================================================================//
    // Update Server Id
    $WsId = GETPOST('WsId','alpha');
    if ( $WsId ) {
        if ( dolibarr_set_const($db,"SPLASH_WS_ID",$WsId,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }
    
    //====================================================================//
    // Update Server Encryption Key
    $WsKey = GETPOST('WsKey','alpha');
    if ( $WsKey ) {
        if ( dolibarr_set_const($db,"SPLASH_WS_KEY",$WsKey,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }
    
    //====================================================================//
    // Update Server Host Url
    $WsHost = GETPOST('WsHost','alpha');
    if ( $WsKey ) {
        if ( dolibarr_set_const($db,"SPLASH_WS_HOST",$WsHost,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }
    
    //====================================================================//
    // Update Protocl
    $WsMethod = GETPOST('WsMethod','alpha');
    if ( $WsMethod ) {
        if ( dolibarr_set_const($db,"SPLASH_WS_METHOD",$WsMethod,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }
    
    //====================================================================//
    // DB Commit & Display User Message
    if (! $error) {
        $db->commit();
        setEventMessage($langs->trans("SetupSaved"),'mesgs');   
    } else {
        $db->rollback();
        setEventMessage($langs->trans("Error"),'errors');   
    }
    
//====================================================================//
// Update of Main Module Parameters
} elseif ($action == 'UpdateLocal')  {  
    
    //====================================================================//
    // Init DB Transaction
    $db->begin();
    
    $errors = 0;
    //====================================================================//
    // Update Default Lang
    $DfLang = GETPOST('DefaultLang','alpha');
    if ( $DfLang ) {
        if ( dolibarr_set_const($db,"SPLASH_LANG",$DfLang,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }

    //====================================================================//
    // Update Default User
    $DfUser = GETPOST('user','alpha');
    if ( $DfUser ) {
        if ( dolibarr_set_const($db,"SPLASH_USER",$DfUser,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }

    //====================================================================//
    // Update Default Stock
    $DfStock = GETPOST('stock','alpha');
    if ( $DfUser ) {
        if ( dolibarr_set_const($db,"SPLASH_STOCK",$DfStock,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }
    
    //====================================================================//
    // Update Default MultiPrice
    $DfPrice = GETPOST('price_level','alpha');
    if ( $DfUser ) {
        if ( dolibarr_set_const($db,"SPLASH_MULTIPRICE_LEVEL",$DfPrice,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }    

    //====================================================================//
    // Update Default Bank Account Id
    $DfBank = GETPOST('accountid','alpha');
    if ( $DfUser ) {
        if ( dolibarr_set_const($db,"SPLASH_BANK",$DfBank,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }
    
    //====================================================================//
    // Update Default Payment Mode Id
    $DfPayMode = GETPOST('paiementcode','alpha');
    if ( $DfUser ) {
        if ( dolibarr_set_const($db,"SPLASH_DEFAULT_PAYMENT",$DfPayMode,'chaine',0,'',$conf->entity) <= 0){
            $errors++;
        }
    }

    //====================================================================//
    // DB Commit & Display User Message
    if (! $error) {
        $db->commit();
        setEventMessage($langs->trans("SetupSaved"),'mesgs');   
    } else {
        $db->rollback();
        setEventMessage($langs->trans("Error"),'errors');   
    }    
}

//====================================================================//
// Create Dolibarr Form Class
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
$form   =   new Form($db);

//====================================================================//
//====================================================================//
//   SHOW PAGE
//====================================================================//
//====================================================================//

//====================================================================//
// Display Page Header
llxHeader($header,$langs->trans("Setup"));

//====================================================================//
// Display Page Title
$title = $langs->trans("SPL_Name") . " - ". $langs->trans("Setup");
$linkback= '<a href="' . DOL_URL_ROOT.'/admin/modules.php' . '" >' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($title,$linkback,'setup');

//====================================================================//
// Display Module Description
print "</br>" . $langs->trans("SPL_Full_Desc") . "</br></br>";

//====================================================================//
// Display Module Main Congiguration Block
include("ConfigMain.php");
//====================================================================//
// Display Module Local Congiguration Block
include("ConfigLocal.php");
//====================================================================//
// Display Module Self Tests
include("ServerTests.php");
include("ServerDebug.php");

//====================================================================//
//  Generic Page Footer
llxFooter('$Date: 2016-03-22 14:21:19 +0100 (mar., 22 mars 2016) $ - $Revision: 492 $');
?>
