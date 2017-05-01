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

class StatGraphs extends WidgetBase
{
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
    /**
     *  Widget Name (Translated by Module)
     */
    protected static    $NAME            =  "Statistics";
    
    /**
     *  Widget Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Statistics";    
    
    /**
     *  Widget Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO            =  "fa fa-line-chart";
    
    //====================================================================//
    // Define Standard Options for this Widget
    // Override this array to change default options for your widget
    static $OPTIONS       = array(
        "Width"         =>  self::SIZE_M,
        "Header"        =>  True,
        "Footer"        =>  True,
        'UseCache'      =>  True,
        'CacheLifeTime' =>  60,        
    );
    
    private $Mode = "CustomerInvoices";
    
    private $ChartType =   "Line";
    
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
        Splash::Local()->LoadDefaultLanguage();
        $langs->load("compta");
        
        //====================================================================//
        // Select Data Type Mode
        $this->FieldsFactory()->Create(SPL_T_TEXT)
                ->Identifier("mode")
                ->Name($langs->trans("Model"))
                ->isRequired()
                ->AddChoice("CustomerInvoices",     html_entity_decode($langs->trans("ReportTurnover")))
                ->AddChoice("CustomerOrders",       html_entity_decode($langs->trans("OrderStats")))
                ->AddChoice("SupplierInvoices",     html_entity_decode($langs->trans("BillsForSuppliers")))
                ;
      
        //====================================================================//
        // Select Chart Rendering Mode
        $this->FieldsFactory()->Create(SPL_T_TEXT)
                ->Identifier("chart_type")
                ->Name($langs->trans("Type"))
                ->isRequired()
                ->AddChoice("Line",    "Line Chart")
                ->AddChoice("Bar",     "Bar Chart")
                ->AddChoice("Area",    "Area Chart")
                ;
        
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
        // Build Data Blocks
        //====================================================================//
        
        if (isset($params["mode"]) && in_array($params["mode"], ["CustomerInvoices", "CustomerOrders", "SupplierInvoices"])) {
            $this->Mode = $params["mode"];
        }
        
        if (isset($params["chart_type"]) && in_array($params["chart_type"], ["Bar", "Line", "Area"])) {
            $this->ChartType = $params["chart_type"];
        }
        
        $this->importDates($params);
        $this->setupMode();
        
        $this->buildMorrisBarBlock();
        
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

    private function setupMode()   {
        
        global $db, $langs;
        
        switch ($this->Mode) {
            
            case "CustomerInvoices":
                $langs->load("compta");
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
                $this->stats    = new \FactureStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select   = "date_format(f.datef,'%".$this->GroupBy."') as step, SUM(f.total) as total";
                $this->where    = "f.datef ";
                $this->title    = $langs->trans("SalesTurnover");
                $this->labels   = array($langs->trans("AmountTTCShort"));
                break;
            
            case "SupplierInvoices":
                $langs->load("compta");
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
                $this->stats    = new \FactureStats($db, 0, 'supplier', 0);
                //====================================================================//
                // Setup Mode
                $this->select   = "date_format(f.datef,'%".$this->GroupBy."') as step, SUM(f.total_ht) as total";
                $this->where    = "f.datef ";
                $this->title    = $langs->trans("BillsForSuppliers");
                $this->labels   = array($langs->trans("AmountHTShort"));
                break;
            
            case "CustomerOrders":
                $langs->load("compta");
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';
                $this->stats    = new \CommandeStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select   = "date_format(c.date_commande,'%".$this->GroupBy."') as step, SUM(c.total_ht) as total";
                $this->where    = "c.date_commande ";
                $this->title    = $langs->trans("OrderStats");
                $this->labels   = array($langs->trans("AmountHTShort"));
                break;            
        }
    }
    
    /**
     * @abstract    Read Widget Datas
     */
    private function getData()   {

        global $db;
                
        //====================================================================//
        // Execute SQL Query
        //====================================================================//
        
        $sql = "SELECT " . $this->select;
        $sql.= " FROM ".$this->stats->from;
        $sql.= " WHERE " . $this->where . " BETWEEN '".$this->DateStart."' AND '".$this->DateEnd."'";
        $sql.= " AND ".$this->stats->where;
        $sql.= " GROUP BY step";
        $sql.= $db->order('step','ASC');

        $Result     = $db->query($sql);
        $num        = $db->num_rows($Result);           // Read number of results
        $i          = 0;
        $RawData    = array();
        
        while ($i < $num)
        {
            $Value = $db->fetch_array($Result);
            $RawData[$Value["step"]] = $Value["total"];
            $i++;
        }        
        
        return $this->parseDatedData($RawData);
    }
   
    
    /**
    *   @abstract     Block Building - Morris Bar Graph
    */
    private function buildMorrisBarBlock()   {

        global $langs;
        
        $langs->load("compta");
        
        //====================================================================//
        // Build Chart Contents
        //====================================================================//
        $Data   = $this->getData();

        //====================================================================//
        // Chart Options
        $ChartOptions = array(
            "title"     => $this->title, 
            "labels"    => $this->labels,
        );
        //====================================================================//
        // Block Options
        $Options = array(
            "AllowHtml"         => True,
        );
        //====================================================================//
        // Add Table Block
        $this->BlocksFactory()->addMorrisGraphBlock($Data, $this->ChartType, $ChartOptions, $Options);
        
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
        $langs->load("main");
        return html_entity_decode($langs->trans(static::$NAME));
    }

    /**
     *      @abstract   Return Description of this Widget Class
     */
    public function getDesc()
    {
        global $langs;
        $langs->load("main");
        return html_entity_decode($langs->trans(static::$DESCRIPTION));
    }

}



?>
