<?php
/**
 * Bootstrap Dolibarr testing environment.
 */

require dirname(__DIR__) . "/splash/vendor/autoload.php";

global $db,$langs,$conf,$user,$hookmanager;

//====================================================================//
// Initiate Dolibarr Global Envirement Variables
require_once(dirname(dirname(__DIR__)) . "/master.inc.php");
//====================================================================//
// Functions Dolibarr
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

//====================================================================//
// Ensure Minimal Dolibarr Config
dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM", 'Splash Sync Tester', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "MAIN_INFO_SOCIETE_COUNTRY", '1:FR:France', 'chaine', 0, '', $conf->entity);

//====================================================================//
// Disable Log Module
dolibarr_set_const($db, "MAIN_MODULE_SYSLOG", '0', 'chaine', 0, '', 0);

//====================================================================//
// Setup Splash Module
dolibarr_set_const($db, "SPLASH_WS_ID", "12345678", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_WS_KEY", "001234567800", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_USER", "1", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_LANG", "fr_FR", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_STOCK", "1", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_BANK", "1", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_DEFAULT_PAYMENT", "CB", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_MULTIPRICE_LEVEL", "1", 'chaine', 0, '', $conf->entity);

//====================================================================//
// Activate Splash Module
activateModule("modSplash");
activateModule("modExpedition");
activateModule("modProductBatch");
activateModule("modAccounting");
