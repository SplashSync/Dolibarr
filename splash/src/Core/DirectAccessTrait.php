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

use Exception;
use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Direct Access to Dolibarr Database tables data 
 */
trait DirectAccessTrait {
    
    /**
     *  @abstract       Update Single Dolibarr Entity Field Data 
     * 
     *  @param      string  $Name       Field Name
     *  @param      mixed   $Value      Field Data
     *  @param      string  $Table      Entity Table Name Without Prefix
     *  @param      int     $RowId      Entity RowId
     * 
     *  @return     bool
     */
    public function setDatabaseField($Name, $Value, $Table = Null, $RowId = Null) 
    {
        global $db;
        
        //====================================================================//
        // Parameters Overide
        $_Table  =   is_null($Table) ?  $this->Object->table_element : $Table; 
        $_RowId  =   is_null($RowId) ?  $this->Object->id : $RowId; 
        //====================================================================//
        // Safety Check
        if ( empty($_Table) || empty($_RowId) ) {
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__, "Wrong Input Parameters.");
        }
        //====================================================================//
        // Prepare SQL Request
        //====================================================================//
        $sql  = "UPDATE ". MAIN_DB_PREFIX . $_Table;
        $sql .= " SET " . $Name . "='".$db->escape($Value)."'";
        $sql .= " WHERE rowid=".$db->escape($_RowId);
        //====================================================================//
        // Execute SQL Query
        //====================================================================//
        $result = $db->query($sql);
        if (empty($result))  {
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__, $db->lasterror());
        }        

        return True;
    }
    
}
