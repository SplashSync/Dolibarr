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

namespace   Splash\Local\Objects\Address;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Contacts Address List Functions 
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
    public function objectsList($filter=NULL,$params=NULL)
    {
        global $db,$langs;
        Splash::log()->deb("MsgLocalFuncTrace",__CLASS__,__FUNCTION__);             
        $data = array();
        //====================================================================//
        // Load Default Language
        Splash::Local()->LoadDefaultLanguage();
        //====================================================================//
        // Load Required Translation Files
        $langs->load("companies");
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql    = $this->getSqlBaseRequest($filter, $params);
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
        Splash::log()->deb("MsgLocalTpl",__CLASS__,__FUNCTION__," SQL : " . $sql);
        if (empty($resql))  {
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__, $db->lasterror());
        }
        //====================================================================//
        // Read Data and prepare Response Array
        $num = $db->num_rows($resql);           // Read number of results
        $data["meta"]["current"]   =   $num;    // Store Current Number of results
        $index = 0;
        //====================================================================//
        // For each result, read information and add to $data
        while ($index < $num)
        {
            $data[$index] = (array) $db->fetch_object($resql);
            $index++;
        }
        $db->free($resql);
        Splash::log()->deb("MsgLocalTpl",__CLASS__,__FUNCTION__, " " . $index . " Contact Found.");
        return $data;
    }

    private function getSqlBaseRequest($filter=NULL,$params=NULL)
    {
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql    = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql   .= " c.rowid as id,";                    // Object Id         
        $sql   .= " c.ref_ext as ref_ext,";             // Reference
        $sql   .= " c.firstname as firstname,";         // FirstName 
        $sql   .= " c.lastname as lastname,";           // LastName 
        $sql   .= " c.phone as phone_pro,";             // Professionnal Phone
        $sql   .= " c.phone_mobile as phone_mobile,";   // Mobile Phone
        $sql   .= " c.email as email,";                 // Email
        $sql   .= " c.zip as zip,";                     // ZipCode
        $sql   .= " c.town as town,";                   // City Name
        if (Splash::Local()->DolVersionCmp("3.7.0") >= 0) {
            $sql   .= " p.label as country,";          // Country Name
        } else {
            $sql   .= " p.libelle as country,";        // Country Name
        }          
        $sql   .= " c.statut as status,";              // Active
        $sql   .= " c.tms as modified";                // last modified date
        //====================================================================//
        // Select Database tables
        $sql   .= " FROM " . MAIN_DB_PREFIX . "socpeople as c ";
        
        if (Splash::Local()->DolVersionCmp("3.7.0") >= 0) {
            $sql   .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as p on c.fk_pays = p.rowid"; 
        } else {
            $sql   .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_pays as p on c.fk_pays = p.rowid"; 
        }        
        
        //====================================================================//
        // Entity Filter
        $sql   .= " WHERE c.entity IN (".getEntity('contact', 1).")";
        
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            $sql   .= " AND ( ";
            //====================================================================//
            // Search in External Ref
            $sql   .= " WHERE LOWER( c.ref_ext ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in FirstName
            $sql   .= " OR LOWER( c.firstname ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in LastName
            $sql   .= " OR LOWER( c.lastname ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Phone
            $sql   .= " OR LOWER( c.phone ) LIKE LOWER( '%" . $filter ."%') ";
            $sql   .= " OR LOWER( c.phone_mobile ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Email
            $sql   .= " OR LOWER( c.email ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Zip
            $sql   .= " OR LOWER( c.zip ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Town
            $sql   .= " OR LOWER( c.town ) LIKE LOWER( '%" . $filter ."%') ";
            $sql   .= " ) ";              
        }  
        
        //====================================================================//
        // Setup sortorder
        //====================================================================//
        $sortfield = empty($params["sortfield"])?"c.rowid":$params["sortfield"];
        $sortorder = empty($params["sortorder"])?"DESC":$params["sortorder"];
        $sql   .= " ORDER BY " . $sortfield . " " . $sortorder;   
        
        return $sql;
    }    
}
