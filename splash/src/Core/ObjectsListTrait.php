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

namespace   Splash\Local\Core;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Listing Helpers
 */
trait ObjectsListTrait
{
    /**
     *  @abstract     Return List Of Dolibarr Objects with required filters
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
    public function objectsList($filter = null, $params = null)
    {
        global $db;
        
        Splash::log()->deb("MsgLocalFuncTrace", __CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Init Data Array
        $data = array();
        
        //====================================================================//
        // Prepare SQL request for reading in Database
        $sql    = $this->getSqlBaseRequest($filter, $params);
        
        //====================================================================//
        // Execute request to get total number of row
        $data["meta"]["total"]   =   $this->getSqlResultsCount($sql);
        
        //====================================================================//
        // Setup limits
        $sql   .= $this->getSqlPagination($params);
        
        //====================================================================//
        // Execute final request
        $resql = $this->getSqlResults($sql);
        if (empty($resql)) {
            return false;
        }
        
        //====================================================================//
        // Read Data and prepare Response Array
        $num = $db->num_rows($resql);           // Read number of results
        $data["meta"]["current"]   =   $num;    // Store Current Number of results
        
        $index = 0;
        
        //====================================================================//
        // For each result, read information and add to $data
        while ($index < $num) {
            $data[$index] = (array) $db->fetch_object($resql);
            $index++;
        }
        
        $db->free($resql);
        
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, " " . $index . " Objects Found.");
        
        return $data;
    }
 
    /**
     *  @abstract     Get Results for Sql query
     *
     *  @param        sting   $sql                   Sql Raw Query
     *
     *  @return       mixed
     */
    private function getSqlResults($sql)
    {
        global $db;
        $resql = $db->query($sql);
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, " SQL : " . $sql);
        if (empty($resql)) {
            Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, $db->lasterror());
            return null;
        }
        return $resql;
    }
    
    /**
     *  @abstract     Get Results Count for Sql query
     *
     *  @param        sting   $sql                   Sql Raw Query
     *
     *  @return       string
     */
    private function getSqlResultsCount($sql)
    {
        global $db;
        
        $resqlcount = $db->query($sql);
        if ($resqlcount) {
            return $db->num_rows($resqlcount);
        }
        
        return 0;
    }
    
    /**
     *  @abstract     Return Raw Sql Pagination
     *
     *  @param        array   $params                   Search parameters for result List.
     *                        $params["max"]            Maximum Number of results
     *                        $params["offset"]         List Start Offset
     *                        $params["sortfield"]      Field name for sort list (Available fields listed below)
     *                        $params["sortorder"]      List Order Constraign (Default = ASC)
     *
     *  @return       string
     */
    private function getSqlPagination($params)
    {
        $sql = null;
        //====================================================================//
        // Setup limmits
        if (!empty($params["max"])) {
            $sql   .= " LIMIT " . $params["max"];
        }
        if (!empty($params["offset"])) {
            $sql   .= " OFFSET " . $params["offset"];
        }
        return $sql;
    }
}
