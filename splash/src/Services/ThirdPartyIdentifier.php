<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Services;

use Societe;

/**
 * Collection of methods to Identify ThirdParty
 */
class ThirdPartyIdentifier
{
    /**
     * Detect ThirdParty by Name
     */
    public static function findOneByName(string $name): ?Societe
    {
        global $db;

        //====================================================================//
        // Return Customer
        return self::findOneBySql("s.nom = '".$db->escape($name)."'");
    }

    /**
     * Detect ThirdParty by Email
     */
    public static function findOneByEmail(string $email): ?Societe
    {
        global $db;

        //====================================================================//
        // Return Customer
        return self::findOneBySql("s.email = '".$db->escape($email)."'");
    }

    /**
     * Detect Supplier by Code
     */
    public static function findOneSupplierByCode(string $code): ?Societe
    {
        global $db;

        //====================================================================//
        // Return Customer
        return self::findOneBySql("s.code_fournisseur = '".$db->escape($code)."'");
    }

    /**
     * Detect ThirdParty from Raw Sql
     */
    private static function findOneBySql(string $sqlWhere): ?Societe
    {
        global $db;

        //====================================================================//
        // Prepare Sql Query
        $sql = 'SELECT s.rowid';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
        $sql .= ' WHERE s.entity IN ('.getEntity('societe').')';
        $sql .= " AND ".$sqlWhere;

        //====================================================================//
        // Execute Query
        $reSql = $db->query($sql);
        if (!$reSql || (1 != $db->num_rows($reSql))) {
            return null;
        }
        $customer = $db->fetch_object($reSql);
        //====================================================================//
        // Try Loading ThirdParty by ID
        $societe = new Societe($db);
        $result = $societe->fetch($customer->rowid);
        if (($result > 0) && ($societe->id > 0)) {
            return $societe;
        }

        //====================================================================//
        // Return ThirdParty Id
        return null;
    }
}
