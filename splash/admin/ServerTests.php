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

use Splash\Client\Splash;

global $db, $action, $conf, $langs, $error, $form;

//====================================================================//
// Open Connection Test Tab
dol_fiche_head(array(), "", $langs->trans("SPL_WsTest"), 0, "");

echo Splash::log()->GetHtmlLog(true);

//====================================================================//
// List Objects
//====================================================================//
$list = Splash::objects();
echo '<table class="noborder" width="100%"><tbody>';
echo '  <tr class="pair">';
echo '      <td width="60%">'.$langs->trans("SPL_ObjectsList").'</td>';
echo '      <td><ul>';
foreach ($list as $value) {
    echo "<li>".$value."</li>";
}
echo '      </ul></td>';
echo '  </tr>';
echo '</tbody></table>';
echo Splash::log()->GetHtmlLog(true);
echo "</br>";

//====================================================================//
// List Widgets
//====================================================================//
$Widgets = Splash::Widgets();
echo '<table class="noborder" width="100%"><tbody>';
echo '  <tr class="pair">';
echo '      <td width="60%">'.$langs->trans("SPL_WidgetsList").'</td>';
echo '      <td><ul>';
foreach ($Widgets as $value) {
    echo "<li>".$value."</li>";
}
echo '      </ul></td>';
echo '  </tr>';
echo '</tbody></table>';
echo Splash::log()->GetHtmlLog(true);
echo "</br>";

//====================================================================//
// Splash Server Configuration
//====================================================================//
echo '<table class="noborder" width="100%"><tbody>';
echo '  <tr class="impair">';
echo '      <td width="60%">'.$langs->trans("SPL_SelfTest").'</td>';
if (Splash::SelfTest()) {
    echo '      <td>'.img_picto("Ok", "tick")."&nbsp;".$langs->trans("SPL_SelfTestOk").'</td>';
} else {
    echo '      <td>'.img_picto("Ko", "high")."&nbsp;".$langs->trans("SPL_SelfTestKo").'</td>';
}
echo '  </tr>';
echo '</tbody></table>';
echo Splash::log()->GetHtmlLog(true);
echo "</br>";

//====================================================================//
// Splash Server Ping
//====================================================================//
echo '<table class="noborder" width="100%"><tbody>';
echo '  <tr class="impair">';
echo '      <td width="60%">'.$langs->trans("SPL_WsTestPing").'</td>';
if (Splash::Ping()) {
    echo '      <td>'.img_picto("Ok", "tick")."&nbsp;".$langs->trans("SPL_WsPingOk").'</td>';
} else {
    echo '      <td>'.img_picto("Ko", "high")."&nbsp;".$langs->trans("SPL_WsPingKo").'</td>';
}
echo '  </tr>';
echo '</tbody></table>';
echo Splash::log()->GetHtmlLog(true);
echo "</br>";

//====================================================================//
// Splash Server Connect
//====================================================================//
echo '<table class="noborder" width="100%"><tbody>';
echo '  <tr class="pair">';
echo '      <td width="60%">'.$langs->trans("SPL_WsTestConnect").'</td>';
if (Splash::Connect()) {
    echo '      <td>'.img_picto("Ok", "tick")."&nbsp;".$langs->trans("SPL_WsConnectOk").'</td>';
} else {
    echo '      <td>'.img_picto("Ko", "high")."&nbsp;".$langs->trans("SPL_WsConnectKo").'</td>';
}
echo '  </tr>';
echo '</tbody></table>';
echo Splash::log()->GetHtmlLog(true);
echo "</br>";

//====================================================================//
// Close Connection Test Tab
echo    '</div>';
