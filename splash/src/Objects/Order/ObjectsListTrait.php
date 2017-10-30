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

namespace   Splash\Local\Objects\Order;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Customer Order List Functions 
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
        global $db,$langs;
        Splash::Log()->Deb("MsgLocalFuncTrace",__CLASS__,__FUNCTION__);             
        $data = array();
        //====================================================================//
        // Load Default Language
        Splash::Local()->LoadDefaultLanguage();
        //====================================================================//
        // Load Required Translation Files
        $langs->load("orders");
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql    = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql .= " o.rowid as id,";                  // Object Id         
        $sql .= " o.ref as ref,";                   // Dolibarr Reference  
        $sql .= " o.ref_ext as ref_ext,";           // External Reference  
        $sql .= " o.ref_int as ref_int,";           // Internal Reference 
        $sql .= " o.ref_client as ref_client,";     // Customer Reference
        $sql .= " o.total_ht as total_ht,";         // Total net of tax
        $sql .= " o.total_ttc as total_ttc,";       // Total with tax
        $sql .= " o.date_commande as date";         // Order date
        //====================================================================//
        // Select Database tables
        $sql   .= " FROM " . MAIN_DB_PREFIX . "commande as o ";
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in Order Ref.
            $sql   .= " WHERE LOWER( o.ref ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Order Internal Ref 
            $sql   .= " OR LOWER( o.ref_int ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Order External Ref
            $sql   .= " OR LOWER( o.ref_ext ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Order Customer Ref
            $sql   .= " OR LOWER( o.ref_client ) LIKE LOWER( '%" . $filter ."%') ";
        }   
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"o.rowid":$params["sortfield"];
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
            $i++;
        }
        $db->free($resql);
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__, " " . $i . " Orders Found.");
        return $data;
    }
    
}
