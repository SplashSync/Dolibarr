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
use Splash\Models\Helpers\InlineHelper;

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
     * ExtraFields Attributes
     *
     * @var array<string, array<string, mixed>>
     */
    private $extraFieldsAttrs;

    /**
     * @var string
     */
    private $extraPrefix = "options_";

    /**
     * @var null|string
     */
    private $extraInList;

    //====================================================================//
    // Generic Splash Fields Access Functions
    //====================================================================//

    /**
     * Build ExtraFields using FieldFactory
     *
     * @return void
     */
    public function buildExtraFields()
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
                ->create($this->getSplashType($fieldType))
                ->identifier($this->encodeType($fieldId))
                ->name($this->getLabel($fieldId))
                ->group("Extra")
                ->addOption('maxLength', '14')
                ->microData("http://meta.schema.org/additionalType", $fieldId);

            if ($this->isRequired($fieldId)) {
                $this->fieldsFactory()->isRequired();
            }
            if ($this->isReadOnly($fieldId)) {
                $this->fieldsFactory()->isReadOnly();
            }
            if ($this->extraInList) {
                $this->fieldsFactory()->inList($this->extraInList);
            }
            if (in_array($fieldType, array('select', "checkbox"), true)) {
                $this->fieldsFactory()->addChoices($this->getChoices($fieldType, $fieldId));
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
    protected function getExtraFields(string $key, string $fieldName): void
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
            case SPL_T_INLINE:
                $fieldType = (string) $this->decodeType($fieldName);
                //====================================================================//
                // Explode Storage Value
                $value = explode(",", $fieldData);
                //====================================================================//
                // Build Intersection Value
                $this->out[$fieldName] = InlineHelper::fromArray(
                    array_intersect_key($this->getOptions($fieldType), array_flip($value))
                );

                break;
            case SPL_T_PRICE:
                $this->out[$fieldName] = self::prices()->Encode(
                    (double) $fieldData,
                    0.0,
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
    protected function setExtraFields(string $fieldName, $fieldData)
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
            case SPL_T_INLINE:
                $fieldType = (string) $this->decodeType($fieldName);
                //====================================================================//
                // Build Storage Value
                $fieldDataStorage = implode(',', array_keys(
                    array_intersect($this->getOptions($fieldType), InlineHelper::toArray($fieldData))
                ));
                //====================================================================//
                // Compare with Current
                if ($currentData != $fieldDataStorage) {
                    $this->object->array_options[$fieldName] = $fieldDataStorage;
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
    protected function getExtraData(string $fieldName)
    {
        global $conf, $object;

        $fieldType = (string) $this->decodeType($fieldName);
        //====================================================================//
        // Detect Computed Data
        $computeSource = $this->getExtraFieldAttr($fieldType, "computed");
        if (!empty($computeSource) && is_scalar($computeSource) && empty($conf->disable_compute)) {
            //====================================================================//
            // Evaluate Computed Data
            try {
                $object = $this->object;

                return dol_eval((string) $computeSource, 1, 0);
            } catch (\Throwable $ex) {
                return null;
            }
        }
        //====================================================================//
        // Extract Generic Field Data
        if (isset($this->object->array_options) && array_key_exists($fieldName, $this->object->array_options)) {
            return $this->object->array_options[$fieldName];
        }

        return null;
    }

    /**
     * Setup for Pushing Fields to a Splash List Name
     *
     * @param string $listName List Identifier
     *
     * @return void
     */
    protected function setInList(string $listName): void
    {
        $this->extraInList = $listName;
    }

    /**
     * Load ExtraFields Definition
     *
     * @param null|string $elementType
     *
     * @return void
     */
    private function loadExtraFields(string $elementType = null)
    {
        global $db;
        //====================================================================//
        // Load ExtraFields List
        if (null == $this->extraFields) {
            require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $this->extraFields = new ExtraFields($db);
            $key = is_null($elementType) ? static::$extraFieldsType : $elementType;
            $this->extraFields->fetch_name_optionals_label($key, true);
            $this->extraFieldsAttrs = $this->extraFields->attributes[$key] ?? array();
        }
    }

    /**
     * Encode Dolibarr ExtraFields Types Id
     *
     * @param string $fieldType
     *
     * @return string
     */
    private function encodeType(string $fieldType): string
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
    private function decodeType(string $fieldType): ?string
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
    private function getExtraTypes(): array
    {
        return $this->extraFieldsAttrs['type'] ?? array();
    }

    /**
     * Check if is Dolibarr ExtraFields Types
     *
     * @param mixed $fieldType
     *
     * @return bool
     */
    private function isExtraType($fieldType): bool
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
     * @param string $fieldType Dolibarr Extra field Type NAme
     *
     * @return null|string Splash Field Type
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getSplashType(string $fieldType): ?string
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
                return SPL_T_BOOL;
            case "checkbox":
                return SPL_T_INLINE;
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
     * Get Splash Type from ExtraFields ID
     *
     * @param string $fieldName
     *
     * @return null|string
     */
    private function getSplashTypeFromId(string $fieldName): ?string
    {
        $fieldType = $this->getExtraFieldAttr(
            (string) $this->decodeType($fieldName),
            "type"
        );

        return is_scalar($fieldType)
            ? $this->getSplashType((string) $fieldType)
            : null
        ;
    }

    /**
     * Get ExtraField Label
     *
     * @param string $fieldType Dolibarr Extra field Type NAme
     *
     * @return string Splash Field Label
     */
    private function getLabel(string $fieldType): string
    {
        global $langs;

        //====================================================================//
        // Load ExtraField Label with Translation
        $langFile = $this->getExtraFieldAttr($fieldType, "langfile");
        $label = $this->getExtraFieldAttr($fieldType, "label");
        if (!empty($langFile) && is_scalar($langFile)) {
            $langs->load((string) $langFile);

            return $langs->trans(is_scalar($label) ? (string) $label : $fieldType);
        }

        return is_scalar($label) ? (string) $label : $fieldType;
    }

    /**
     * Get ExtraField Required Flag
     *
     * @param string $fieldType Dolibarr Extra field Type Name
     *
     * @return bool
     */
    private function isRequired(string $fieldType): bool
    {
        return (bool) $this->getExtraFieldAttr($fieldType, "required");
    }

    /**
     * Get ExtraField ReadOnly Flag
     *
     * @param string $fieldType Dolibarr Extra field Type Name
     *
     * @return bool
     */
    private function isReadOnly(string $fieldType): bool
    {
        return (bool) $this->getExtraFieldAttr($fieldType, "computed");
    }

    /**
     * Get ExtraField Options
     *
     * @param string $fieldType Dolibarr Extra field Type Name
     *
     * @return string[]
     */
    private function getOptions(string $fieldType): array
    {
        $param = $this->getExtraFieldAttr($fieldType, "param");
        if (!is_array($param) || empty($param["options"]) || !is_array($param["options"])) {
            return array();
        }

        return $param["options"];
    }

    /**
     * Get ExtraField Choices
     *
     * @param string $fieldType Dolibarr Extra field Type
     * @param string $fieldId   Dolibarr Extra field Name
     *
     * @return array<string, string>
     */
    private function getChoices(string $fieldType, string $fieldId): array
    {
        if ('select' == $fieldType) {
            return $this->getOptions($fieldId);
        }

        $choices = array();
        foreach ($this->getOptions($fieldId) as $option) {
            $choices[$option] = $option;
        }

        return $choices;
    }

    /**
     * Get ExtraField Attribute Value
     *
     * @param string $fieldType Dolibarr Extra field Type Name
     * @param string $attrType  Dolibarr Extra field Attribute Code
     *
     * @return null|bool|string|string[]
     */
    private function getExtraFieldAttr(string $fieldType, string $attrType)
    {
        $this->loadExtraFields();

        return $this->extraFieldsAttrs[$attrType][$fieldType] ?? null;
    }
}
