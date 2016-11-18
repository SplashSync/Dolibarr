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
 *  \Id 	$Id: ServerTests.php 527 2016-05-25 10:18:55Z Nanard33 $
 *  \version    $Revision: 527 $
 *  \ingroup    Splash - Dolibarr Synchronisation via WebService
 *  \brief      Display Module Tests Results
*/


//====================================================================//
// Open Connection Test Tab
dol_fiche_head(array(), Null, $langs->trans("SPL_WsTest") , 0, null);

echo Splash::Log()->GetHtmlLog(True);

//====================================================================//
// List Objects
//====================================================================//
$list   =   Splash::Objects();
echo '<table class="noborder" width="100%"><tbody>';
echo '  <tr class="pair">';
echo '      <td width="60%">' . $langs->trans("SPL_ObjectsList") . '</td>';
echo '      <td><ul>';
foreach ($list as $value) {
    echo "<li>" . $value . "</li>";
}
echo '      </ul></td>';
echo '  </tr>';
echo '</tbody></table>';
echo Splash::Log()->GetHtmlLog(True);
echo "</br>";

//====================================================================//
// Splash Server Configuration
//====================================================================//
echo '<table class="noborder" width="100%"><tbody>';
echo '  <tr class="impair">';
echo '      <td width="60%">' . $langs->trans("SPL_SelfTest") . '</td>';
if ( Splash::SelfTest() ) {
    echo '      <td>' . img_picto("Ok","tick") . "&nbsp;" . $langs->trans("SPL_SelfTestOk") . '</td>';
} else {
    echo '      <td>' . img_picto("Ko","high") . "&nbsp;" . $langs->trans("SPL_SelfTestKo") . '</td>';
}
echo '  </tr>';
echo '</tbody></table>';
echo Splash::Log()->GetHtmlLog(True);
echo "</br>";

//====================================================================//
// Splash Server Ping
//====================================================================//
echo '<table class="noborder" width="100%"><tbody>';
echo '  <tr class="impair">';
echo '      <td width="60%">' . $langs->trans("SPL_WsTestPing") . '</td>';
if ( Splash::Ping() ) {
    echo '      <td>' . img_picto("Ok","tick") . "&nbsp;" . $langs->trans("SPL_WsPingOk") . '</td>';
} else {
    echo '      <td>' . img_picto("Ko","high") . "&nbsp;" . $langs->trans("SPL_WsPingKo") . '</td>';
}
echo '  </tr>';
echo '</tbody></table>';
echo Splash::Log()->GetHtmlLog(True);
echo "</br>";

//====================================================================//
// Splash Server Connect
//====================================================================//
echo '<table class="noborder" width="100%"><tbody>';
echo '  <tr class="pair">';
echo '      <td width="60%">' . $langs->trans("SPL_WsTestConnect") . '</td>';
if ( Splash::Connect() ) {
    echo '      <td>' . img_picto("Ok","tick") . "&nbsp;" . $langs->trans("SPL_WsConnectOk") . '</td>';
} else {
    echo '      <td>' . img_picto("Ko","high") . "&nbsp;" . $langs->trans("SPL_WsConnectKo") . '</td>';
}
echo '  </tr>';
echo '</tbody></table>';
echo Splash::Log()->GetHtmlLog(True);
echo "</br>";

//====================================================================//
// Close Connection Test Tab
echo    '</div>';

