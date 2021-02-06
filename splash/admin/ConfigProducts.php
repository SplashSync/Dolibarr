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

use Splash\Core\SplashCore as Splash;

global $db, $action, $conf, $langs, $error, $form;

//====================================================================//
// Create Setup Form
echo    '<form name="MainSetup" action="'.filter_input(INPUT_SERVER, "PHP_SELF").'" method="POST">';
echo    '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo    '<input type="hidden" name="action" value="UpdateProducts">';

//====================================================================//
// Open Local Configuration Tab
dol_fiche_head(array(), "", $langs->trans("Products"), 0, "");

echo '<table class="noborder" width="100%"><tbody>';

//====================================================================//
// Default Warehouse Parameter
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
$formproduct = new FormProduct($db);

//====================================================================//
// Enable Products Multi-Warehouses Mode
if ($conf->global->SPLASH_WS_EXPERT) {
    echo '  <tr class="pair">';
    echo '      <td>'.$langs->trans("SPL_MultiStock").'</td>';
    if ($conf->global->SPLASH_MULTISTOCK) {
        echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateMultiStock&MultiStock=0">';
        echo img_picto($langs->trans("Enabled"), 'switch_on');
        echo '</a></td>';
    } else {
        echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateMultiStock&MultiStock=1">';
        echo img_picto($langs->trans("Disabled"), 'switch_off');
        echo '</a></td>';
    }
    echo '  </tr>';
}

if (empty($conf->global->SPLASH_WS_EXPERT) || empty($conf->global->SPLASH_MULTISTOCK)) {
    echo '  <tr class="pair">';
    echo '      <td>'.$langs->trans("SPL_DfStock").'</td>';
    echo '      <td>'.$formproduct->selectWarehouses($conf->global->SPLASH_STOCK, 'stock', '', 0).'</td>';
    echo '  </tr>';
}

//====================================================================//
// Products Default Warehouse Parameter
echo '  <tr class="pair">';
echo '      <td>'.$langs->trans("SPL_ProductStock").'</td>';
echo '      <td>'.$formproduct->selectWarehouses($conf->global->SPLASH_PRODUCT_STOCK, 'product_stock', '', 1).'</td>';
echo '  </tr>';

//====================================================================//
// If multiprices are enabled
if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
    //====================================================================//
    // Default Synchronized Product Price
    echo '  <tr class="pair">';
    echo '      <td>'.$langs->trans("SPL_DfMultiPrice").'</td>';
    echo '      <td>';

    print '<select name="price_level" class="flat">';
    // @phpstan-ignore-next-line
    for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
        print '<option value="'.$i.'"' ;
        // @phpstan-ignore-next-line
        if ($i == $conf->global->SPLASH_MULTIPRICE_LEVEL) {
            print 'selected';
        }
        print '>'.$langs->trans('SellingPrice')." ".$i;
        $keyforlabel = 'PRODUIT_MULTIPRICES_LABEL'.$i;
        if (! empty($conf->global->{$keyforlabel})) {
            print ' - '.$langs->trans($conf->global->{$keyforlabel});
        }
        print '</option>';
    }
    print '</select>';

    echo '      </td>';
    echo '  </tr>';
}

echo '</tbody></table>';

//====================================================================//
// Close Local Configuration Tab
echo "</div>";

//====================================================================//
// Display Save Btn | Help Link
echo    '<div class="tabsAction">';
echo    '      <div class="inline-block" >';
echo    '           <a href="'.$langs->trans("SPL_Local_Help").'" target="_blank">';
echo    '               <i class="fa fa-external-link">&nbsp;</i>';
echo                    $langs->trans("SPL_Help_Msg").'<i class="fa fa-question">&nbsp;</i>';
echo    '           </a>';
echo    '       </div>';
echo    '       <input type="submit" class="butAction" align="right" value="'.$langs->trans("Save").'">';
echo    '</div>';

//====================================================================//
// Close Main Configuration Form
echo    "</form>";
