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

/**
 * Dolibarr Products Fields
 */
trait DimensionsTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildDimensionsFields(): void
    {
        global $langs;

        //====================================================================//
        // Weight
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("weight")
            ->name($langs->trans("Weight"))
            ->description($langs->trans("Weight")." (".$langs->trans("WeightUnitkg").")")
            ->microData("http://schema.org/Product", "weight")
        ;
        //====================================================================//
        // Length
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("length")
            ->name($langs->trans("Length"))
            ->description($langs->trans("Length")." (".$langs->trans("LengthUnitm").")")
            ->microData("http://schema.org/Product", "depth")
        ;
        //====================================================================//
        // Width
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("width")
            ->name($langs->trans("Width"))
            ->description($langs->trans("Width")." (".$langs->trans("LengthUnitm").")")
            ->microData("http://schema.org/Product", "width")
            ->isNotTested()
        ;
        //====================================================================//
        // Height
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("height")
            ->name($langs->trans("Height"))
            ->description($langs->trans("Heigth")." (".$langs->trans("LengthUnitm").")")
            ->microData("http://schema.org/Product", "height")
            ->isNotTested()
        ;
        //====================================================================//
        // Surface
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("surface")
            ->name($langs->trans("Surface"))
            ->description($langs->trans("Surface")." (".$langs->trans("SurfaceUnitm2").")")
            ->microData("http://schema.org/Product", "surface")
        ;
        //====================================================================//
        // Volume
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("volume")
            ->name($langs->trans("Volume"))
            ->description($langs->trans("Volume")." (".$langs->trans("VolumeUnitm3").")")
            ->microData("http://schema.org/Product", "volume")
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getDimensionsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'weight':
                $this->out[$fieldName] = (float) $this->convertWeight(
                    $this->object->weight,
                    $this->object->weight_units
                );

                break;
            case 'length':
            case 'width':
            case 'height':
                $this->out[$fieldName] = (float) $this->convertLength(
                    $this->object->{ $fieldName },
                    $this->object->length_units
                    // $this->object->{ $fieldName."_units" }
                );

                break;
            case 'surface':
                $this->out[$fieldName] = (float) $this->convertSurface(
                    $this->object->surface,
                    $this->object->surface_units
                );

                break;
            case 'volume':
                $this->out[$fieldName] = (float) $this->convertVolume(
                    $this->object->volume,
                    $this->object->volume_units
                );

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
    protected function setDimensionsFields(string $fieldName, ?string $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'weight':
                $this->updateProductWeight((float) $fieldData);

                break;
            case 'surface':
                if ((string) $fieldData !== (string) $this->convertSurface(
                    (float) $this->object->surface ?: 0.0,
                    $this->object->surface_units
                )) {
                    $normalized = $this->normalizeSurface((float) $fieldData);
                    $this->object->surface = $normalized->surface;
                    $this->object->surface_units = $normalized->surface_units;
                    $this->needUpdate();
                }

                break;
            case 'volume':
                if ((string) $fieldData !== (string) $this->convertVolume(
                    (float) $this->object->volume ?: 0.0,
                    $this->object->volume_units
                )) {
                    $normalized = $this->normalizeVolume((float) $fieldData);
                    $this->object->volume = $normalized->volume;
                    $this->object->volume_units = $normalized->volume_units;
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setDimensionsValuesFields(string $fieldName, ?string $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'width':
            case 'height':
            case 'length':
                if ((string)$fieldData !== (string) $this->convertLength(
                    (float) $this->object->{ $fieldName } ?: 0.0,
                    $this->object->length_units
                )) {
                    $nomalized = $this->normalizeLength((float) $fieldData);
                    $this->object->{ $fieldName } = $nomalized->length;
                    $this->object->length_units = $nomalized->length_units;
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Update Product Weight with Variants Management
     *
     * Concepts:
     *  - Standards Products: Weight is Normalized to best Unit
     *  - Variants: Weight is Stored using Parent Unit
     *  - Variants Impact: Computed & Stored Using Parent Unit
     *
     * @param float $fieldData
     *
     * @return void
     */
    private function updateProductWeight(float $fieldData)
    {
        //====================================================================//
        // Check if Product Weight Updated => NO CHANGES
        $weightStr = $this->convertWeight($this->object->weight, $this->object->weight_units);
        if ((string) $fieldData == (string) $weightStr) {
            return;
        }
        //====================================================================//
        // Update Current Product Weight (With Variant Detection)
        $normalized = $this->normalizeWeight($fieldData);
        $this->object->weight = $normalized->weight;
        $this->object->weight_units = $normalized->weight_units;
        $this->needUpdate();
        //====================================================================//
        // Update Current Product Weight
        if ($this->isVariant() && !empty($this->baseProduct)) {
            // Update Combination Weight Impact
            $this->setSimple(
                "variation_weight",
                $normalized->weight - $this->baseProduct->weight,
                "combination"
            );
        }
    }
}
