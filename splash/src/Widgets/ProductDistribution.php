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

use Splash\Models\AbstractWidget;
use Splash\Core\SplashCore      as Splash;

class ProductDistribution extends AbstractWidget
{
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Widget Name (Translated by Module)
     */
    protected static $NAME            =  "BoxProductDistribution";
    
    /**
     *  Widget Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "BoxProductDistribution";
    
    /**
     *  Widget Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO            =  "fa fa-pie-chart";
    
    //====================================================================//
    // Define Standard Options for this Widget
    // Override this array to change default options for your widget
    public static $OPTIONS       = array(
        "Width"         =>  self::SIZE_M,
        "Header"        =>  true,
        "Footer"        =>  true,
        'UseCache'      =>  true,
        'CacheLifeTime' =>  60,
    );
    
    //====================================================================//
    // General Class Variables
    //====================================================================//

    private $stats;
    private $select;
    private $where;
    private $from;
    private $title;
    private $labels;

    private $Mode       = "Invoices";
    private $ChartType  =   "Line";
    
    //====================================================================//
    // Class Main Functions
    //====================================================================//

    public function __construct()
    {
        //====================================================================//
        // Load Default Language
        Splash::local()->loadDefaultLanguage();
    }
    
    /**
     *      @abstract   Return Widget Customs Parameters
     */
    public function getParameters()
    {
        global $langs;
        Splash::local()->loadDefaultLanguage();
        
        $langs->load("main");
        $langs->load("bills");
        $langs->load("orders");
        $langs->load("compta");
        
        $ParamTitle     = $langs->transnoentitiesnoconv("Products").'/'.$langs->transnoentitiesnoconv("Services");
        $TitleInvoices  = $langs->trans(
            "BoxProductDistributionFor",
            $ParamTitle,
            $langs->transnoentitiesnoconv("Invoices")
        );
        $TitleOrders    = $langs->trans(
            "BoxProductDistributionFor",
            $ParamTitle,
            $langs->transnoentitiesnoconv("Orders")
        );
                
        //====================================================================//
        // Select Data Type Mode
        $this->fieldsFactory()->create(SPL_T_TEXT)
                ->Identifier("mode")
                ->Name($langs->trans("Model"))
                ->isRequired()
                ->AddChoice("Invoices", html_entity_decode($TitleInvoices))
                ->AddChoice(
                    "InvoicesCount",
                    html_entity_decode($TitleInvoices . " (" . $langs->trans("NbOfLines") . ")")
                )
                ->AddChoice("Orders", html_entity_decode($TitleOrders))
                ->AddChoice("OrdersCount", html_entity_decode($TitleOrders . " (" . $langs->trans("NbOfLines") . ")"))
                ;
      
        
        //====================================================================//
        // Select Chart Rendering Mode
        $this->fieldsFactory()->create(SPL_T_TEXT)
                ->Identifier("chart_type")
                ->Name($langs->trans("Type"))
                ->isRequired()
                ->AddChoice("Pie", "Pie Chart")
                ->AddChoice("Bar", "Bar Chart")
                ;
        
        //====================================================================//
        // Publish Fields
        return $this->fieldsFactory()->publish();
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
        Splash::local()->loadDefaultLanguage();

        //====================================================================//
        // Setup Widget Core Informations
        //====================================================================//

        $this->setTitle($this->getName());
        $this->setIcon($this->getIcon());
        
        //====================================================================//
        // Build Data Blocks
        //====================================================================//
        
        if (isset($params["mode"])
                && in_array($params["mode"], ["Invoices", "InvoicesCount", "Orders", "OrdersCount"])) {
            $this->Mode = $params["mode"];
        }
        
        if (isset($params["chart_type"]) && in_array($params["chart_type"], ["Bar", "Pie"])) {
            $this->ChartType = $params["chart_type"];
        }
        
        $this->importDates($params);
        $this->setupMode();
        
        if ($this->ChartType == "Bar") {
            $this->buildMorrisBarBlock();
        } else {
            $this->buildMorrisDonutBlock();
        }
        
        //====================================================================//
        // Set Blocks to Widget
        $this->setBlocks($this->blocksFactory()->render());

        //====================================================================//
        // Publish Widget
        return $this->render();
    }
        

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    private function setupMode()
    {
        global $db, $langs;
        $langs->load("main");
        $langs->load("bills");
        $langs->load("compta");
        $langs->load("orders");
        $ParamTitle     = $langs->transnoentitiesnoconv("Products").'/'.$langs->transnoentitiesnoconv("Services");
                
        switch ($this->Mode) {
            case "Invoices":
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
                $this->stats    = new \FactureStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select   = "product.ref as label, SUM(tl.".$this->stats->field_line.") as value";
                $this->from     = $this->stats->from.", ";
                $this->from    .= $this->stats->from_line.", ";
                $this->from    .= MAIN_DB_PREFIX."product as product";
                $this->where    = "f.rowid = tl.fk_facture AND tl.fk_product = product.rowid AND f.datef";
                //====================================================================//
                // Setup Titles
                $this->title    = $langs->trans(
                    "BoxProductDistributionFor",
                    $ParamTitle,
                    $langs->transnoentitiesnoconv("Invoices")
                );
                $this->labels   = array($langs->trans("AmountHTShort"));
                break;

            case "InvoicesCount":
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
                $this->stats    = new \FactureStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select   = "product.ref as label, COUNT(product.ref) as value";
                $this->from     = $this->stats->from.", ";
                $this->from    .= $this->stats->from_line.", ";
                $this->from    .= MAIN_DB_PREFIX."product as product";
                $this->where    = "f.rowid = tl.fk_facture AND tl.fk_product = product.rowid AND f.datef";
                //====================================================================//
                // Setup Titles
                $this->title    = $langs->trans(
                    "BoxProductDistributionFor",
                    $ParamTitle,
                    $langs->transnoentitiesnoconv("Invoices")
                ) . " (" . $langs->trans("NbOfLines") . ")";
                $this->labels   = array($langs->trans("NbOfLines"));
                break;
            
            case "Orders":
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';
                $this->stats    = new \CommandeStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select   = "product.ref as label, SUM(tl.".$this->stats->field_line.") as value";
                $this->from     = $this->stats->from.", ";
                $this->from    .= $this->stats->from_line.", ";
                $this->from    .= MAIN_DB_PREFIX."product as product";
                $this->where    = "c.rowid = tl.fk_commande AND tl.fk_product = product.rowid AND c.date_commande";
                //====================================================================//
                // Setup Titles
                $this->title    = $langs->trans(
                    "BoxProductDistributionFor",
                    $ParamTitle,
                    $langs->transnoentitiesnoconv("Orders")
                );
                $this->labels   = array($langs->trans("AmountHTShort"));
                break;
            
            case "OrdersCount":
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';
                $this->stats    = new \CommandeStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select   = "product.ref as label, COUNT(product.ref) as value";
                $this->from     = $this->stats->from.", ";
                $this->from    .= $this->stats->from_line.", ";
                $this->from    .= MAIN_DB_PREFIX."product as product";
                $this->where    = "c.rowid = tl.fk_commande AND tl.fk_product = product.rowid AND c.date_commande";
                //====================================================================//
                // Setup Titles
                $this->title    = $langs->trans(
                    "BoxProductDistributionFor",
                    $ParamTitle,
                    $langs->transnoentitiesnoconv("Orders")
                ) . " (" . $langs->trans("NbOfLines") . ")";
                $this->labels   = array($langs->trans("NbOfLines"));
                break;
        }
    }
    
    /**
     * @abstract    Read Widget Datas
     */
    private function getData($Limit = null)
    {

        global $db;
                
        //====================================================================//
        // Execute SQL Query
        //====================================================================//
        $sql = "SELECT " . $this->select . " FROM ".$this->from;
        $sql.= " WHERE " . $this->where . " BETWEEN '".$this->DateStart."' AND '".$this->DateEnd."'";
        $sql.= " AND ".$this->stats->where;
        $sql.= " GROUP BY label";
        $sql.= $db->order('value', 'DESC');
        if ($Limit) {
            $sql.= $db->plimit($Limit);
        }
        
        $Result     = $db->query($sql);
        $num        = $db->num_rows($Result);           // Read number of results
        $index      = 0;
        $RawData    = array();
        while ($index < $num) {
            $RawData[$index] = $db->fetch_array($Result);
            $index++;
        }
        
        return $RawData;
    }
   
    /**
    *   @abstract     Block Building - Morris Donut Graph
    */
    private function buildMorrisDonutBlock()
    {

        global $langs;
        
        //====================================================================//
        // Build Chart Contents
        //====================================================================//
        $Data   = $this->getData();

        if (empty($Data)) {
            $langs->load("admin");
            $this->blocksFactory()->addNotificationsBlock(array(
                "warning"   => $langs->trans("PreviewNotAvailable")
                    ));
            return;
        }
        
        $langs->load("compta");
        

        //====================================================================//
        // Chart Options
        $ChartOptions = array(
            "title"     => $this->title,
            "labels"    => $this->labels,
        );
        //====================================================================//
        // Block Options
        $Options = array(
            "AllowHtml"         => true,
        );
        //====================================================================//
        // Add Table Block
        $this->blocksFactory()->addMorrisDonutBlock($Data, $ChartOptions, $Options);
    }
    
    /**
    *   @abstract     Block Building - Morris Bar Graph
    */
    private function buildMorrisBarBlock()
    {

        global $langs;
        
        //====================================================================//
        // Build Chart Contents
        //====================================================================//
        $Data   = $this->getData(5);

        
        if (empty($Data)) {
            $langs->load("admin");
            $this->blocksFactory()->addNotificationsBlock(array(
                "warning"   => $langs->trans("PreviewNotAvailable")
                    ));
            return;
        }
        
        $langs->load("compta");
        
        //====================================================================//
        // Chart Options
        $ChartOptions = array(
            "title"     => $this->title,
            "labels"    => $this->labels,
        );
        //====================================================================//
        // Block Options
        $Options = array(
            "AllowHtml"         => true,
        );
        //====================================================================//
        // Add Table Block
        $this->blocksFactory()->addMorrisGraphBlock($Data, "Bar", $ChartOptions, $Options);
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
        $langs->load("boxes");
        return html_entity_decode($langs->trans(static::$NAME));
    }

    /**
     *      @abstract   Return Description of this Widget Class
     */
    public function getDesc()
    {
        global $langs;
        $langs->load("main");
        $langs->load("boxes");
        return html_entity_decode($langs->trans(static::$DESCRIPTION));
    }
}
