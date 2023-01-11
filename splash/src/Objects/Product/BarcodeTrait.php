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

namespace   Splash\Local\Objects\Product;

use Product;

/**
 * Dolibarr Products BarCodes Fieldsconst
 */
trait BarcodeTrait
{
    /**
     * @var string
     */
    private string $defaultBarcodeType;

    /**
     * List of Integer barcodes Types
     *
     * @var string[]
     */
    private static array $intBarcodes = array(
        "EAN8", "EAN13", "UPC",
        "ISBN", "C39",
    );

    /**
     * List of Known Barcodes Schemas
     *
     * @var array<string, string>
     */
    private static array $knownBarcodes = array(
        "EAN8" => "gtin8",
        "EAN13" => "gtin13",
        "UPC" => "gtin12",
        "ISBN" => "gtin14",
    );

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildBarcodeFields()
    {
        global $langs;

        //====================================================================//
        // Bar Code Value
        $this->fieldsFactory()->create($this->getBarcodeFormat())
            ->identifier("barcode")
            ->name($langs->trans("BarcodeValue"))
            ->description($langs->trans("BarcodeValue")." (".$this->getDefaultBarcodeType().")")
            ->microData("http://schema.org/Product", $this->getBarcodeSchema())
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
    protected function getBarcodeFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'barcode':
                $this->getSimple($fieldName);

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
    protected function setBarcodeFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
            case 'barcode':
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Detect Default BarCode Type Code
     *
     * @return string
     */
    private function getDefaultBarcodeType(): string
    {
        global $db, $conf;
        //====================================================================//
        // Load from Cache
        if (isset($this->defaultBarcodeType)) {
            return $this->defaultBarcodeType;
        }
        //====================================================================//
        // Check if Default Bar Code Type is Defined
        if (empty($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE)) {
            return $this->defaultBarcodeType = "";
        }
        //====================================================================//
        // Load Bar Code Type Values
        $common = new Product($db);
        $common->barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
        $common->barcode_type_code = "";
        $common->fetch_barcode();
        //====================================================================//
        // Return Bar Code Name
        $this->defaultBarcodeType = empty($common->barcode_type_code) ? "" : $common->barcode_type_code;

        return $this->defaultBarcodeType;
    }

    /**
     * Get Default BarCode Type Splash Type
     *
     * @return string
     */
    private function getBarcodeFormat(): string
    {
        //====================================================================//
        // Get Default Bar Code Type Name
        $dfType = $this->getDefaultBarcodeType();
        //====================================================================//
        // Check if Int Barcode Type
        if (empty($dfType) || !in_array($dfType, self::$intBarcodes, true)) {
            return SPL_T_VARCHAR;
        }

        return SPL_T_INT;
    }

    /**
     * Get Default BarCode Type Splash Type
     *
     * @return string
     */
    private function getBarcodeSchema(): string
    {
        //====================================================================//
        // Get Default Bar Code Type Name
        $dfType = $this->getDefaultBarcodeType();
        //====================================================================//
        // Check if Int Barcode Type
        if (empty($dfType) || !isset(self::$knownBarcodes[$dfType])) {
            return "qrcode";
        }

        return self::$knownBarcodes[$dfType];
    }
}
