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

namespace   Splash\Local\Objects\Product;

use Splash\Local\Services\MultiCompany;

/**
 * Dolibarr Products Objects List Functions
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
        $sql .= " p.rowid as id,";                    // Object Id
        $sql .= " p.entity as entity_id,";            // Entity Id
        $sql .= " p.ref as ref,";                     // Reference
        $sql .= " p.label as label,";                 // Product Name
        $sql .= " p.description as description,";     // Short Description
        $sql .= " p.stock as stock_reel,";            // Stock Level
        $sql .= " ROUND(p.price, 3) as price,";                 // Price
        $sql .= " p.tobuy as status_buy,";            // Product may be Ordered / Bought
        $sql .= " p.tosell as status,";               // Product may be Sold
        $sql .= " p.tms as modified";                 // last modified date
        //====================================================================//
        // Select Database tables
        $sql .= " FROM ".MAIN_DB_PREFIX."product as p ";
        $sql .= "LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination as c ON p.rowid = c.fk_product_parent";
        //====================================================================//
        // Entity Filter
        $entityIds = MultiCompany::isMarketplaceMode() ? MultiCompany::getVisibleSqlIds() : getEntity('product', 1);
        $sql .= " WHERE c.rowid IS NULL AND p.entity IN (".$entityIds.")";
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names conversions. Added LOWER function to be NON case sensitive
        if (!empty($filter) && is_string($filter)) {
            $sql .= " AND ( ";
            //====================================================================//
            // Search in Product Ref.
            $sql .= " LOWER( p.ref ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Product Label
            $sql .= " OR LOWER( p.label ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Product Description
            $sql .= " OR LOWER( p.description ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Product Stock
            $sql .= " OR LOWER( p.stock ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Product Price
            $sql .= " OR LOWER( p.price ) LIKE LOWER( '%".$filter."%') ";
            $sql .= " ) ";
        }
        //====================================================================//
        // Setup sort order
        //====================================================================//
        $sortField = empty($params["sortfield"])?"p.rowid":$params["sortfield"];
        $sortOrder = empty($params["sortorder"])?"DESC":$params["sortorder"];
        $sql .= " ORDER BY ".$sortField." ".$sortOrder;

        return $sql;
    }
}
