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
echo    '<input type="hidden" name="action" value="UpdateLocal">';

//====================================================================//
// Open Local Configuration Tab
dol_fiche_head(array(), null, $langs->trans("SPL_Local_Config"), 0, null);


echo '<table class="noborder" width="100%"><tbody>';

//====================================================================//
// Build Language Combo
$langcombo  =   '<select name="DefaultLang" id="DefaultLang" class="form-control" >';
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
echo '      <td>';
echo $form->select_dolusers($conf->global->SPLASH_USER, 'user', 1, null, 0, '', '', $conf->entity);
echo '      </td>';
echo '  </tr>';
//====================================================================//
// Default Warehouse Parameter
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
$formproduct=new FormProduct($db);
echo '  <tr class="pair">';
echo '      <td>' . $langs->trans("SPL_DfStock") . '</td>';
echo '      <td>' . $formproduct->selectWarehouses($conf->global->SPLASH_STOCK, 'stock', '', 1) . '</td>';
echo '  </tr>';


//====================================================================//
// If multiprices are enabled
if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
    //====================================================================//
    // Default Synchronized Product Price
    echo '  <tr class="pair">';
    echo '      <td>' . $langs->trans("SPL_DfMultiPrice") . '</td>';
    echo '      <td>';
    
    print '<select name="price_level" class="flat">';
    for ($i=1; $i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
        print '<option value="'.$i.'"' ;
        if ($i == $conf->global->SPLASH_MULTIPRICE_LEVEL) {
            print 'selected';
        }
        print '>'. $langs->trans('SellingPrice') . " " .$i;
        $keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$i;
        if (! empty($conf->global->$keyforlabel)) {
                    print ' - '.$langs->trans($conf->global->$keyforlabel);
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
echo    '           <a href="' . $langs->trans("SPL_Local_Help") . '" target="_blank">';
echo    '               <i class="fa fa-external-link">&nbsp;</i>' . $langs->trans("SPL_Help_Msg") . '<i class="fa fa-question">&nbsp;</i>';
echo    '           </a>';
echo    '       </div>';
echo    '       <input type="submit" class="butAction" align="right" value="'.$langs->trans("Save").'">';
echo    '</div>';

//====================================================================//
// Close Main Configuration Form
echo    "</form>";
