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
//                  BANK ACCOUNTS LEVELS WIDGET                       //
// *******************************************************************//
//====================================================================//

class SplashBankAccounts extends SplashWidget
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
    protected static    $NAME            =  "BoxCurrentAccounts";
    
    /**
     *  Widget Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "BoxTitleCurrentAccounts";    
    
    /**
     *  Widget Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO            =  "fa fa-money";
    
    //====================================================================//
    // Define Standard Options for this Widget
    // Override this array to change default options for your widget
    static $OPTIONS       = array(
        "Width"         =>  self::SIZE_M,
        "Header"        =>  True,
        "Footer"        =>  False
    );
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//

    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    /**
     *      @abstract   Return Widget Customs Parameters
     */
    public function getParameters()
    {
        global $langs;
        $langs->load("admin");
        
        //====================================================================//
        // Max Number of Entities
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("max")
                ->Name($langs->trans("MaxNbOfLinesForBoxes"));
        
        //====================================================================//
        // Max Number of Entities
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("compact")
                ->Name($langs->trans("Compact Mode"));
      
        //====================================================================//
        // Publish Fields
        return $this->FieldsFactory()->Publish();
    }       
    
    /**
     *  @abstract     Return requested Customer Data
     * 
     *  @param        array   $params               Search parameters for result List. 
     *                        $params["start"]      Maximum Number of results 
     *                        $params["end"]        List Start Offset 
     *                        $params["groupby"]    Field name for sort list (Available fields listed below)    

     */
    public function Get($params=NULL)
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Load Default Language
        Splash::Local()->LoadDefaultLanguage();

        //====================================================================//
        // Load Dolibarr Box Class & Prepare Datas
        include_once DOL_DOCUMENT_ROOT.'/core/boxes/box_comptes.php';        
        $this->Box = new box_comptes($db);
        $this->Box->loadBox($params["max"] ? $params["max"]: Null );
        
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
        // Build Data Blocks
        //====================================================================//
        if( $params["compact"]) {
            $this->buildSparkBlock();
        } else {
            $this->buildTableBlock();
        }
        
        //====================================================================//
        // Set Blocks to Widget
        $this->setBlocks($this->BlocksFactory()->Render());

        //====================================================================//
        // Publish Widget
        return $this->Render();
    }
        

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    /**
    *   @abstract     Block Building - Box is Disabled
    */
    private function buildDisabledBlock()   {

        global $langs;
        
        if ( !$this->Box->enabled ) {
            $langs->load("admin");
            $Contents   = array("warning"   => $langs->trans("PreviewNotAvailable"));
            //====================================================================//
            // Warning Block
            $this->BlocksFactory()->addNotificationsBlock($Contents);
        }
    }    
  
    /**
    *   @abstract     Block Building - Text Intro
    */
    private function buildTableBlock()   {

        global $langs;
        
        if ( count($this->Box->info_box_contents) < 1 ) {
            $langs->load("admin");
            $Contents   = array("warning"   => $langs->trans("ReadPermissionNotAllowed"));
            //====================================================================//
            // Warning Block
            $this->BlocksFactory()->addNotificationsBlock($Contents);
            
            return;
        } 
        
        //====================================================================//
        // Build Table Contents
        //====================================================================//
        $Contents   = array();
        $Prefix     = '<i class="fa fa-money" aria-hidden="true">&nbsp;-&nbsp;</i>';;
     
        foreach ($this->Box->info_box_contents as $Line) {
            
            if ( $Line[1]["text"] != "&nbsp;" ) {
                $Text = $Prefix . $Line[1]["text"] ;
            } else {
                $Text = $Line[0]["text"] ;
            }
            
            $Contents[] = array(
                $Text,   
                $Line[3]["text"],   
            );
            
        }
        //====================================================================//
        // Build Table Options
        //====================================================================//
        $Options = array(
            "AllowHtml"         => True,
            "HeadingRows"       => 0,
        );
        //====================================================================//
        // Add Table Block
        $this->BlocksFactory()->addTableBlock($Contents,$Options);
        
    }      

    /**
    *   @abstract     Block Building - Text Intro
    */
    private function buildSparkBlock()   {

        global $langs;
        
        if ( count($this->Box->info_box_contents) < 1 ) {
            $langs->load("admin");
            $Contents   = array("warning"   => $langs->trans("ReadPermissionNotAllowed"));
            //====================================================================//
            // Warning Block
            $this->BlocksFactory()->addNotificationsBlock($Contents);
            
            return;
        } 
        
        //====================================================================//
        // Build SparkInfo Options
        //====================================================================//
        $Options = array(
            "AllowHtml"         =>  True,
            "Width"             =>  self::SIZE_SM
        );
        
        //====================================================================//
        // Build SparkInfo Contents
        //====================================================================//
        foreach ($this->Box->info_box_contents as $Line) {
            
            if ( $Line[1]["text"] != "&nbsp;" ) {
                $Text = $Line[1]["text"] ;
            } else {
                $Text = '<b class="text-danger">' . $Line[0]["text"] . "</b>";
            }
            
            $Contents = array(
                "title"     =>      $Text,   
                "fa_icon"   =>      "money",
                "value"     =>      $Line[3]["text"],   
            );
            //====================================================================//
            // Add SparkInfo Block
            $this->BlocksFactory()->addSparkInfoBlock($Contents, $Options );
            
        }

        
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



?>
