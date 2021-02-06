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
// Update of Local Module Parameters
if ('UpdateLocal' == $action) {
    //====================================================================//
    // Init DB Transaction
    $db->begin();

    $errors = 0;
    //====================================================================//
    // Update Default Lang
    $dfLang = GETPOST('DefaultLang', 'alpha');
    if ($dfLang && !is_array($dfLang)) {
        if (dolibarr_set_const($db, "SPLASH_LANG", $dfLang, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }

    //====================================================================//
    // Update Other Langs
    $otherLangs = GETPOST('OtherLangs', 'alpha');
    if ($otherLangs && !is_array($otherLangs)) {
        if (dolibarr_set_const($db, "SPLASH_LANGS", serialize($otherLangs), 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }

    //====================================================================//
    // Update Default User
    $dfUser = GETPOST('user', 'alpha');
    if ($dfUser && !is_array($dfUser)) {
        if (dolibarr_set_const($db, "SPLASH_USER", $dfUser, 'chaine', 0, '', $conf->entity) <= 0) {
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
