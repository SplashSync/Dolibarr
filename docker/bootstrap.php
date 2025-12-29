<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
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
dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM", 'Dolibarr for Splash', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, "MAIN_INFO_SOCIETE_COUNTRY", '1:FR:France', 'chaine', 0, '', $conf->entity);

//====================================================================//
// Activate Splash Module
activateModule("modSplash");
activateModule("modExpedition");
activateModule("modProductBatch");
activateModule("modAccounting");
