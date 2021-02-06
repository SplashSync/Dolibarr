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
echo    '<input type="hidden" name="action" value="UpdatePayments">';

//====================================================================//
// Open Local Configuration Tab
dol_fiche_head(array(), "", $langs->trans("SPL_Payment_Config"), 0, "");

echo '<table class="noborder" width="100%"><tbody>';

//====================================================================//
// Default Bank Account Parameter
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
$form = new Form($db);
echo '  <tr class="pair">';
echo '      <td>'.$langs->trans("SPL_DfBankAccount").'</td>';
echo '      <td>';
$form->select_comptes($conf->global->SPLASH_BANK);
echo '      </td>';
echo '  </tr>';

//====================================================================//
// Payment Methods to Bank Account Associations
$form->load_cache_types_paiements();
foreach ($form->cache_types_paiements as $PaymentType) {
    //====================================================================//
    // Filter Default & Disabled Payment Methods
    if (!$PaymentType["active"] || empty($PaymentType["label"])) {
        continue;
    }
    //====================================================================//
    // Render Account Select Line
    $ParameterName = "SPLASH_BANK_FOR_".$PaymentType["id"];
    echo '  <tr class="pair">';
    echo '      <td>'.$langs->trans("SPL_BankAccountFor", $PaymentType["label"], $PaymentType["code"]).'</td>';
    echo '      <td>';
    $form->select_comptes($conf->global->{$ParameterName}, $PaymentType["id"]);
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
echo    '           <a href="'.$langs->trans("SPL_Payment_Help").'" target="_blank">';
echo    '               <i class="fa fa-external-link">&nbsp;</i>';
echo                    $langs->trans("SPL_Help_Msg").'<i class="fa fa-question">&nbsp;</i>';
echo    '           </a>';
echo    '       </div>';
echo    '       <input type="submit" class="butAction" align="right" value="'.$langs->trans("Save").'">';
echo    '</div>';

//====================================================================//
// Close Main Configuration Form
echo    "</form>";
