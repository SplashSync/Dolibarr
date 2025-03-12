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

namespace Splash\Local\Core;

use ExtraFields;
use Splash\Local\Local;

/**
 * Access to Dolibarr Extra Fields for PhpUnit
 */
trait ExtraFieldsPhpUnitTrait
{
    /**
     * @var array<string, string>
     */
    private static array $testedExtraTypes = array(
        "varchar" => "phpunit_varchar",
        "text" => "phpunit_text",
        "int" => "phpunit_int",
        "bool" => "phpunit_bool",
        "price" => "phpunit_price",
        "date" => "phpunit_date",
        "select" => "phpunit_select",
        "checkbox" => "phpunit_checkbox",
        "sellist" => "phpunit_sellist",
        "chkbxlst" => "phpunit_chkbxlst",
    );

    /**
     * Create & Enable All Possible Extra Fields on Object Type
     *
     * @param string $elementType Object Type Identifier
     * @param bool   $visible     ExtraField Visible / Hidden
     *
     * @return void
     */
    public static function configurePhpUnitExtraFields(string $elementType, bool $visible = true): void
    {
        global $db;
        //====================================================================//
        // Load ExtraFields List
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extraFields = new ExtraFields($db);
        //====================================================================//
        // Load array of ExtraFields for elementType = $this->table_element
        $extraFields->fetch_name_optionals_label($elementType);

        //====================================================================//
        // Load Existing Types for this Element
        $existingTypes = $extraFields->attributes[$elementType]['type'] ?? null;
        if (empty($existingTypes)) {
            $existingTypes = array();
        }

        //====================================================================//
        // Setup all Testing ExtraTypes
        foreach (self::$testedExtraTypes as $extraFieldType => $extraFieldName) {
            if (in_array($extraFieldName, array_keys($existingTypes), true)) {
                //====================================================================//
                // ExtraField Already Exist => Update
                self::updatePhpUnitExtraField($elementType, $extraFieldType, $extraFieldName, $visible);
            } else {
                //====================================================================//
                // ExtraField Not Found => Create
                self::createPhpUnitExtraField($elementType, $extraFieldType, $extraFieldName, $visible);
            }
        }
    }

    /**
     * Create an Extra Fields on Object Type
     */
    private static function createPhpUnitExtraField(
        string $element,
        string $type,
        string $name,
        bool $visible = true
    ): void {
        global $db;
        $extraFields = new ExtraFields($db);

        $extraFields->addExtraField(
            $name,
            ucwords($name, "_"),
            $type,
            0,
            '255',
            $element,
            0,
            0,
            '',
            array("options" => self::getExtraFieldOptions($type)),
            1,
            '',
            '0',
            ($visible ? '0':'1')
        );
    }

    /**
     * Update an Extra Fields on Object Type
     */
    private static function updatePhpUnitExtraField(
        string $element,
        string $type,
        string $name,
        bool $visible = true
    ): void {
        global $db;
        $extraFields = new ExtraFields($db);

        $extraFields->update(
            $name,
            ucwords($name, "_"),
            $type,
            255,
            $element,
            0,
            0,
            0,
            array("options" => self::getExtraFieldOptions($type)),
            1,
            '',
            '0',
            ($visible ? '0':'1')
        );
    }

    /**
     * Create an Extra Fields on Object Type
     */
    private static function getExtraFieldOptions(string $type): array
    {
        switch ($type) {
            case "select":
            case "checkbox":
                return array(
                    "A" => "Option A",
                    "B" => "Option B",
                    "C" => "Option C",
                    "D" => "Option D",
                );
            case "sellist":
            case "chkbxlst":
                return (Local::dolVersionCmp("18.0.0") >= 0)
                    ? array("c_typent:libelle:code::(active:=:1)" => null)
                    : array("c_typent:libelle:code::(active=1)" => null)
                ;
        }

        return array();
    }
}
