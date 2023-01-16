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

use Splash\Local\Services\PaymentMethods;

/**
 * Access to Order & Invoices Payment Method
 */
trait PaymentMethodTrait
{
    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildPaymentMethodFields(): void
    {
        global $langs;

        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("mode_reglement_id")
            ->name($langs->trans("PaymentMode"))
            ->microData("http://schema.org/Invoice", "paymentMethodId")
            ->addChoices(PaymentMethods::getChoices())
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
    protected function getPaymentMethodFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'mode_reglement_id':
                $this->out[$fieldName] = PaymentMethods::getSplashCode($this->object->mode_reglement_code);

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
    protected function setPaymentMethodFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'mode_reglement_id':
                $fkModePayment = PaymentMethods::getDoliId((string) $fieldData);
                if ($this instanceof \FactureFournisseur) {
                    if ($fkModePayment && ($fkModePayment != $this->object->mode_reglement_id)) {
                        $this->object->setValueFrom('fk_mode_reglement', $fkModePayment);
                    }
                } else {
                    $this->setSimple($fieldName, $fkModePayment);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
