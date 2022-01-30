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

namespace Splash\Local\Services;

use FactureLigne;
use OrderLine;
use Splash\Components\FieldsFactory;
use Splash\Local\Core\ExtraFieldsTrait;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\PricesTrait;
use SupplierInvoiceLine;

/**
 * MultiCompany Module Manager
 */
class LinesExtraFieldsParser
{
    use PricesTrait;
    use ExtraFieldsTrait;

    /**
     * @var string
     */
    public static $extraFieldsType;

    /**
     * Static Storage for Caching
     *
     * @var LinesExtraFieldsParser
     */
    private static $instance;

    /**
     * @var FieldsFactory
     */
    private static $factory;

    /**
     * @var FactureLigne|OrderLine|SupplierInvoiceLine
     */
    private $object;

    /**
     * @var array
     */
    private $in = array();

    /**
     * @var array
     */
    private $out = array();

    /**
     * @var bool
     */
    private $needUpdate = false;

    /**
     * Constructor
     *
     * @param FieldsFactory $factory     Parent Fields Factory
     * @param string        $elementType Dolibarr Extra Fields type Code
     */
    public function __construct(FieldsFactory $factory, string $elementType)
    {
        //====================================================================//
        // Store Config
        self::$factory = $factory;
        self::$extraFieldsType = $elementType;
        //====================================================================//
        // Tells Fields Parser to put fields inside a list
        $this->setInList("lines");
    }

    /**
     * Build Parser for a Given Object
     *
     * @param AbstractObject $splashObject
     *
     * @return LinesExtraFieldsParser
     */
    public static function fromSplashObject(AbstractObject $splashObject): LinesExtraFieldsParser
    {
        if (!isset(self::$instance)) {
            self::$instance = new self(
                $splashObject::fieldsFactory(),
                // @phpstan-ignore-next-line
                $splashObject::$extraLineFieldsType ?? ""
            );
        }

        return self::$instance;
    }

    /**
     * Read requested Extra Field
     *
     * @param FactureLigne|OrderLine|SupplierInvoiceLine $item
     * @param string                                     $fieldId Field Identifier / Name
     *
     * @return null|bool|float|int|string
     */
    public function getExtraField(object $item, string $fieldId)
    {
        $this->in["index"] = $fieldId;
        $this->object = $item;
        $this->getExtraFields("index", $fieldId);

        return $this->out[$fieldId] ?? null;
    }

    /**
     * Write Given Extra Field
     *
     * @param FactureLigne|OrderLine|SupplierInvoiceLine $item
     * @param string                                     $fieldName Field Identifier / Name
     * @param mixed                                      $fieldData Field Data
     *
     * @return bool
     */
    public function setExtraField(object $item, string $fieldName, $fieldData): bool
    {
        $this->needUpdate = false;
        $this->in[$fieldName] = $fieldData;
        $this->object = $item;

        $this->setExtraFields($fieldName, $fieldData);

        return $this->needUpdate;
    }

    /**
     * Get Parent Field Factory
     *
     * @return FieldsFactory
     */
    protected static function fieldsFactory(): FieldsFactory
    {
        return self::$factory;
    }

    /**
     * @abstract    Flag Object For Database Update
     *
     * @return self
     */
    protected function needUpdate(): self
    {
        $this->needUpdate = true;

        return $this;
    }
}
