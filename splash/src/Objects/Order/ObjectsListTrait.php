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

namespace   Splash\Local\Objects\Order;

use Splash\Local\Services\MultiCompany;

/**
 * Dolibarr Customer Order List Functions
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
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql .= " o.rowid as id,";                  // Object Id
        $sql .= " o.entity as entity_id,";          // Entity Id
        $sql .= " o.ref as ref,";                   // Dolibarr Reference
        $sql .= " o.ref_ext as ref_ext,";           // External Reference
        $sql .= " o.ref_int as ref_int,";           // Internal Reference
        $sql .= " o.ref_client as ref_client,";     // Customer Reference
        $sql .= " o.total_ht as total_ht,";         // Total net of tax
        $sql .= " o.total_ttc as total_ttc,";       // Total with tax
        $sql .= " o.date_commande as date";         // Order date
        //====================================================================//
        // Select Database tables
        $sql .= " FROM ".MAIN_DB_PREFIX."commande as o ";
        //====================================================================//
        // Entity Filter
        $entityIds = MultiCompany::isMarketplaceMode() ? MultiCompany::getVisibleSqlIds() : getEntity('commande', 1);
        $sql .= " WHERE o.entity IN (".$entityIds.")";
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names conversions. Added LOWER function to be NON case sensitive
        if (!empty($filter) && is_string($filter)) {
            $sql .= " AND ( ";
            //====================================================================//
            // Search in Order Ref.
            $sql .= " LOWER( o.ref ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Order Internal Ref
            $sql .= " OR LOWER( o.ref_int ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Order External Ref
            $sql .= " OR LOWER( o.ref_ext ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Order Customer Ref
            $sql .= " OR LOWER( o.ref_client ) LIKE LOWER( '%".$filter."%') ";
            $sql .= " ) ";
        }
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"o.rowid":$params["sortfield"];
        $sortorder = empty($params["sortorder"])?"DESC":$params["sortorder"];
        $sql .= " ORDER BY ".$sortfield." ".$sortorder;

        return $sql;
    }
}
