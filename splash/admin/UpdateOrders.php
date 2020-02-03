<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
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
// Update Detect Tax Names Mode
if ('UpdateOrderTaxMode' == $action) {
    $DetectTaxMode = GETPOST('DetectTax')?1:0;
    dolibarr_set_const($db, "SPLASH_DETECT_TAX_NAME", $DetectTaxMode, 'chaine', 0, '', $conf->entity);
    setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    header("location:".filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update Product SKU Detection Mode
if ('UpdateOrderDetectSkuMode' == $action) {
    $detectSkuMode = GETPOST('DetectSku')?1:0;
    dolibarr_set_const($db, "SPLASH_DECTECT_ITEMS_BY_SKU", $detectSkuMode, 'chaine', 0, '', $conf->entity);
    setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    header("location:".filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update Allow Guests Orders
if ('UpdateOrderAllowGuest' == $action) {
    $AllowGuest = GETPOST('AllowGuest')?1:0;
    dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_ALLOW", $AllowGuest, 'chaine', 0, '', $conf->entity);
    setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    header("location:".filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update Detcet Customer Email on Order Import
if ($conf->global->SPLASH_GUEST_ORDERS_ALLOW && ('UpdateOrderEmail' == $action)) {
    $DetectEmail = GETPOST('DetectEmail')?1:0;
    dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_EMAIL", $DetectEmail, 'chaine', 0, '', $conf->entity);
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
    $DfPayMode = GETPOST('paiementcode', 'alpha');
    if ($DfPayMode) {
        if (dolibarr_set_const($db, "SPLASH_DEFAULT_PAYMENT", $DfPayMode, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }

    //====================================================================//
    // Update Default Guest Customer
    if ($conf->global->SPLASH_GUEST_ORDERS_ALLOW) {
        $SocId = GETPOST('GuestCustomerid', 'alpha');
        if ($SocId) {
            if (dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_CUSTOMER", $SocId, 'chaine', 0, '', $conf->entity) <= 0) {
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
