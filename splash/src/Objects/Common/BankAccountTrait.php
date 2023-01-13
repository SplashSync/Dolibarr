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

use Splash\Local\Services\BankAccounts;

/**
 * Access to Order & Invoices Main Bank Account
 */
trait BankAccountTrait
{
    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildBankAccountFields(): void
    {
        global $langs;

        $bankChoices = BankAccounts::getChoices();

        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("fk_account")
            ->name($langs->trans("BankAccount"))
            ->microData("http://schema.org/Invoice", "paymentMethodId")
            ->addChoices($bankChoices)
            ->isNotTested(empty($bankChoices))
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
    protected function getBankAccountFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'fk_account':
                $this->out[$fieldName] = BankAccounts::getCode($this->object->fk_account);

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
    protected function setBankAccountFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'fk_account':
                $fkAccount = BankAccounts::getDoliId((string) $fieldData);
                if ($fkAccount && ($fkAccount != $this->object->fk_account)) {
                    $this->object->setValueFrom('fk_account', $fkAccount);
                    $this->setSimple($fieldName, $fkAccount);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
