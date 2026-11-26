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

use Splash\Core\SplashCore as Splash;

/**
 * Manage Access to ThirdParty Types (Customer Types)
 *
 * Third-party types are stored in Dolibarr dictionary llx_c_typent: the code
 * is the stable identifier, the name is only displayed. Only active types
 * available for the company country are used, named as in Dolibarr forms.
 *
 * Types are kept in a static cache, reloaded once when an unknown type is
 * requested (i.e. a type added to the dictionary since the first load).
 *
 * @phpstan-type TypeDef array{id: int, code: string, name: string, label: string}
 */
class ThirdPartyTypeManager
{
    /**
     * Available ThirdParty Types, Indexed by Code
     *
     * @var null|array<string, TypeDef>
     */
    private static ?array $types = null;

    /**
     * Get ThirdParty Types Choices for Fields, Indexed by Code
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        return array_map(
            static function (array $type): string {
                return $type["name"];
            },
            self::getTypes()
        );
    }

    /**
     * Get Name of a ThirdParty Type
     *
     * @param null|string $code ThirdParty Type Code
     *
     * @return string Empty if type is unknown
     */
    public static function getName(?string $code): string
    {
        return self::getType($code)["name"] ?? "";
    }

    /**
     * Get Dictionary ID of a ThirdParty Type
     *
     * @param null|string $code ThirdParty Type Code
     *
     * @return null|int Null if type is unknown
     */
    public static function getId(?string $code): ?int
    {
        return self::getType($code)["id"] ?? null;
    }

    /**
     * Resolve a Received Value to a ThirdParty Type Code
     *
     * The exact code is searched first, then the exact name (as displayed by
     * Dolibarr), then the raw dictionary label.
     *
     * @param null|string $value Received ThirdParty Type Code or Name
     *
     * @return null|string Null if no type matches
     */
    public static function resolve(?string $value): ?string
    {
        $value = trim((string) $value);
        if ("" === $value) {
            return null;
        }

        //====================================================================//
        // Search in Cache, then Reload Types Once
        return self::search($value, self::getTypes())
            ?? self::search($value, self::getTypes(true))
        ;
    }

    /**
     * Get a ThirdParty Type Definition by Code, Reload Types if Unknown
     *
     * @param null|string $code ThirdParty Type Code
     *
     * @return null|TypeDef
     */
    private static function getType(?string $code): ?array
    {
        if (empty($code)) {
            return null;
        }

        return self::getTypes()[$code] ?? self::getTypes(true)[$code] ?? null;
    }

    /**
     * Search a ThirdParty Type Code by Code, Name or Label
     *
     * @param string                 $value Received ThirdParty Type Code or Name
     * @param array<string, TypeDef> $types Available ThirdParty Types
     *
     * @return null|string
     */
    private static function search(string $value, array $types): ?string
    {
        if (isset($types[$value])) {
            return $value;
        }

        foreach (array("name", "label") as $key) {
            foreach ($types as $code => $type) {
                if ($type[$key] === $value) {
                    return $code;
                }
            }
        }

        return null;
    }

    /**
     * Get Available ThirdParty Types, Indexed by Code
     *
     * Names follow Dolibarr forms rule: translation of the code when
     * available, raw dictionary label otherwise.
     *
     * @param bool $reload Force reload from database
     *
     * @return array<string, TypeDef>
     */
    private static function getTypes(bool $reload = false): array
    {
        global $db, $langs, $mysoc;

        //====================================================================//
        // Already in Cache
        if (isset(self::$types) && !$reload) {
            return self::$types;
        }
        self::$types = array();
        $langs->load("companies");
        //====================================================================//
        // Load Active Types for Company Country
        $sql = "SELECT id, code, libelle as label";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_typent";
        $sql .= " WHERE active = 1";
        $sql .= " AND (fk_country IS NULL OR fk_country = ".((int) ($mysoc->country_id ?? 0)).")";
        $sql .= " ORDER BY id ASC";
        $result = $db->query($sql);
        if (!$result) {
            Splash::log()->err($db->lasterror());

            return self::$types;
        }

        while ($obj = $db->fetch_object($result)) {
            $code = (string) $obj->code;
            $label = (string) $obj->label;
            $name = ($langs->trans($code) != $code) ? (string) $langs->trans($code) : $label;
            self::$types[$code] = array(
                "id" => (int) $obj->id,
                "code" => $code,
                "name" => ("-" === $name) ? "" : $name,
                "label" => $label,
            );
        }
        $db->free($result);

        return self::$types;
    }
}
