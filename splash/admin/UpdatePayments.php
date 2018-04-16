<?php
/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//====================================================================//
// *******************************************************************//
// ACTIONS
// *******************************************************************//
//====================================================================//

//====================================================================//
// Update of Payments Parameters
if ($action == 'UpdatePayments') {
    //====================================================================//
    // Init DB Transaction
    $db->begin();
    
    $errors = 0;

    //====================================================================//
    // Update Default Bank Account Id
    $DfBank = GETPOST('accountid', 'alpha');
    if ($DfBank) {
        if (dolibarr_set_const($db, "SPLASH_BANK", $DfBank, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }
    
    //====================================================================//
    // Payment Methods to Bank Account Associations
    $form->load_cache_types_paiements();
    foreach ($form->cache_types_paiements as $Type) {
        //====================================================================//
        // Filter Default & Disabled Payment Methods
        if (!$Type["active"] || empty($Type["label"])) {
            continue;
        }
        //====================================================================//
        // Update Account Select
        $Acc = GETPOST($Type["id"], 'alpha');
        if ($Acc) {
            if (dolibarr_set_const($db, "SPLASH_BANK_FOR_".$Type["id"], $Acc, 'chaine', 0, '', $conf->entity) <= 0) {
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
