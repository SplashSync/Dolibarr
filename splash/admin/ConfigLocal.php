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



//====================================================================//
// Create Setup Form
echo    '<form name="MainSetup" action="'.  filter_input(INPUT_SERVER, "php_self").'" method="POST">';
echo    '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo    '<input type="hidden" name="action" value="UpdateLocal">';

//====================================================================//
// Open Local Configuration Tab
dol_fiche_head(array(), Null, $langs->trans("SPL_Local_Config") , 0, null);


echo '<table class="noborder" width="100%"><tbody>';
//====================================================================//
// Default Language Parameter

    //====================================================================//
    // Build Language Combo
    $langcombo	=	'<select name="DefaultLang" id="DefaultLang" class="form-control" >';
    foreach ($langs->get_available_languages() as $key => $value) {
        if ($conf->global->SPLASH_LANG == $key) {
            $langcombo .=  '<option value="' . $key . '" selected="true">' . $value . '</option>';
        } else {
            $langcombo .=  '<option value="' . $key . '">' . $value . '</option>';
        }        
    }
    $langcombo .= '</select>';    

echo '  <tr class="pair">';
echo '      <td>' . $langs->trans("SPL_DfLang") . '</td>';
echo '      <td width="30%">' . $langcombo . '</td>';
echo '  </tr>';
//====================================================================//
// Default User Parameter
echo '  <tr class="impair">';
echo '      <td>' . $langs->trans("SPL_DfUser") . '</td>';
echo '      <td>' . $form->select_dolusers($conf->global->SPLASH_USER,'user',1) . '</td>';
echo '  </tr>';
//====================================================================//
// Default Warehouse Parameter
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
$formproduct=new FormProduct($db);
echo '  <tr class="pair">';
echo '      <td>' . $langs->trans("SPL_DfStock") . '</td>';
echo '      <td>' . $formproduct->selectWarehouses($conf->global->SPLASH_STOCK,'stock','',1) . '</td>';
echo '  </tr>';
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
$form->select_types_paiements($conf->global->SPLASH_DEFAULT_PAYMENT,'paiementcode','',2);
echo '      </td>';
echo '  </tr>';
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
