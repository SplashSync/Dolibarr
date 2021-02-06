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
// Create Setup Form
echo    '<form name="MainSetup" action="'.filter_input(INPUT_SERVER, "PHP_SELF").'" method="POST">';
echo    '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo    '<input type="hidden" name="action" value="UpdateMain">';

//====================================================================//
// Open Main Configuration Tab
dol_fiche_head(array(), "", $langs->trans("SPL_Main_Config"), 0, "");

echo '<table class="noborder" width="100%"><tbody>';
//====================================================================//
// Node Id Parameter
echo '  <tr class="pair">';
echo '      <td>'.$form->textwithpicto($langs->trans("SPL_SiteId"), $langs->trans("SPL_SiteId_Tooltip")).'</td>';
echo '      <td width="30%">';
echo '          <input type="text"  name="WsId" value="'.$conf->global->SPLASH_WS_ID.'" maxlength="32" size="50">';
echo '      </td>';
echo '  </tr>';
//====================================================================//
// Node Ws Key Parameter
echo '  <tr class="impair">';
echo '      <td>'.$form->textwithpicto($langs->trans("SPL_WsKey"), $langs->trans("SPL_WsKey_Tooltip")).'</td>';
echo '      <td><input type="text"  name="WsKey" value="'.$conf->global->SPLASH_WS_KEY.'" size="50"></td>';
echo '  </tr>';
//====================================================================//
// Ws Expert Mode
echo '  <tr class="pair">';
echo '      <td>'.$form->textwithpicto(
    $langs->trans("SPL_WsExpert"),
    $langs->trans("SPL_WsExpert_Tooltip")
).'</td>';
if ($conf->global->SPLASH_WS_EXPERT) {
    echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateMode&WsExpert=0">';
    echo img_picto($langs->trans("Enabled"), 'switch_on');
    echo '</a></td>';
} else {
    echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateMode&WsExpert=1">';
    echo img_picto($langs->trans("Disabled"), 'switch_off');
    echo '</a></td>';
}
echo '  </tr>';
//====================================================================//
// Ws Host Url Parameter
if ($conf->global->SPLASH_WS_EXPERT) {
    echo '  <tr class="pair">';
    echo '      <td>'.$form->textwithpicto(
        $langs->trans("SPL_WsHost"),
        $langs->trans("SPL_WsHost_Tooltip")
    ).'</td>';
    echo '      <td>';
    echo '          <input type="text"  name="WsHost" value="'.$conf->global->SPLASH_WS_HOST.'" size="50">';
    echo '      </td>';
    echo '  </tr>';
}
//====================================================================//
// Ws Protocol Parameter
if ($conf->global->SPLASH_WS_EXPERT) {
    //====================================================================//
    // Default Synchronized Product Price
    echo '  <tr class="pair">';
    echo '      <td>'.$langs->trans("SPL_WsMethod").'</td>';
    echo '      <td>';

    print '<select name="WsMethod" class="flat">';
    print '     <option value="NuSOAP" '.(("NuSOAP" == $conf->global->SPLASH_WS_METHOD) ? 'selected' : '').'>' ;
    print '         NuSOAP Librarie' ;
    print '     </option>' ;
    print '     <option value="SOAP" '.(("SOAP" == $conf->global->SPLASH_WS_METHOD) ? 'selected' : '').'>' ;
    print '         Generic PHP SOAP' ;
    print '     </option>' ;
    print '</select>';

    echo '      </td>';
    echo '  </tr>';
}

//====================================================================//
// Smart Notifications
echo '  <tr class="pair">';
echo '      <td>'.$form->textwithpicto(
    $langs->trans("SPL_Smart"),
    $langs->trans("SPL_Smart_Tooltip")
).'</td>';
if ($conf->global->SPLASH_SMART_NOTIFY) {
    echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateMain&SmartNotify=0">';
    echo img_picto($langs->trans("Enabled"), 'switch_on');
    echo '</a></td>';
} else {
    echo '<td><a href="'.filter_input(INPUT_SERVER, "PHP_SELF").'?action=UpdateMain&SmartNotify=1">';
    echo img_picto($langs->trans("Disabled"), 'switch_off');
    echo '</a></td>';
}
echo '  </tr>';

echo '</tbody></table>';

//====================================================================//
// Close Main Configuration Tab
echo "</div>";

//====================================================================//
// Display Save Btn | Help Link
echo    '<div class="tabsAction">';
echo    '      <div class="inline-block" >';
echo    '           <a href="'.$langs->trans("SPL_Main_Help").'" target="_blank">';
echo    '               <i class="fa fa-external-link">&nbsp;</i>';
echo                    $langs->trans("SPL_Help_Msg").'<i class="fa fa-question">&nbsp;</i>';
echo    '           </a>';
echo    '       </div>';
echo    '       <input type="submit" class="butAction" align="right" value="'.$langs->trans("Save").'">';
echo    '</div>';

//====================================================================//
// Close Main Configuration Form
echo    "</form>";
