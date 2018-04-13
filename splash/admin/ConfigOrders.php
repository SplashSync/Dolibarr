<?php
/*
 * Copyright (C) 2011 Bernard Paquier       <bernard.paquier@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 *
 *  \Id 	$Id: ConfigLocal.php 492 2016-03-22 13:21:19Z Nanard33 $
 *  \version    $Revision: 492 $
 *  \ingroup    Splash - Dolibarr Synchronisation via WebService
 *  \brief      Display Module Tests Results
*/

use Splash\Core\SplashCore as Splash;

//====================================================================//
// Create Setup Form
echo    '<form name="MainSetup" action="'.  filter_input(INPUT_SERVER, "php_self").'" method="POST">';
echo    '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo    '<input type="hidden" name="action" value="UpdateOrder">';

//====================================================================//
// Open Local Configuration Tab
dol_fiche_head(array(), null, $langs->trans("SPL_Orders_Config"), 0, null);


echo '<table class="noborder" width="100%"><tbody>';

//====================================================================//
// Default Bank Account Parameter
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
$form=  new Form($db);
echo '  <tr class="pair">';
echo '      <td>' . $langs->trans("SPL_DfBankAccount") . '</td>';
echo '      <td>';
$form->select_comptes($conf->global->SPLASH_BANK);
echo '      </td>';
echo '  </tr>';
//====================================================================//
// Default Payment Method Parameter
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
$formproduct=new FormProduct($db);
echo '  <tr class="pair">';
echo '      <td>' . $langs->trans("SPL_DfPayMethod") . '</td>';
echo '      <td>';
$form->select_types_paiements($conf->global->SPLASH_DEFAULT_PAYMENT, 'paiementcode', '', 2);
echo '      </td>';
echo '  </tr>';

//====================================================================//
// Tax Name Detection Mode
if (Splash::local()->dolVersionCmp("5.0.0") >= 0) {
    echo '  <tr class="pair">';
    echo '      <td>' . $form->textwithpicto(
        $langs->trans("SPL_DetectTaxName"),
        $langs->trans("SPL_DetectTaxName_T")
    ) . '</td>';
    if ($conf->global->SPLASH_DETECT_TAX_NAME) {
        echo '<td><a href="' . filter_input(INPUT_SERVER, "PHP_SELF") . '?action=UpdateOrder&DetectTax=0">';
            echo img_picto($langs->trans("Enabled"), 'switch_on');
        echo '</a></td>';
    } else {
        echo '<td><a href="' . filter_input(INPUT_SERVER, "PHP_SELF") . '?action=UpdateOrder&DetectTax=1">';
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
// Display Form Submit Btn
echo    '<div class="tabsAction">';
echo    '   <input type="submit" class="butAction" align="right" value="'.$langs->trans("Save").'">';
echo    '</div>';

//====================================================================//
// Close Main Configuration Form
echo    "</form>";
