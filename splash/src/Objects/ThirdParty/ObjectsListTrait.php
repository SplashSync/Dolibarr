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

namespace   Splash\Local\Objects\ThirdParty;

use Splash\Local\Local;
use Splash\Local\Services\MultiCompany;

/**
 * Dolibarr ThirdParty List Functions
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
        $sql .= " s.rowid as id,";                   // Object Id
        $sql .= " s.entity as entity_id,";           // Entity Id
        $sql .= " s.nom as name,";                   // Company Name
        $sql .= " s.code_client as code_client,";    // Reference
        $sql .= " s.phone as phone,";                // Phone
        $sql .= " s.email as email,";                // Email
        $sql .= " s.zip as zip,";                    // ZipCode
        $sql .= " s.town as town,";                  // City
        if (Local::dolVersionCmp("3.7.0") >= 0) {
            $sql .= " p.label as country,";          // Country Name
        } else {
            $sql .= " p.libelle as country,";        // Country Name
        }
        $sql .= " s.status as status,";              // Active
        $sql .= " s.tms as modified";                // last modified date
        //====================================================================//
        // Select Database tables
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s ";
        if (Local::dolVersionCmp("3.7.0") >= 0) {
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as p on s.fk_pays = p.rowid";
        } else {
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p on s.fk_pays = p.rowid";
        }
        //====================================================================//
        // Entity Filter
        $entityIds = MultiCompany::isMarketplaceMode() ? MultiCompany::getVisibleSqlIds() : getEntity('societe', 1);
        $sql .= " WHERE s.entity IN (".$entityIds.")";
        //====================================================================//
        // Setup filters
        // Add filters with names conversions. Added LOWER function to be NON case sensitive
        if (!empty($filter) && is_string($filter)) {
            $sql .= " AND ( ";
            //====================================================================//
            // Search in Customer Code
            $sql .= " LOWER( s.code_client ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Customer Name
            $sql .= " OR LOWER( s.nom ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Customer Phone
            $sql .= " OR LOWER( s.phone ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Customer Email
            $sql .= " OR LOWER( s.email ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Customer Zip
            $sql .= " OR LOWER( s.zip ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Customer Town
            $sql .= " OR LOWER( s.town ) LIKE LOWER( '%".$filter."%') ";
            $sql .= " ) ";
        }
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"s.nom":$params["sortfield"];
        $sortorder = empty($params["sortorder"])?"DESC":$params["sortorder"];
        $sql .= " ORDER BY ".$sortfield." ".$sortorder;

        return $sql;
    }
}
