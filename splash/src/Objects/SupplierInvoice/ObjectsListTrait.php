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

namespace   Splash\Local\Objects\SupplierInvoice;

use Splash\Local\Local;
use Splash\Local\Services\MultiCompany;

/**
 * Dolibarr Supplier Invoice List Functions
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
        // Dolibarr Total Ht Columns Name was Updated in V14
        $totalHtColumn = (Local::dolVersionCmp("14.0.0") >= 0) ? "f.total_ht" : "f.total";

        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql .= " f.rowid as id,";                  // Object ID
        $sql .= " f.entity as entity_id,";          // Entity ID
        $sql .= " f.ref as ref,";                   // Dolibarr Reference
        $sql .= " f.ref_ext as ref_ext,";           // External Reference
        $sql .= " f.ref_supplier as ref_supplier,"; // Supplier Reference
        $sql .= " ".$totalHtColumn." as total_ht,"; // Total net of tax
        $sql .= " f.total_ttc as total_ttc,";       // Total with tax
        $sql .= " f.datef as date";                 // Invoice date
        //====================================================================//
        // Select Database tables
        $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f ";
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
            $sql .= " LOWER( f.ref ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Invoice Supplier Ref
            $sql .= " OR LOWER( f.ref_supplier ) LIKE LOWER( '%".$filter."%') ";
            $sql .= " ) ";
        }
        //====================================================================//
        // Setup sort order
        $sortField = empty($params["sortfield"])?"f.rowid":$params["sortfield"];
        $sortOrder = empty($params["sortorder"])?"DESC":$params["sortorder"];
        $sql .= " ORDER BY ".$sortField." ".$sortOrder;

        return $sql;
    }
}