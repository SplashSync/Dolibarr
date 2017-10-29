<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * 
 **/

namespace   Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Products Objects List Functions 
 */
trait ObjectsListTrait {
    
    /**
     *  @abstract     Return List Of Customer with required filters
     * 
     *  @param        string  $filter                   Filters/Search String for Contact List. 
     *  @param        array   $params                   Search parameters for result List. 
     *                        $params["max"]            Maximum Number of results 
     *                        $params["offset"]         List Start Offset 
     *                        $params["sortfield"]      Field name for sort list (Available fields listed below)    
     *                        $params["sortorder"]      List Order Constraign (Default = ASC)    
     * 
     *  @return       array   $data                     List of all customers main data
     *                        $data["meta"]["total"]     ==> Total Number of results
     *                        $data["meta"]["current"]   ==> Total Number of results
     */
    public function ObjectsList($filter=NULL,$params=NULL)
    {
        global $db,$conf;
        Splash::Log()->Deb("MsgLocalFuncTrace",__CLASS__,__FUNCTION__);             
        $data = array();
        
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql    = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql   .= " p.rowid as id,";                    // Object Id         
        $sql   .= " p.ref as ref,";                     // Reference
        $sql   .= " p.label as label,";                 // Product Name 
        $sql   .= " p.description as description,";     // Short Description 
        $sql   .= " p.stock as stock_reel,";            // Stock Level
        $sql   .= " p.price as price,";                 // Price
        $sql   .= " p.tobuy as status_buy,";            // Product may be Ordered / Bought
        $sql   .= " p.tosell as status,";               // Product may be Sold
        $sql   .= " p.tms as modified";                 // last modified date
        //====================================================================//
        // Select Database tables
        $sql   .= " FROM " . MAIN_DB_PREFIX . "product as p ";
        
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in Product Ref.
            $sql   .= " WHERE LOWER( p.ref ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Product Label
            $sql   .= " OR LOWER( p.label ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Product Description
            $sql   .= " OR LOWER( p.description ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Product Stock
            $sql   .= " OR LOWER( p.stock ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Product Price
            $sql   .= " OR LOWER( p.price ) LIKE LOWER( '%" . $filter ."%') ";
        }  
        
        //====================================================================//
        // Setup sortorder
        //====================================================================//
        $sortfield = empty($params["sortfield"])?"p.rowid":$params["sortfield"];
        $sortorder = empty($params["sortorder"])?"DESC":$params["sortorder"];
        $sql   .= " ORDER BY " . $sortfield . " " . $sortorder;   
        
        //====================================================================//
        // Execute request to get total number of row
        $resqlcount = $db->query($sql);
        if ($resqlcount)    {
            $data["meta"]["total"]   =   $db->num_rows($resqlcount);  // Store Total Number of results
        }
        //====================================================================//
        // Setup limmits
        if ( !empty($params["max"])  ) {
            $sql   .= " LIMIT " . $params["max"];
        }
        if ( !empty($params["offset"])  ) {
            $sql   .= " OFFSET " . $params["offset"];
        }
        //====================================================================//
        // Execute final request
        $resql = $db->query($sql);
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," SQL : " . $sql);
        if (empty($resql))  {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__, $db->lasterror());
        }
        //====================================================================//
        // Read Data and prepare Response Array
        $num = $db->num_rows($resql);           // Read number of results
        $data["meta"]["current"]   =   $num;    // Store Current Number of results
        $i = 0;
        //====================================================================//
        // For each result, read information and add to $data
        while ($i < $num)
        {
            $data[$i] = (array) $db->fetch_object($resql);
            $data[$i]["price"] = round($data[$i]["price"],3) . " " . $conf->global->MAIN_MONNAIE;
            $i++;
        }
        $db->free($resql);
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__, " " . $i . " Products Found.");
        return $data;
    }
    
}
