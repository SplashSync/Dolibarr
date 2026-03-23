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

use Splash\Local\Services\PaymentTerms;

/**
 * Access to Order & Invoices Payment Terms / Conditions
 */
trait PaymentTermTrait
{
    /**
     * Build Payment Term Fields using FieldFactory
     *
     * @return void
     */
    protected function buildPaymentTermFields(): void
    {
        global $langs;

        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("cond_reglement_id")
            ->name($langs->trans("PaymentCondition"))
            ->microData("http://schema.org/Invoice", "paymentTerm")
            ->addChoices(PaymentTerms::getChoices())
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
    protected function getPaymentTermFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'cond_reglement_id':
                $this->out[$fieldName] = PaymentTerms::getCode(
                    (int) $this->object->cond_reglement_id
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
    protected function setPaymentTermFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'cond_reglement_id':
                $this->setSimple(
                    $fieldName,
                    PaymentTerms::getDoliId((string) $fieldData)
                );

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
