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

namespace   Splash\Local\Core;

use ExtraFields;

/**
 * Access to Dolibarr Extra Fields for PhpUnit
 */
trait ExtraFieldsPhpUnitTrait
{
    /**
     * @var array
     */
    private static $testedExtraTypes = array(
        "varchar" => "phpunit_varchar",
        "text" => "phpunit_text",
        "int" => "phpunit_int",
        "bool" => "phpunit_bool",
        "double" => "phpunit_double",
        "price" => "phpunit_price",
        "mail" => "phpunit_mail",
        "phone" => "phpunit_phone",
        "url" => "phpunit_url",
        "date" => "phpunit_date",
    );

    /**
     * Create & Enable All Possible Extra Fields on Object Type
     *
     * @param string $elementType Object Type Identifier
     * @param bool   $visible     ExtraField Visible / Hidden
     *
     * @return void
     */
    public static function configurePhpUnitExtraFields($elementType, $visible = true)
    {
        global $db;
        //====================================================================//
        // Load ExtraFields List
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extraFields = new ExtraFields($db);
        //====================================================================//
        // Load array of extrafields for elementype = $this->table_element
        $extraFields->fetch_name_optionals_label($elementType);

        //====================================================================//
        // Load Existing Types for this Element
        $existingTypes = $extraFields->attribute_type['type'];
        if (empty($existingTypes)) {
            $existingTypes = array();
        }

        //====================================================================//
        // Setup all Testing ExtraTypes
        foreach (static::$testedExtraTypes as $extraFieldType => $extraFieldName) {
            //====================================================================//
            // ExtraField Already Exist => Update
            if (in_array($extraFieldName, array_keys($existingTypes), true)) {
                $extraFields->update(
                    (string) $extraFieldName,
                    ucwords((string) $extraFieldName, "_"),
                    $extraFieldType,
                    255,
                    $elementType,
                    0,
                    0,
                    0,
                    array(),
                    1,
                    '',
                    '0',
                    ($visible ? '0':'1')
                );
            //====================================================================//
            // ExtraField Not Found = Create
            } else {
                $extraFields->addExtraField(
                    $extraFieldName,
                    ucwords($extraFieldName, "_"),
                    $extraFieldType,
                    0,
                    '255',
                    $elementType,
                    0,
                    0,
                    '',
                    '0',
                    1,
                    '',
                    '0',
                    ($visible ? '0':'1')
                );
            }
        }
    }
}
