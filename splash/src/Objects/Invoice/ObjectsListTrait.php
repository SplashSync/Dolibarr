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

namespace   Splash\Local\Objects\Invoice;

use Splash\Local\Local;
use Splash\Local\Services\MultiCompany;

/**
 * Dolibarr Customer Invoice List Functions
 */
trait ObjectsListTrait
{
    /**
     * Build Object Listing Base Sql Query
     *
     * @param string $filter Filters/Search String for Contact List.
     * @param array  $params Search parameters for result List.
     *
     * @return string
     */
    protected function getSqlBaseRequest($filter = null, $params = null)
    {
        //====================================================================//
        // Dolibarr Reference Columns Name was Updated in V10
        $refColumn = (Local::dolVersionCmp("10.0.0") >= 0) ? "f.ref" : "f.facnumber";

        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql .= " f.rowid as id,";                  // Object Id
        $sql .= " f.entity as entity_id,";          // Entity Id
        $sql .= " ".$refColumn." as ref,";          // Dolibarr Reference
        $sql .= " f.ref_ext as ref_ext,";           // External Reference
        $sql .= " f.ref_int as ref_int,";           // Internal Reference
        $sql .= " f.ref_client as ref_client,";     // Customer Reference
        $sql .= " f.total as total_ht,";            // Total net of tax
        $sql .= " f.total_ttc as total_ttc,";       // Total with tax
        $sql .= " f.datef as date";                 // Invoice date
        //====================================================================//
        // Select Database tables
        $sql .= " FROM ".MAIN_DB_PREFIX."facture as f ";
        //====================================================================//
        // Entity Filter
        $entityIds = MultiCompany::isMarketplaceMode() ? MultiCompany::getVisibleSqlIds() : getEntity('facture', 1);
        $sql .= " WHERE f.entity IN (".$entityIds.")";
        $sql .= " AND f.type IN (".implode(", ", static::$dolibarrTypes).")";

        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names conversions. Added LOWER function to be NON case sensitive
        if (!empty($filter) && is_string($filter)) {
            $sql .= " AND ( ";
            //====================================================================//
            // Search in Invoice Ref.
            $sql .= " LOWER( ".$refColumn." ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Invoice Internal Ref
            $sql .= " OR LOWER( f.ref_int ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Invoice External Ref
            $sql .= " OR LOWER( f.ref_ext ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Invoice Customer Ref
            $sql .= " OR LOWER( f.ref_client ) LIKE LOWER( '%".$filter."%') ";
            $sql .= " ) ";
        }
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"f.rowid":$params["sortfield"];
        $sortorder = empty($params["sortorder"])?"DESC":$params["sortorder"];
        $sql .= " ORDER BY ".$sortfield." ".$sortorder;

        return $sql;
    }
}
