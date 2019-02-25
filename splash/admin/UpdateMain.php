<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

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
    $WsExpert = GETPOST('WsExpert')?1:0;
    dolibarr_set_const($db, "SPLASH_WS_EXPERT", $WsExpert, 'int', 0, '', $conf->entity);
    if (!$WsExpert) {
        dolibarr_set_const($db, "SPLASH_WS_HOST", "", 'chaine', 0, '', $conf->entity);
    }
    header("location:" . filter_input(INPUT_SERVER, "PHP_SELF"));
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
    $WsId = GETPOST('WsId', 'alpha');
    if ($WsId) {
        if (dolibarr_set_const($db, "SPLASH_WS_ID", $WsId, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }
    
    //====================================================================//
    // Update Server Encryption Key
    $WsKey = GETPOST('WsKey', 'alpha');
    if ($WsKey) {
        if (dolibarr_set_const($db, "SPLASH_WS_KEY", $WsKey, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }
    
    //====================================================================//
    // Update Server Host Url
    $WsHost = GETPOST('WsHost', 'alpha');
    if ($WsKey) {
        if (dolibarr_set_const($db, "SPLASH_WS_HOST", $WsHost, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }
    
    //====================================================================//
    // Update Protocl
    $WsMethod = GETPOST('WsMethod', 'alpha');
    if ($WsMethod) {
        if (dolibarr_set_const($db, "SPLASH_WS_METHOD", $WsMethod, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
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
