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

namespace Splash\Local\Objects\Product;

use Splash\Local\Services\UnitConverter;

/**
 * Provides functionality for managing Products unit-related fields in the system.
 */
trait UnitsTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildUnitsFields(): void
    {
        global $conf, $langs;

        if (empty($conf->global->PRODUCT_USE_UNITS)) {
            return;
        }

        $choices = UnitConverter::getDolUnitChoices();
        //====================================================================//
        // Product Sell Unit Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("fk_unit")
            ->name($langs->trans("Unit"))
            ->description($langs->trans("SizeUnits"))
            ->microData("http://schema.org/Product", "sellUnit")
            ->addChoices($choices)
        ;
        //====================================================================//
        // Product Sell Unit Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("fk_unit_name")
            ->name($langs->trans("Unit")." Name")
            ->description(sprintf("[%s] %s", $langs->trans("Label"), $langs->trans("SizeUnits")))
            ->microData("http://schema.org/Product", "sellUnitName")
            ->addChoices(array_combine($choices, $choices))
        ;
    }

    /**
     * Read Requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getUnitsFields(string $key, string $fieldName): void
    {
        global $langs;

        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'fk_unit':
                $unit = UnitConverter::getDolUnitById((int) $this->object->fk_unit);
                $this->out[$fieldName] = $unit ? $unit->code : null;

                break;
            case 'fk_unit_name':
                $unit = UnitConverter::getDolUnitById((int) $this->object->fk_unit);
                $this->out[$fieldName] = $unit ? $langs->trans($unit->label) : null;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setUnitsFields(string $fieldName, ?string $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'fk_unit':
                if ($unit = UnitConverter::getDolUnitByCode($fieldData)) {
                    $this->setSimple($fieldName, $unit->id);
                }

                break;
            case 'fk_unit_name':
                if ($unit = UnitConverter::getDolUnitByLabel($fieldData)) {
                    $this->setSimple('fk_unit', $unit->id);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
