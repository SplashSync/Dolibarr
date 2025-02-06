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

/**
 * Manage Access to Shipping Methods
 *
 * @phpstan-type MethodDef array{id: int, code: string, label: string, active: int}
 */
class ShippingMethods
{
    /**
     * @var null|array<int|string, MethodDef>
     */
    private static ?array $methods;

    /**
     * Decode Shipping Method Code from ID
     */
    public static function getCode(?int $methodId): ?string
    {
        //====================================================================//
        // Walk on Known Methods
        foreach (self::getMethods(null) as $methodDef) {
            if ($methodDef["id"] == $methodId) {
                return $methodDef["code"];
            }
        }

        return null;
    }

    /**
     * Decode Received Method to Dolibarr Shipping Method Code
     */
    public static function getDoliCode(string $splashCode): ?string
    {
        //====================================================================//
        // Walk on Available Methods - Search by Code
        foreach (self::getAllMethods() as $method) {
            if ($method["code"] == $splashCode) {
                return $method["code"];
            }
        }
        //====================================================================//
        // Walk on Available Methods - Search by Label
        foreach (self::getAllMethods() as $method) {
            if ($method["label"] == $splashCode) {
                return $method["code"];
            }
        }

        return null;
    }

    /**
     * Decode Splash Codes to Dolibarr Shipping Method Code
     *
     * @param string $splashCode
     *
     * @return null|int
     */
    public static function getDoliId(string $splashCode): ?int
    {
        $doliCode = self::getDoliCode($splashCode);

        //====================================================================//
        // Walk on Available Methods - Search by Label
        foreach (self::getAllMethods() as $method) {
            if ($method["code"] == $doliCode) {
                return (int) $method["id"];
            }
        }

        return null;
    }

    /**
     * Get Shipping Method Choices for Fields
     *
     * @param null|bool $active
     *
     * @return array<string, string>
     */
    public static function getChoices(?bool $active = true): array
    {
        $choices = array();
        //====================================================================//
        // Walk on Available Methods
        foreach (self::getMethods($active) as $method) {
            //====================================================================//
            // Populate Choices
            if ($code = $method['code'] ?: null) {
                $choices[$code] = sprintf("[%s] %s", $code, $method['label']);
            }
        }

        return  $choices;
    }

    /**
     * Get Shipping Methods Infos with Filters
     *
     * @param null|bool $active
     *
     * @return array<int|string, MethodDef>
     */
    public static function getMethods(?bool $active = true): array
    {
        $methods = self::getAllMethods();
        //====================================================================//
        // Filter Inactive
        if (isset($active)) {
            $methods = array_filter($methods, function (array $method) use ($active) {
                return ($method["active"] == $active);
            });
        }

        return $methods;
    }

    /**
     * Detect Local / Direct Shipping Method
     */
    public static function isMySocMethod(?int $methodId): bool
    {
        //====================================================================//
        // Detect Shipping Method Code
        $methodCode = ShippingMethods::getCode($methodId);
        if ($methodCode && in_array($methodCode, array("CATCH", "SHOP", "DIRECT"), true)) {
            return true;
        }

        return false;
    }

    /**
     * Get All Payment Methods Infos
     *
     * @return array<int|string, MethodDef>
     */
    private static function getAllMethods(): array
    {
        global $db;

        //====================================================================//
        // Already in Cache
        if (isset(self::$methods)) {
            return self::$methods;
        }
        self::$methods = array();
        //====================================================================//
        // Init Cache
        $sql = "SELECT rowid as id, code, libelle as label, active";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode";
        $sql .= " WHERE entity IN (".getEntity('c_transport_mode').")";
        $sql .= " ORDER BY code ASC";
        if (!$reSql = $db->query($sql)) {
            return self::$methods;
        }
        $num = $db->num_rows($reSql);
        $index = 0;
        while ($index < $num) {
            /** @var MethodDef $method */
            $method = (array) $db->fetch_object($reSql);
            self::$methods[] = $method;
            $index++;
        }

        return self::$methods;
    }
}
