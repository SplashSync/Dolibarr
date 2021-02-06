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
// Update Detect Tax Names Mode
if ('UpdateOrderTaxMode' == $action) {
    $detectTaxMode = GETPOST('DetectTax') ? "1": "0";
    dolibarr_set_const($db, "SPLASH_DETECT_TAX_NAME", $detectTaxMode, 'chaine', 0, '', $conf->entity);
    setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    header("location:".filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update Product SKU Detection Mode
if ('UpdateOrderDetectSkuMode' == $action) {
    $detectSkuMode = GETPOST('DetectSku') ? "1": "0";
    dolibarr_set_const($db, "SPLASH_DECTECT_ITEMS_BY_SKU", $detectSkuMode, 'chaine', 0, '', $conf->entity);
    setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    header("location:".filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update Allow Guests Orders
if ('UpdateOrderAllowGuest' == $action) {
    $allowGuest = GETPOST('AllowGuest') ? "1": "0";
    dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_ALLOW", $allowGuest, 'chaine', 0, '', $conf->entity);
    setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    header("location:".filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update Detcet Customer Email on Order Import
if ($conf->global->SPLASH_GUEST_ORDERS_ALLOW && ('UpdateOrderEmail' == $action)) {
    $detectEmail = GETPOST('DetectEmail')? "1": "0";
    dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_EMAIL", $detectEmail, 'chaine', 0, '', $conf->entity);
    setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    header("location:".filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update of Orders & Invoices Parameters
if ('UpdateOrder' == $action) {
    //====================================================================//
    // Init DB Transaction
    $db->begin();

    $errors = 0;

    //====================================================================//
    // Update Default Payment Mode Id
    $dfPayMode = GETPOST('paiementcode', 'alpha');
    if ($dfPayMode && !is_array($dfPayMode)) {
        if (dolibarr_set_const($db, "SPLASH_DEFAULT_PAYMENT", $dfPayMode, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }

    //====================================================================//
    // Update Default Guest Customer
    if ($conf->global->SPLASH_GUEST_ORDERS_ALLOW) {
        $socId = GETPOST('GuestCustomerid', 'alpha');
        if ($socId && !is_array($socId)) {
            if (dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_CUSTOMER", $socId, 'chaine', 0, '', $conf->entity) <= 0) {
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
