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
use Splash\Local\Local;

global $db, $action, $conf, $langs, $error, $form;

//====================================================================//
// Create Setup Form
echo    '<form name="MainSetup" action="'.filter_input(INPUT_SERVER, "PHP_SELF").'" method="POST">';
echo    '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo    '<input type="hidden" name="action" value="UpdateOrder">';

//====================================================================//
// Open Local Configuration Tab
dol_fiche_head(array(), "", $langs->trans("SPL_Orders_Config"), 0, "");

echo '<table class="noborder" width="100%"><tbody>';

//====================================================================//
// Default Payment Method Parameter
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
$formproduct = new FormProduct($db);
echo '  <tr class="impair">';
echo '      <td>'.$langs->trans("SPL_DfPayMethod").'</td>';
echo '      <td>';
$form->select_types_paiements($conf->global->SPLASH_DEFAULT_PAYMENT, 'paiementcode', '', 2);
echo '      </td>';
echo '  </tr>';

//====================================================================//
// Tax Name Detection Mode
if (Local::dolVersionCmp("5.0.0") >= 0) {
    echo '  <tr class="pair">';
    echo '      <td>'.$form->textwithpicto(
        $langs->trans("SPL_DetectTaxName"),
        $langs->trans("SPL_DetectTaxName_T")
    ).'</td>';
    if ($conf->global->SPLASH_DETECT_TAX_NAME) {
        echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateOrderTaxMode&DetectTax=0">';
        echo img_picto($langs->trans("Enabled"), 'switch_on');
        echo '</a></td>';
    } else {
        echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateOrderTaxMode&DetectTax=1">';
        echo img_picto($langs->trans("Disabled"), 'switch_off');
        echo '</a></td>';
    }
    echo '  </tr>';
}

//====================================================================//
// Product SKU Detection Mode
echo '  <tr class="pair">';
echo '      <td>'.$form->textwithpicto(
    $langs->trans("SPL_DetectBySku"),
    $langs->trans("SPL_DetectBySku_T")
).'</td>';
if ($conf->global->SPLASH_DECTECT_ITEMS_BY_SKU) {
    echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateOrderDetectSkuMode&DetectSku=0">';
    echo img_picto($langs->trans("Enabled"), 'switch_on');
    echo '</a></td>';
} else {
    echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateOrderDetectSkuMode&DetectSku=1">';
    echo img_picto($langs->trans("Disabled"), 'switch_off');
    echo '</a></td>';
}
echo '  </tr>';

//====================================================================//
// Allow Import of Guests Orders & Invoices
echo '  <tr class="impair">';
echo '      <td>'.$form->textwithpicto(
    $langs->trans("SPL_Orders_Guest"),
    $langs->trans("SPL_Orders_Guest_T")
).'</td>';
if ($conf->global->SPLASH_GUEST_ORDERS_ALLOW) {
    echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateOrderAllowGuest&AllowGuest=0">';
    echo img_picto($langs->trans("Enabled"), 'switch_on');
    echo '</a></td>';
} else {
    echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateOrderAllowGuest&AllowGuest=1">';
    echo img_picto($langs->trans("Disabled"), 'switch_off');
    echo '</a></td>';
}
echo '  </tr>';

if ($conf->global->SPLASH_GUEST_ORDERS_ALLOW) {
    //====================================================================//
    // Select Guest Orders Customer
    echo '  <tr class="pair">';
    echo '      <td>'.$langs->trans("SPL_Orders_Guest_U").'</td>';
    echo '      <td>';
    echo $form->select_company(
        $conf->global->SPLASH_GUEST_ORDERS_CUSTOMER,
        'GuestCustomerid',
        '(s.client = 1 OR s.client = 3)',
        0,
        0,
        0
    );
    echo '      </td>';
    echo '  </tr>';

    //====================================================================//
    // Try to detect Customer Using Email
    echo '  <tr class="impair">';
    echo '      <td>'.$langs->trans("SPL_Orders_Email").'</td>';
    if ($conf->global->SPLASH_GUEST_ORDERS_EMAIL) {
        echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateOrderEmail&DetectEmail=0">';
        echo img_picto($langs->trans("Enabled"), 'switch_on');
        echo '</a></td>';
    } else {
        echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateOrderEmail&DetectEmail=1">';
        echo img_picto($langs->trans("Disabled"), 'switch_off');
        echo '</a></td>';
    }
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
echo    '           <a href="'.$langs->trans("SPL_Orders_Help").'" target="_blank">';
echo    '               <i class="fa fa-external-link">&nbsp;</i>';
echo                    $langs->trans("SPL_Help_Msg").'<i class="fa fa-question">&nbsp;</i>';
echo    '           </a>';
echo    '       </div>';
echo    '       <input type="submit" class="butAction" align="right" value="'.$langs->trans("Save").'">';
echo    '</div>';

//====================================================================//
// Close Main Configuration Form
echo    "</form>";
