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

class CustomerInvoicesStats extends WidgetBase
{
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Widget Disable Flag. Uncomment this line to Override this flag and disable Object.
     */
    protected static $DISABLED        =  true;
    
    /**
     *  Widget Name (Translated by Module)
     */
    protected static $NAME            =  "CustomersInvoices";
    
    /**
     *  Widget Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "CustomersInvoices";
    
    /**
     *  Widget Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO            =  "fa fa-line-chart";
    
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
        // Build Data Blocks
        //====================================================================//
        
        $this->importDates($params);
        
        
        $this->buildMorrisBarBlock();
        
        //====================================================================//
        // Set Blocks to Widget
        $this->setBlocks($this->blocksFactory()->Render());

        //====================================================================//
        // Publish Widget
        return $this->Render();
    }
        

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//


    
    /**
     * @abstract    Read Widget Datas
     */
    private function getData()
    {

        global $db;
        
        include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
        
        $stats = new \FactureStats($db, 0, 'customer', 0);
        
        //====================================================================//
        // Execute SQL Query
        //====================================================================//
        
        $sql = "SELECT date_format(f.datef,'%".$this->GroupBy."') as step,";
        $sql.= " COUNT(*) as nb, SUM(f.total) as total";
        $sql.= " FROM ".$stats->from;
        $sql.= " WHERE f.datef BETWEEN '".$this->DateStart."' AND '".$this->DateEnd."'";
        $sql.= " AND ".$stats->where;
        $sql.= " GROUP BY step";
        $sql.= $db->order('step', 'ASC');

        $Result = $db->query($sql);

        $RawData = array();
        foreach (mysqli_fetch_all($Result, MYSQLI_ASSOC) as $Value) {
            $RawData[$Value["step"]] = $Value["total"];
        }
        
        return $this->parseDatedData($RawData);
    }
   
    
    /**
    *   @abstract     Block Building - Morris Bar Graph
    */
    private function buildMorrisBarBlock()
    {

        global $langs;
        
        $langs->load("compta");
        
        //====================================================================//
        // Build Chart Contents
        //====================================================================//
        $Data   = $this->getData();

        //====================================================================//
        // Chart Options
        $ChartOptions = array(
            "title"     => $langs->trans("SalesTurnover"),
            "labels"            => array($langs->trans("AmountTTCShort")),
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
