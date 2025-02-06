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

namespace Splash\Local\Objects\Common;

use Splash\Local\Services\ShippingMethods;

/**
 * Access to Orders Shipping Method
 */
trait ShippingMethodTrait
{
    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildShippingMethodFields(): void
    {
        global $langs;

        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("shipping_method_id")
            ->name($langs->trans("SendingMethod"))
            ->microData("http://schema.org/ParcelDelivery", "identifier")
            ->addChoices(ShippingMethods::getChoices())
            ->setPreferRead()
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
    protected function getShippingMethodFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'shipping_method_id':
                $this->out[$fieldName] = ShippingMethods::getCode($this->object->shipping_method_id);

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
    protected function setShippingMethodFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'shipping_method_id':
                $this->setSimple($fieldName, (int) ShippingMethods::getDoliId((string) $fieldData));

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
