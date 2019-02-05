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
use Splash\Local\Local;

class CustomerInvoicesStats extends AbstractWidget
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
    // Class Main Functions
    //====================================================================//
    
    /**
     * Class Constructor
     */
    public function __construct()
    {
        //====================================================================//
        // Load Default Language
        Local::loadDefaultLanguage();
    }
    
    /**
     * {@inheritdoc}
     */
    public function get($params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Default Language
        Local::loadDefaultLanguage();

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
        $blocks = $this->blocksFactory()->render();
        if (false !== $blocks) {
            $this->setBlocks($blocks);
        }

        //====================================================================//
        // Publish Widget
        return $this->render();
    }
    
    //====================================================================//
    // Overide Splash Functions
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        global $langs;
        $langs->load("main");
        $langs->load("boxes");
        return html_entity_decode($langs->trans(static::$NAME));
    }

    /**
     * {@inheritdoc}
     */
    public function getDesc()
    {
        global $langs;
        $langs->load("main");
        $langs->load("boxes");
        return html_entity_decode($langs->trans(static::$DESCRIPTION));
    }

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//
    
    /**
     * Read Widget Datas
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

        $result = $db->query($sql);

        $rawData = array();
        foreach (mysqli_fetch_all($result, MYSQLI_ASSOC) as $value) {
            $rawData[$value["step"]] = $value["total"];
        }
        
        return $this->parseDatedData($rawData);
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
        $data   = $this->getData();

        //====================================================================//
        // Chart Options
        $chartOptions = array(
            "title"     => $langs->trans("SalesTurnover"),
            "labels"            => array($langs->trans("AmountTTCShort")),
        );
        //====================================================================//
        // Block Options
        $options = array(
            "AllowHtml"         => true,
        );
        //====================================================================//
        // Add Table Block
        $this->blocksFactory()->addMorrisGraphBlock($data, "Bar", $chartOptions, $options);
    }
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//
}
