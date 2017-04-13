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

namespace   Splash\Local\Widgets;

use Splash\Models\WidgetBase;
use Splash\Core\SplashCore      as Splash;

class BankAccounts extends WidgetBase
{
    
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
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
        "Footer"        =>  False,
        'UseCache'      =>  True,
        'CacheLifeTime' =>  60,
    );
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//

    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    public function __construct() {
        //====================================================================//
        // Load Default Language
        Splash::Local()->LoadDefaultLanguage();
    }
    
    /**
     *      @abstract   Return Widget Customs Parameters
     */
    public function getParameters()
    {
        global $langs;
        $langs->load("admin");
        
        //====================================================================//
        // Use Compact Mode
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
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Load Default Language
        Splash::Local()->LoadDefaultLanguage();

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
        $this->MaxItems = !empty($params["max"]) ? $params["max"] : 10;
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

        global $langs, $user;
        
        if ( !$user->rights->banque->lire ) {
            $langs->load("admin");
            $Contents   = array("warning"   => $langs->trans("ReadPermissionNotAllowed"));
            //====================================================================//
            // Warning Block
            $this->BlocksFactory()->addNotificationsBlock($Contents);
        }
    }    
  
    /**
     * @abstract    Read Widget Datas
     */
    private function getData()   {

        global $langs, $user, $db, $conf;
        
        if ( !$user->rights->banque->lire ) {
            return array();
        }
        
        //====================================================================//
        // Execute SQL Request
        //====================================================================//
        $sql = "SELECT rowid, ref, label, bank, clos, account_number, currency_code, min_desired, comment";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
        $sql.= " WHERE entity = ".$conf->entity;
        $sql.= " AND clos = 0";
        $sql.= " ORDER BY label";
        $sql.= $db->plimit($this->MaxItems, 0);
        dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
        $Result = $db->query($sql);
        
        //====================================================================//
        // Empty Contents
        //====================================================================//
        if ( count($Result) < 1 ) {
            $langs->load("admin");
            $Contents   = array("warning"   => $langs->trans("PreviewNotAvailable"));
            //====================================================================//
            // Warning Block
            $this->BlocksFactory()->addNotificationsBlock($Contents);
            return array();
        } 
        
        return $Result;
    }
        
    /**
    *   @abstract     Block Building - Text Intro
    */
    private function buildTableBlock()   {

        global $langs, $db;
        
        $Data   = $this->getData();
        
        //====================================================================//
        // Build Table Contents
        //====================================================================//
        $Contents       = array();
        include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
        $account_static = new \Account($db);
        $Prefix     = '<i class="fa fa-university" aria-hidden="true">&nbsp;</i>';
     
        foreach ($Data as $Line) {
            $account_static->id         = $Line["rowid"];
            $account_static->label      = $Line["label"];
            $account_static->number     = $Line["number"];
            $solde=$account_static->solde(0);
                    
            if ($solde < 0) {
                $Value = '<span class="text-danger">' . price($solde, 0, $langs, 0, -1, -1, $Line["currency_code"]) . '</span>';            
                $Value.= '&nbsp;<i class="fa fa-exclamation-triangle text-danger" aria-hidden="true"></i>';     
            } elseif ($solde < $Line["min_desired"]) {
                $Value = '<span class="text-warning">' . price($solde, 0, $langs, 0, -1, -1, $Line["currency_code"]) . '</span>';            
                $Value.= '&nbsp;<i class="fa fa-exclamation text-warning" aria-hidden="true"></i>';    
            } else {
                $Value = '<span class="text-success">' . price($solde, 0, $langs, 0, -1, -1, $Line["currency_code"]) . '</span>';            
            }
            
            $Contents[] = array(
                $Prefix . $Line["ref"], $Line["label"], $Line["bank"],
                $Value,   
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

        global $langs, $db;
        
        $Data   = $this->getData();

        //====================================================================//
        // Build SparkInfo Options
        //====================================================================//
        switch($Data->num_rows) {
            case 1:
                $Width = self::SIZE_XL;
                break;
            case 2:
                $Width = self::SIZE_M;
                break;
            case 3:
                $Width = self::SIZE_SM;
                break;
            default:
                $Width = self::SIZE_XS;
                break;
        }
        $Options = array(
            "AllowHtml"         =>  True,
            "Width"             =>  $Width
        );
        
        //====================================================================//
        // Build SparkInfo Contents
        //====================================================================//
        
        include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
        $account_static = new \Account($db);
        
        foreach ($Data as $Line) {
            $account_static->id         = $Line["rowid"];
            $account_static->label      = $Line["label"];
            $account_static->number     = $Line["number"];
            $solde  =   $account_static->solde(0);
            
            
             if ($solde < 0) {
                $Class = "text-danger";
                $Value = '<span class="text-danger">' . price($solde, 0, $langs, 0, -1, -1, $Line["currency_code"]) . '</span>';            
                $Value.= '&nbsp;<i class="fa fa-exclamation-triangle text-danger" aria-hidden="true"></i>';     
            } elseif ($solde < $Line["min_desired"]) {
                $Class = "text-warning";
                $Value = '<span class="text-warning">' . price($solde, 0, $langs, 0, -1, -1, $Line["currency_code"]) . '</span>';            
                $Value.= '&nbsp;<i class="fa fa-exclamation text-warning" aria-hidden="true"></i>';    
            } else {
                $Class = "text-success";
                $Value = '<span class="text-success">' . price($solde, 0, $langs, 0, -1, -1, $Line["currency_code"]) . '</span>';            
            }           
            
            $Contents = array(
                "title"     =>      $Line["ref"],   
                "fa_icon"   =>      "university " . $Class,
                "value"     =>      $Value,   
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
