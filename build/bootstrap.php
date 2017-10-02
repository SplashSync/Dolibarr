<?php
/**
 * Bootstrap Dolibarr testing environment.
 */

require "../splash/vendor/autoload.php";

global $db,$langs,$conf,$user,$hookmanager;

//====================================================================//
// Initiate Dolibarr Global Envirement Variables
require_once( "../master.inc.php");  
     
//====================================================================//
// Activate Splash Module
activateModule("modSplash");

//====================================================================//
// Setup Splash Module
dolibarr_set_const($db,"SPLASH_WS_ID","12345678",'chaine',0,'',$conf->entity);
dolibarr_set_const($db,"SPLASH_WS_KEY","001234567800",'chaine',0,'',$conf->entity);
dolibarr_set_const($db,"SPLASH_USER",1,'chaine',0,'',$conf->entity);
dolibarr_set_const($db,"SPLASH_LANG","fr_FR",'chaine',0,'',$conf->entity);
dolibarr_set_const($db,"SPLASH_STOCK",1,'chaine',0,'',$conf->entity);
dolibarr_set_const($db,"SPLASH_BANK",1,'chaine',0,'',$conf->entity);
dolibarr_set_const($db,"SPLASH_DEFAULT_PAYMENT","CB",'chaine',0,'',$conf->entity);
dolibarr_set_const($db,"SPLASH_MULTIPRICE_LEVEL",1,'chaine',0,'',$conf->entity);
       