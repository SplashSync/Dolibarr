<?php
/**
 * Bootstrap Dolibarr for Pḧpstan
 */

require dirname(__DIR__) . "/splash/vendor/autoload.php";

global $db,$langs,$conf,$user,$hookmanager;

//====================================================================//
// Initiate Dolibarr Global Envirement Variables
require_once(dirname(dirname(__DIR__)) . "/master.inc.php");
//====================================================================//
// Functions Dolibarr
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

//include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';



////====================================================================//
//// Disable Log Module
//dolibarr_set_const($db, "MAIN_MODULE_SYSLOG", 0, 'chaine', 0, '', 0);
//
////====================================================================//
//// Setup Splash Module
//dolibarr_set_const($db, "SPLASH_WS_ID", "12345678", 'chaine', 0, '', $conf->entity);
//dolibarr_set_const($db, "SPLASH_WS_KEY", "001234567800", 'chaine', 0, '', $conf->entity);
//dolibarr_set_const($db, "SPLASH_USER", 1, 'chaine', 0, '', $conf->entity);
//dolibarr_set_const($db, "SPLASH_LANG", "fr_FR", 'chaine', 0, '', $conf->entity);
//dolibarr_set_const($db, "SPLASH_STOCK", 1, 'chaine', 0, '', $conf->entity);
//dolibarr_set_const($db, "SPLASH_BANK", 1, 'chaine', 0, '', $conf->entity);
//dolibarr_set_const($db, "SPLASH_DEFAULT_PAYMENT", "CB", 'chaine', 0, '', $conf->entity);
//dolibarr_set_const($db, "SPLASH_MULTIPRICE_LEVEL", 1, 'chaine', 0, '', $conf->entity);
//
////====================================================================//
//// Activate Splash Module
//activateModule("modSplash");

//
//try {
////    activateModule("modSplash", 1);
//} catch ( \PHPUnit\Framework\Exception $ex) {
//    echo $ex->getMessage();
//}
