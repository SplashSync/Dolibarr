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
 *  \Id 	$Id: ConfigMain.php 493 2016-03-24 23:01:42Z Nanard33 $
 *  \version    $Revision: 493 $
 *  \ingroup    Splash - Dolibarr Synchronisation via WebService
 *  \brief      Display Module Tests Results
*/


//====================================================================//
// Create Setup Form
echo    '<form name="MainSetup" action="'.  filter_input(INPUT_SERVER, "PHP_SELF").'" method="POST">';
echo    '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo    '<input type="hidden" name="action" value="UpdateMain">';

//====================================================================//
// Open Main Configuration Tab
dol_fiche_head(array(), null, $langs->trans("SPL_Main_Config"), 0, null);


echo '<table class="noborder" width="100%"><tbody>';
//====================================================================//
// Node Id Parameter
echo '  <tr class="pair">';
echo '      <td>' . $form->textwithpicto($langs->trans("SPL_SiteId"), $langs->trans("SPL_SiteId_Tooltip")) . '</td>';
echo '      <td width="30%">';
echo '          <input type="text"  name="WsId" value="' . $conf->global->SPLASH_WS_ID . '" maxlength="32" size="50">';
echo '      </td>';
echo '  </tr>';
//====================================================================//
// Node Ws Key Parameter
echo '  <tr class="impair">';
echo '      <td>' . $form->textwithpicto($langs->trans("SPL_WsKey"), $langs->trans("SPL_WsKey_Tooltip")) . '</td>';
echo '      <td><input type="text"  name="WsKey" value="' . $conf->global->SPLASH_WS_KEY . '" size="50"></td>';
echo '  </tr>';
//====================================================================//
// Ws Expert Mode
echo '  <tr class="pair">';
echo '      <td>' . $form->textwithpicto(
    $langs->trans("SPL_WsExpert"),
    $langs->trans("SPL_WsExpert_Tooltip")
) . '</td>';
if ($conf->global->SPLASH_WS_EXPERT) {
    echo '<td><a href="' . filter_input(INPUT_SERVER, "PHP_SELF") . '?action=UpdateMode&WsExpert=0">';
        echo img_picto($langs->trans("Enabled"), 'switch_on');
    echo '</a></td>';
} else {
    echo '<td><a href="' . filter_input(INPUT_SERVER, "PHP_SELF") . '?action=UpdateMode&WsExpert=1">';
        echo img_picto($langs->trans("Disabled"), 'switch_off');
    echo '</a></td>';
}
echo '  </tr>';
//====================================================================//
// Ws Host Url Parameter
if ($conf->global->SPLASH_WS_EXPERT) {
    echo '  <tr class="pair">';
    echo '      <td>' . $form->textwithpicto(
        $langs->trans("SPL_WsHost"),
        $langs->trans("SPL_WsHost_Tooltip")
    ) . '</td>';
    echo '      <td>';
    echo '          <input type="text"  name="WsHost" value="' . $conf->global->SPLASH_WS_HOST . '" size="50">';
    echo '      </td>';
    echo '  </tr>';
}
//====================================================================//
// Ws Protocol Parameter
if ($conf->global->SPLASH_WS_EXPERT) {
    //====================================================================//
    // Default Synchronized Product Price
    echo '  <tr class="pair">';
    echo '      <td>' . $langs->trans("SPL_WsMethod") . '</td>';
    echo '      <td>';
    
    print '<select name="WsMethod" class="flat">';
    print '     <option value="NuSOAP" ' . ( ("NuSOAP" == $conf->global->SPLASH_WS_METHOD) ? 'selected' : '' ) . '>' ;
    print '         NuSOAP Librarie' ;
    print '     </option>' ;
    print '     <option value="SOAP" '   . ( ("SOAP" == $conf->global->SPLASH_WS_METHOD) ? 'selected' : '' ) . '>' ;
    print '         Generic PHP SOAP' ;
    print '     </option>' ;
    print '</select>';
    
    echo '      </td>';
    echo '  </tr>';
}
echo '</tbody></table>';

//====================================================================//
// Close Main Configuration Tab
echo "</div>";

//====================================================================//
// Display Form Submit Btn
echo    '<div class="tabsAction">';
echo    '   <input type="submit" class="butAction" align="right" value="'.$langs->trans("Save").'">';
echo    '</div>';

//====================================================================//
// Close Main Configuration Form
echo    "</form>";
