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
 * Access to Dolibarr Extra Fields
 */
trait ExtraFieldsTrait
{
    /**
     * @var ExtraFields
     */
    private $extraFields;

    /**
     * @var string
     */
    private $extraPrefix = "options_";

    //====================================================================//
    // Generic Splash Fields Access Functions
    //====================================================================//

    /**
     * Build ExtraFields using FieldFactory
     *
     * @return void
     */
    protected function buildExtraFields()
    {
        //====================================================================//
        // Load ExtraFields List
        $this->loadExtraFields();
        //====================================================================//
        // Run All ExtraFields List
        foreach ($this->getExtraTypes() as $fieldId => $fieldType) {
            //====================================================================//
            // Skip Incompatibles Types
            if (empty($this->getSplashType($fieldType))) {
                continue;
            }
            //====================================================================//
            // Create Extra Field Definition
            $this->fieldsFactory()
                ->Create($this->getSplashType($fieldType))
                ->Identifier($this->extraPrefix.$fieldId)
                ->Name($this->getLabel($fieldId))
                ->Group("Extra")
                ->addOption('maxLength', '14')
                ->MicroData("http://meta.schema.org/additionalType", $fieldId);

            if ($this->isRequired($fieldId)) {
                $this->fieldsFactory()->isRequired();
            }
            if ($this->isReadOnly($fieldId)) {
                $this->fieldsFactory()->isReadOnly();
            }
        }
    }

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getExtraFields($key, $fieldName)
    {
        global $conf;
        $this->loadExtraFields();
        //====================================================================//
        // Check is Extra Field
        if (!$this->isExtraType($fieldName)) {
            return;
        }
        //====================================================================//
        // Extract Field Data
        $fieldData = $this->getExtraData($fieldName);
        //====================================================================//
        // READ Field Data
        switch ($this->getSplashTypeFromId($fieldName)) {
            case SPL_T_VARCHAR:
            case SPL_T_TEXT:
            case SPL_T_PHONE:
            case SPL_T_URL:
            case SPL_T_EMAIL:
                $this->out[$fieldName] = $fieldData;

                break;
            case SPL_T_DATE:
            case SPL_T_DATETIME:
                if (!is_numeric($fieldData)) {
                    $this->out[$fieldName] = $fieldData;
                } else {
                    $this->out[$fieldName] = dol_print_date((int) $fieldData, 'dayrfc');
                }

                break;
            case SPL_T_INT:
                $this->out[$fieldName] = (int) $fieldData;

                break;
            case SPL_T_DOUBLE:
                $this->out[$fieldName] = (double) $fieldData;

                break;
            case SPL_T_BOOL:
                $this->out[$fieldName] = (bool) $fieldData;

                break;
            case SPL_T_PRICE:
                $this->out[$fieldName] = self::prices()->Encode(
                    (double) $fieldData,
                    (double) 0,
                    null,
                    $conf->global->MAIN_MONNAIE
                );

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    //====================================================================//
    // Fields Writing Functions
    //====================================================================//

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setExtraFields($fieldName, $fieldData)
    {
        $this->loadExtraFields();
        //====================================================================//
        // Check is Extra Field
        if (!$this->isExtraType($fieldName)) {
            return;
        }
        //====================================================================//
        // Extract Field Data
        $currentData = $this->getExtraData($fieldName);
        //====================================================================//
        // READ Field Data
        switch ($this->getSplashTypeFromId($fieldName)) {
            case SPL_T_VARCHAR:
            case SPL_T_TEXT:
            case SPL_T_DATE:
            case SPL_T_PHONE:
            case SPL_T_URL:
            case SPL_T_EMAIL:
            case SPL_T_INT:
            case SPL_T_DOUBLE:
            case SPL_T_BOOL:
                if ($currentData != $fieldData) {
                    $this->object->array_options[$fieldName] = $fieldData;
                    $this->needUpdate();
                }

                break;
            case SPL_T_DATETIME:
                if ($currentData != $fieldData) {
                    date_default_timezone_set('UTC');
                    $this->object->array_options[$fieldName] = $fieldData;
                    $this->needUpdate();
                }

                break;
            case SPL_T_PRICE:
                $priceHT = self::prices()->TaxExcluded($fieldData);
                if ($currentData != $priceHT) {
                    $this->object->array_options[$fieldName] = $priceHT;
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);
    }

    /**
     * Get Dolibarr ExtraField Data
     *
     * @param string $fieldName Field Identifier / Name
     *
     * @return mixed
     */
    protected function getExtraData($fieldName)
    {
        //====================================================================//
        // Extract Field Data
        if (isset($this->object->array_options) && array_key_exists($fieldName, $this->object->array_options)) {
            return $this->object->array_options[$fieldName];
        }

        return null;
    }

    /**
     * Load ExtraFields Definition
     *
     * @param string $elementType
     *
     * @return void
     */
    private function loadExtraFields($elementType = null)
    {
        global $db;
        //====================================================================//
        // Load ExtraFields List
        if (null == $this->extraFields) {
            require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $this->extraFields = new ExtraFields($db);
            $key = is_null($elementType) ? static::$ExtraFieldsType : $elementType;
            $this->extraFields->fetch_name_optionals_label($key, true);
        }
    }

    /**
     * Encode Dolibarr ExtraFields Types Id
     *
     * @param string $fieldType
     *
     * @return string
     */
    private function encodeType($fieldType)
    {
        return $this->extraPrefix.$fieldType;
    }

    /**
     * Decode Dolibarr ExtraFields Types Id
     *
     * @param string $fieldType
     *
     * @return null|string
     */
    private function decodeType($fieldType)
    {
        if (0 == strpos($this->extraPrefix, $fieldType)) {
            return substr($fieldType, strlen($this->extraPrefix));
        }

        return null;
    }

    /**
     * Get Dolibarr ExtraFields Types
     *
     * @return array
     */
    private function getExtraTypes()
    {
        if (empty($this->extraFields->attribute_type)) {
            return array();
        }

        return $this->extraFields->attribute_type;
    }

    /**
     * Check if is Dolibarr ExtraFields Types
     *
     * @param mixed $fieldType
     *
     * @return bool
     */
    private function isExtraType($fieldType)
    {
        if (empty($this->getExtraTypes())) {
            return false;
        }
        if (!in_array($this->decodeType($fieldType), array_keys($this->getExtraTypes()), true)) {
            return false;
        }

        return true;
    }

    /**
     * Convert Dolibarr Field Type to SPlash Type
     *
     * @param string $fieldType Dolibarr Extrafield Type NAme
     *
     * @return null|string Splash Field Type
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getSplashType($fieldType)
    {
        switch ($fieldType) {
            case "varchar":
            case "password":
            case "select":
            case "link":
                return SPL_T_VARCHAR;
            case "text":
                return SPL_T_TEXT;
            case "int":
                return SPL_T_INT;
            case "double":
                return SPL_T_DOUBLE;
            case "date":
                return SPL_T_DATE;
            case "datetime":
                return SPL_T_DATETIME;
            case "boolean":
            case "radio":
            case "checkbox":
                return SPL_T_BOOL;
            case "price":
                return SPL_T_PRICE;
            case "phone":
                return SPL_T_PHONE;
            case "mail":
                return SPL_T_EMAIL;
            case "url":
                return SPL_T_URL;
            case "sellist":
            case "chkbxlst":
            case "separate":
                return null;
        }

        return SPL_T_VARCHAR;
    }

    /**
     * Get Splash Type from ExtraFields Id
     *
     * @param mixed $fieldName
     *
     * @return null|string
     */
    private function getSplashTypeFromId($fieldName)
    {
        $fieldId = $this->decodeType($fieldName);
        $fieldType = $this->extraFields->attribute_type[$fieldId];

        return $this->getSplashType($fieldType);
    }

    /**
     * Get ExtraField Label
     *
     * @param string $fieldType Dolibarr Extrafield Type NAme
     *
     * @return string Splash Field Label
     */
    private function getLabel($fieldType)
    {
        global $langs;

        $this->loadExtraFields();

        //====================================================================//
        // Load ExtraField Label with Translation
        if (!empty($this->extraFields->attribute_langfile[$fieldType])) {
            $langs->load($this->extraFields->attribute_langfile[$fieldType]);

            return $langs->trans($this->extraFields->attribute_label[$fieldType]);
        }

        return $this->extraFields->attribute_label[$fieldType];
    }

    /**
     * Get ExtraField Required Flag
     *
     * @param string $fieldType Dolibarr Extrafield Type NAme
     *
     * @return bool
     */
    private function isRequired($fieldType)
    {
        $this->loadExtraFields();

        return $this->extraFields->attribute_required[$fieldType];
    }

    /**
     * Get ExtraField ReadOnly Flag
     *
     * @param string $fieldType Dolibarr Extrafield Type NAme
     *
     * @return bool
     */
    private function isReadOnly($fieldType)
    {
        $this->loadExtraFields();
        if (isset($this->extraFields->attribute_computed)) {
            return !empty($this->extraFields->attribute_computed[$fieldType]);
        }

        return false;
    }
}
