<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Local\Core;

use Splash\Core\SplashCore      as Splash;

/**
 * Direct Access to Dolibarr Database tables data
 */
trait DirectAccessTrait
{
    /**
     * Update Single Dolibarr Entity Field Data
     *
     * @param string $name  Field Name
     * @param mixed  $value Field Data
     * @param string $table Entity Table Name Without Prefix
     * @param int    $rowId Entity RowId
     *
     * @return bool
     */
    public function setDatabaseField($name, $value, $table = null, $rowId = null)
    {
        global $db;

        //====================================================================//
        // Parameters Overide
        $realTable = is_null($table) ?  $this->object->table_element : $table;
        $realRowId = is_null($rowId) ?  $this->object->id : $rowId;
        //====================================================================//
        // Safety Check
        if (empty($realTable) || empty($realRowId)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Wrong Input Parameters.");
        }
        //====================================================================//
        // Prepare SQL Request
        //====================================================================//
        $sql = "UPDATE ".MAIN_DB_PREFIX.$realTable;
        $sql .= " SET ".$name."='".$db->escape($value)."'";
        $sql .= " WHERE rowid=".$db->escape($realRowId);
        //====================================================================//
        // Execute SQL Query
        //====================================================================//
        $result = $db->query($sql);
        if (empty($result)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, $db->lasterror());
        }

        return true;
    }
}
