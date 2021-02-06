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

namespace   Splash\Local\Objects\Address;

use Splash\Local\Local;
use Splash\Local\Services\MultiCompany;

/**
 * Dolibarr Contacts Address List Functions
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
        $sql .= " c.rowid as id,";                    // Object Id
        $sql .= " c.entity as entity_id,";           // Entity Id
        $sql .= " c.ref_ext as ref_ext,";             // Reference
        $sql .= " c.firstname as firstname,";         // FirstName
        $sql .= " c.lastname as lastname,";           // LastName
        $sql .= " c.phone as phone_pro,";             // Professionnal Phone
        $sql .= " c.phone_mobile as phone_mobile,";   // Mobile Phone
        $sql .= " c.email as email,";                 // Email
        $sql .= " c.zip as zip,";                     // ZipCode
        $sql .= " c.town as town,";                   // City Name
        if (Local::dolVersionCmp("3.7.0") >= 0) {
            $sql .= " p.label as country,";          // Country Name
        } else {
            $sql .= " p.libelle as country,";        // Country Name
        }
        $sql .= " c.statut as status,";              // Active
        $sql .= " c.tms as modified";                // last modified date
        //====================================================================//
        // Select Database tables
        $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c ";
        if (Local::dolVersionCmp("3.7.0") >= 0) {
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as p on c.fk_pays = p.rowid";
        } else {
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p on c.fk_pays = p.rowid";
        }
        //====================================================================//
        // Entity Filter
        $entityIds = MultiCompany::isMarketplaceMode() ? MultiCompany::getVisibleSqlIds() : getEntity('contact', 1);
        $sql .= " WHERE c.entity IN (".$entityIds.")";
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names conversions. Added LOWER function to be NON case sensitive
        if (!empty($filter) && is_string($filter)) {
            $sql .= " AND ( ";
            //====================================================================//
            // Search in External Ref
            $sql .= " WHERE LOWER( c.ref_ext ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in FirstName
            $sql .= " OR LOWER( c.firstname ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in LastName
            $sql .= " OR LOWER( c.lastname ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Phone
            $sql .= " OR LOWER( c.phone ) LIKE LOWER( '%".$filter."%') ";
            $sql .= " OR LOWER( c.phone_mobile ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Email
            $sql .= " OR LOWER( c.email ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Zip
            $sql .= " OR LOWER( c.zip ) LIKE LOWER( '%".$filter."%') ";
            //====================================================================//
            // Search in Town
            $sql .= " OR LOWER( c.town ) LIKE LOWER( '%".$filter."%') ";
            $sql .= " ) ";
        }

        //====================================================================//
        // Setup sort order
        //====================================================================//
        $sortField = empty($params["sortfield"])?"c.rowid":$params["sortfield"];
        $sortOrder = empty($params["sortorder"])?"DESC":$params["sortorder"];
        $sql .= " ORDER BY ".$sortField." ".$sortOrder;

        return $sql;
    }
}
