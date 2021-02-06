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

global $db, $action, $conf, $langs, $error;

//====================================================================//
// *******************************************************************//
// ACTIONS
// *******************************************************************//
//====================================================================//

//====================================================================//
// Update of Products Module Parameters
if ('UpdateMultiStock' == $action) {
    //====================================================================//
    // Update Server Expert Mode
    $multiStocks = GETPOST('MultiStock') ? "1": "0";
    dolibarr_set_const($db, "SPLASH_MULTISTOCK", $multiStocks, 'int', 0, '', $conf->entity);
    header("location:".filter_input(INPUT_SERVER, "PHP_SELF"));
}

//====================================================================//
// Update of Products Module Parameters
if ('UpdateProducts' == $action) {
    //====================================================================//
    // Init DB Transaction
    $db->begin();

    $errors = 0;

    //====================================================================//
    // Update Default Stock
    $dfStock = GETPOST('stock', 'alpha');
    if ($dfStock && !is_array($dfStock)) {
        if (dolibarr_set_const($db, "SPLASH_STOCK", $dfStock, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }
    //====================================================================//
    // Update Products Default Stock
    $dfProductStock = GETPOST('product_stock', 'alpha');
    if ($dfProductStock && !is_array($dfProductStock)) {
        if (dolibarr_set_const($db, "SPLASH_PRODUCT_STOCK", $dfProductStock, 'chaine', 0, '', $conf->entity) <= 0) {
            $errors++;
        }
    }
    //====================================================================//
    // Update Default MultiPrice
    $dfPrice = GETPOST('price_level', 'alpha');
    if ($dfPrice && !is_array($dfPrice)) {
        if (dolibarr_set_const($db, "SPLASH_MULTIPRICE_LEVEL", $dfPrice, 'chaine', 0, '', $conf->entity) <= 0) {
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
