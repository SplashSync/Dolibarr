<?php
/*
 * Copyright (C) 2011-2014  Bernard Paquier       <bernard.paquier@gmail.com>
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
 *  \Id 	$Id: osws-local-Customers.class.php 92 2014-09-16 22:18:01Z Nanard33 $
 *  \version    $Revision: 92 $
 *  \date       $LastChangedDate: 2014-09-17 00:18:01 +0200 (mer. 17 sept. 2014) $
 *  \ingroup    Splash - Open Synchronisation WebService
 *  \brief      Local Function Definition for Management of Customers Data
 *  \class      SplashDemo
 *  \remarks	Designed for Splash Module - Dolibar ERP Version
*/
                    
//====================================================================//
// *******************************************************************//
//                     SPLASH FOR DOLIBARR                            //
// *******************************************************************//
//                     LAST CUSTOMER BOX WIDGET                       //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Widgets;

use Splash\Models\WidgetBase;
use Splash\Core\SplashCore      as Splash;

class UpdatedCustomers extends WidgetBase
{
    
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Widget Disable Flag. Uncomment thius line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Widget Name (Translated by Module)
     */
    protected static $NAME            =  "BoxLastCustomers";
    
    /**
     *  Widget Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "BoxTitleLastModifiedCustomers";
    
    /**
     *  Widget Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO            =  "fa fa-users";
    
    //====================================================================//
    // Define Standard Options for this Widget
    // Override this array to change default options for your widget
    public static $OPTIONS       = array(
        "Width"         =>  self::SIZE_M,
        "Header"        =>  true,
        "Footer"        =>  false
    );
    
    //====================================================================//
    // General Class Variables
    //====================================================================//

    private $MaxItems   =   10;
    
    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    public function __construct()
    {
        //====================================================================//
        // Load Default Language
        Splash::local()->LoadDefaultLanguage();
    }
    

    /**
     *      @abstract   Return Widget Customs Parameters
     */
    public function getParameters()
    {
        global $langs;
        $langs->load("admin");
        
        //====================================================================//
        // Max Number of Entities
        $this->fieldsFactory()->Create(SPL_T_INT)
                ->Identifier("max")
                ->Name($langs->trans("MaxNbOfLinesForBoxes"))
                ->Description($langs->trans("BoxTitleLastModifiedCustomers"));
      
        //====================================================================//
        // Publish Fields
        return $this->fieldsFactory()->Publish();
    }
    
    /**
     *  @abstract     Return requested Customer Data
     *
     *  @param        array   $params               Search parameters for result List.
     *                        $params["start"]      Maximum Number of results
     *                        $params["end"]        List Start Offset
     *                        $params["groupby"]    Field name for sort list (Available fields listed below)

     */
    public function get($params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Default Language
        Splash::local()->LoadDefaultLanguage();
        //====================================================================//
        // Setup Widget Core Informations
        //====================================================================//

        $this->setTitle($this->getName());
        $this->setIcon($this->getIcon());
        
        //====================================================================//
        // Build Disabled Block
        //====================================================================//
        $this->buildDisabledBlock();
          
        //====================================================================//
        // Build Disabled Block
        //====================================================================//
        $this->MaxItems = !empty($params["max"]) ? $params["max"] : 10;
        $this->buildTableBlock();
        
        //====================================================================//
        // Set Blocks to Widget
        $this->setBlocks($this->blocksFactory()->Render());

        //====================================================================//
        // Publish Widget
        return $this->render();
    }
        

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    /**
    *   @abstract     Block Building - Box is Disabled
    */
    private function buildDisabledBlock()
    {

        global $langs, $user;
        
        if (!$user->rights->societe->lire) {
            $langs->load("admin");
            $Contents   = array("warning"   => $langs->trans("PreviewNotAvailable"));
            //====================================================================//
            // Warning Block
            $this->blocksFactory()->addNotificationsBlock($Contents);
        }
    }
  
    /**
    *   @abstract     Block Building - Text Intro
    */
    private function buildTableBlock()
    {

        global $langs, $db, $user;
        
        if (!$user->rights->societe->lire) {
            return;
        }
        
        //====================================================================//
        // Execute SQL Request
        //====================================================================//
        $sql = "SELECT s.nom as name, s.rowid as socid, s.tms as modified, s.status as status";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
        $sql.= " ORDER BY s.tms DESC";
        $sql.= $db->plimit($this->MaxItems, 0);
        dol_syslog(get_class($this)."::loadLastModifiedUsers", LOG_DEBUG);
        $Result = $db->query($sql);
        
        //====================================================================//
        // Empty Contents
        //====================================================================//
        if ($db->num_rows($Result) < 1) {
            $langs->load("admin");
            $Contents   = array("warning"   => $langs->trans("NoRecordedCustomers"));
            //====================================================================//
            // Warning Block
            $this->blocksFactory()->addNotificationsBlock($Contents);
            
            return;
        }
        
        //====================================================================//
        // Build Table Contents
        //====================================================================//
        $langs->load('companies');
        $Contents   = array();
        $num        = $db->num_rows($Result);           // Read number of results
        $index      = 0;
        
        while ($index < $num) {
            $Value = $db->fetch_array($Result);
            $Name = '<i class="fa fa-building-o" aria-hidden="true">&nbsp;-&nbsp;</i>' . $Value["name"];
            if ($Value["status"]) {
                $Status = '<i class="fa fa-check-circle-o text-success" aria-hidden="true">&nbsp;';
                $Status.= $langs->trans("InActivity").'</i>';
            } else {
                $Status = '<i class="fa fa-times-circle-o text-danger" aria-hidden="true">&nbsp;';
                $Status.= $langs->trans("ActivityCeased").'</i>';
            }
            $Contents[] = array($Name, $Value["modified"], $Status);
            $index++;
        }
        //====================================================================//
        // Build Table Options
        //====================================================================//
        $Options = array(
            "AllowHtml"         => true,
            "HeadingRows"       => 0,
        );
        //====================================================================//
        // Add Table Block
        $this->blocksFactory()->addTableBlock($Contents, $Options);
    }

    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

    //====================================================================//
    // Overide Splash Functions
    //====================================================================//

    /**
     *      @abstract   Return name of this Widget Class
     */
    public function getName()
    {
        global $langs;
        $langs->load("boxes");
        return html_entity_decode($langs->trans(static::$NAME));
    }

    /**
     *      @abstract   Return Description of this Widget Class
     */
    public function getDesc()
    {
        global $langs;
        $langs->load("boxes");
        return html_entity_decode($langs->trans(static::$DESCRIPTION));
    }
}
