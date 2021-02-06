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
// Update of Payments Parameters
if ('UpdatePayments' == $action) {
    //====================================================================//
    // Init DB Transaction
    $db->begin();

    $errors = 0;

    //====================================================================//
    // Update Default Bank Account Id
    $dfBank = GETPOST('accountid', 'alpha');
    if ($dfBank && !is_array($dfBank)) {
        if (dolibarr_set_const($db, "SPLASH_BANK", $dfBank, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }

    //====================================================================//
    // Payment Methods to Bank Account Associations
    $form->load_cache_types_paiements();
    foreach ($form->cache_types_paiements as $accType) {
        //====================================================================//
        // Filter Default & Disabled Payment Methods
        if (!$accType["active"] || empty($accType["label"])) {
            continue;
        }
        //====================================================================//
        // Update Account Select
        $accId = GETPOST($accType["id"], 'alpha');
        if ($accId && !is_array($accId)) {
            $res = dolibarr_set_const($db, "SPLASH_BANK_FOR_".$accType["id"], $accId, 'chaine', 0, '', $conf->entity);
            if ($res <= 0) {
                $errors++;
            }
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
