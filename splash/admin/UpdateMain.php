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

global $db, $action, $conf, $langs, $error, $form;

//====================================================================//
// *******************************************************************//
// ACTIONS
// *******************************************************************//
//====================================================================//

//====================================================================//
// Update of Main Module Parameters
if ('UpdateMode' == $action) {
    //====================================================================//
    // Update Server Expert Mode
    $wsExpert = GETPOST('WsExpert') ? "1" : "0";
    dolibarr_set_const($db, "SPLASH_WS_EXPERT", $wsExpert, 'int', 0, '', $conf->entity);
    if (!$wsExpert) {
        dolibarr_set_const($db, "SPLASH_WS_HOST", "", 'chaine', 0, '', $conf->entity);
    }
    header("location:".filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update of Main Module Parameters
if ('UpdateMain' == $action) {
    //====================================================================//
    // Init DB Transaction
    $db->begin();

    $errors = 0;
    //====================================================================//
    // Update Server Id
    $wsId = GETPOST('WsId', 'alpha');
    if ($wsId && !is_array($wsId)) {
        if (dolibarr_set_const($db, "SPLASH_WS_ID", $wsId, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }

    //====================================================================//
    // Update Server Encryption Key
    $wsKey = GETPOST('WsKey', 'alpha');
    if ($wsKey && !is_array($wsKey)) {
        if (dolibarr_set_const($db, "SPLASH_WS_KEY", $wsKey, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }

    //====================================================================//
    // Update Server Host Url
    $wsHost = GETPOST('WsHost', 'alpha');
    if ($wsHost && !is_array($wsHost)) {
        if (dolibarr_set_const($db, "SPLASH_WS_HOST", $wsHost, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }

    //====================================================================//
    // Update Protocol
    $wsMethod = GETPOST('WsMethod', 'alpha');
    if ($wsMethod && !is_array($wsMethod)) {
        if (dolibarr_set_const($db, "SPLASH_WS_METHOD", $wsMethod, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }

    //====================================================================//
    // Update Smart Notifications
    $smartNotify = GETPOST('SmartNotify') ? "1" : "0";
    if (dolibarr_set_const($db, "SPLASH_SMART_NOTIFY", $smartNotify, 'int', 0, '', $conf->entity) <= 0) {
        $errors++;
    }

    //====================================================================//
    // DB Commit & Display User Message
    if (! $error) {
        $db->commit();
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        $db->rollback();
        setEventMessage($langs->trans("Error"), 'errors');
    }
}
