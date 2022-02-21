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

require dirname(__DIR__)."/splash/vendor/autoload.php";

global $db, $langs, $conf, $user, $hookmanager;

//====================================================================//
// Initiate Dolibarr Global Environment Variables
require_once(dirname(dirname(__DIR__))."/master.inc.php");
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
// Allow Negative Stocks
dolibarr_set_const($db, "STOCK_ALLOW_NEGATIVE_TRANSFER", '1', 'chaine', 0, '', 0);

//====================================================================//
// Setup Splash Module
dolibarr_set_const($db, "MAIN_MODULE_SPLASH", "1", 'chaine', 0, '', 0);
dolibarr_set_const($db, "MAIN_MODULE_SPLASH_TRIGGERS", "1", 'chaine', 0, '', 0);
dolibarr_set_const($db, "SPLASH_WS_ID", "12345678", 'chaine', 0, '', 0);
dolibarr_set_const($db, "SPLASH_WS_KEY", "001234567800", 'chaine', 0, '', 0);
dolibarr_set_const($db, "SPLASH_USER", "1", 'chaine', 0, '', 0);
dolibarr_set_const($db, "SPLASH_LANG", "fr_FR", 'chaine', 0, '', 0);

dolibarr_set_const($db, "SPLASH_LANGS", 'a:2:{i:0;s:5:"fr_BE";i:1;s:5:"en_US";}', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_STOCK", "1", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_PRODUCT_STOCK", "1", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_BANK", "1", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_DEFAULT_PAYMENT", "CB", 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "SPLASH_MULTIPRICE_LEVEL", "2", 'chaine', 0, '', $conf->entity);

//====================================================================//
// Activate Splash Module
activateModule("modSplash");
activateModule("modExpedition");
activateModule("modProductBatch");
activateModule("modAccounting");
