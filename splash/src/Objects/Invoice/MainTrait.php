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

namespace Splash\Local\Objects\Invoice;

/**
 * Dolibarr Customer Orders Fields
 */
trait MainTrait
{
    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMainFields()
    {
        global $langs,$conf;

        //====================================================================//
        // Invoice PaymentDueDate Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->Identifier("date_lim_reglement")
            ->Name($langs->trans("DateMaxPayment"))
            ->MicroData("http://schema.org/Invoice", "paymentDueDate");

        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("total_ht")
            ->Name($langs->trans("TotalHT")." (".$conf->global->MAIN_MONNAIE.")")
            ->MicroData("http://schema.org/Invoice", "totalPaymentDue")
            ->isReadOnly();

        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("total_ttc")
            ->Name($langs->trans("TotalTTC")." (".$conf->global->MAIN_MONNAIE.")")
            ->MicroData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
            ->isReadOnly();

        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//

        //====================================================================//
        // Is Draft
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isDraft")
            ->Name($langs->trans("Invoice")." : ".$langs->trans("Draft"))
            ->Group(html_entity_decode($langs->trans("Status")))
            ->MicroData("http://schema.org/PaymentStatusType", "InvoiceDraft")
            ->Association("isDraft", "isCanceled", "isValidated")
            ->isReadOnly();

        //====================================================================//
        // Is Canceled
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isCanceled")
            ->Name($langs->trans("Invoice")." : ".$langs->trans("Canceled"))
            ->Group(html_entity_decode($langs->trans("Status")))
            ->MicroData("http://schema.org/PaymentStatusType", "PaymentDeclined")
            ->Association("isDraft", "isCanceled", "isValidated")
            ->isReadOnly();

        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isValidated")
            ->Name($langs->trans("Invoice")." : ".$langs->trans("Validated"))
            ->Group(html_entity_decode($langs->trans("Status")))
            ->MicroData("http://schema.org/PaymentStatusType", "PaymentDue")
            ->Association("isDraft", "isCanceled", "isValidated")
            ->isReadOnly();

        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isPaid")
            ->Name($langs->trans("Invoice")." : ".$langs->trans("Paid"))
            ->Group(html_entity_decode($langs->trans("Status")))
            ->MicroData("http://schema.org/PaymentStatusType", "PaymentComplete")
            ->isNotTested();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Order Delivery Date
            case 'date_lim_reglement':
                $date = $this->object->date_lim_reglement;
                $this->out[$fieldName] = !empty($date)?dol_print_date($date, '%Y-%m-%d'):null;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getTotalsFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_ht':
            case 'total_ttc':
            case 'total_vat':
                $this->getSimple($fieldName);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getStateFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//

            case 'isDraft':
                $this->out[$fieldName] = (0 == $this->object->statut)    ?   true:false;

                break;
            case 'isCanceled':
                $this->out[$fieldName] = (3 == $this->object->statut)   ?   true:false;

                break;
            case 'isValidated':
                $this->out[$fieldName] = (1 == $this->object->statut)    ?   true:false;

                break;
            case 'isPaid':
                $this->out[$fieldName] = (2 == $this->object->statut)    ?   true:false;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setMainFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Invoice Payment Due Date
            case 'date_lim_reglement':
                if (dol_print_date($this->object->{$fieldName}, 'standard') === $fieldData) {
                    break;
                }
                $this->setSimple($fieldName, $fieldData);

                break;
            //====================================================================//
            // PAYMENT STATUS
            //====================================================================//
            case 'isPaid':
                $this->setPaidFlag($fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param mixed $data Field Data
     *
     * @return bool
     */
    private function setPaidFlag($data)
    {
        global $user;

        //====================================================================//
        // If Status Is Not Validated => Cannot Update This Flag
        if (($data == $this->object->paye) || ($this->object->statut < 1)) {
            return true;
        }

        if ($data) {
            //====================================================================//
            // Set Paid using Dolibarr Function
            if ((1 == $this->object->statut) && (1 != $this->object->set_paid($user))) {
                return $this->catchDolibarrErrors();
            }
        } else {
            //====================================================================//
            // Set UnPaid using Dolibarr Function
            if ((2 == $this->object->statut) && (1 != $this->object->set_unpaid($user))) {
                return $this->catchDolibarrErrors();
            }
        }

        //====================================================================//
        // Setup Current Object not to Overite changes with Update
        $this->object->paye = ($data ? 1 : 0);

        return true;
    }
}
