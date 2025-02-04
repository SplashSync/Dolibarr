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

namespace Splash\Local\Services;

/**
 * Manage Access to Bank Accounts
 *
 * @phpstan-type AccountDef array{
 *  id: int, label: string, ref: string, bank: string, status: int|string, currency: string
 * }
 */
class BankAccounts
{
    /**
     * @var null|array<int|string, AccountDef>
     */
    private static ?array $accounts;

    /**
     * Identify Bank Account ID for Payment Method using Splash Configuration
     *
     * @param null|int $paymentTypeId Payment Method ID
     *
     * @return int Bank Account ID
     */
    public static function getDoliIdFromMethodId(?int $paymentTypeId): int
    {
        global $conf;
        //====================================================================//
        // Safety Check
        $parameterName = "SPLASH_BANK_FOR_".((string) $paymentTypeId);
        if (!$paymentTypeId || empty($conf->global->{$parameterName})) {
            //====================================================================//
            // Default Payment Account Id
            return (int) ($conf->global->SPLASH_BANK ?? 0);
        }

        //====================================================================//
        // Detect Bank Account ID From Method Code
        return $conf->global->{$parameterName};
    }

    /**
     * Get Bank Accounts Choices for Fields
     *
     * @param bool $active Filter on Actives Accounts
     *
     * @return array<string, string>
     */
    public static function getChoices(bool $active = true): array
    {
        $choices = array();
        $accounts = $active ? self::getActives() : self::getAll();
        //====================================================================//
        // Walk on Available Methods
        foreach ($accounts as $accDef) {
            //====================================================================//
            // Populate Choices
            $choices[$accDef['ref']] = sprintf("[%s] %s (%s)", $accDef['ref'], $accDef['label'], $accDef['currency']);
        }

        return  $choices;
    }

    /**
     * Get Bank Accounts Code from Bank Account ID
     *
     * @param null|int $fkBank Bank Account ID
     *
     * @return null|string Bank Account Code
     */
    public static function getCode(?int $fkBank): ?string
    {
        if (!$fkBank) {
            return null;
        }

        return self::getAll()[$fkBank]["ref"] ?? null;
    }

    /**
     * Get Bank Account ID from Bank Account Code
     *
     * @param null|string $ref Bank Account Code
     *
     * @return null|int Bank Account ID
     */
    public static function getDoliId(?string $ref): ?int
    {
        //====================================================================//
        // No Bank Account Code
        if (!$ref) {
            return null;
        }
        //====================================================================//
        // Walk on All Bank Accounts
        foreach (self::getAll() as $accountId => $accountDef) {
            if ($accountDef["ref"] == $ref) {
                return (int) $accountId;
            }
        }

        return null;
    }

    /**
     * Get Active Bank Accounts Infos
     *
     * @return array<int|string, AccountDef>
     */
    public static function getActives(): array
    {
        $actives = array();
        foreach (self::getAll() as $accountId => $accountDef) {
            if (empty($accountDef["status"])) {
                $actives[$accountId] = $accountDef;
            }
        }

        return $actives;
    }

    /**
     * Get All Bank Accounts Infos
     *
     * @return array<int|string, AccountDef>
     */
    private static function getAll(): array
    {
        global $db;

        if (!isset(self::$accounts)) {
            self::$accounts = array();
            //====================================================================//
            // Prepare SQL Request
            $sql = "SELECT rowid as id, ref, label, bank, clos as status, currency_code as currency";
            $sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
            $sql .= " WHERE entity IN (".getEntity('bank_account').")";
            //====================================================================//
            // Execute SQL Request
            $result = $db->query($sql);
            if ($result) {
                $num = $db->num_rows($result);
                $inc = 0;
                while ($inc < $num) {
                    /** @var AccountDef $account */
                    $account = (array)  $db->fetch_object($result);
                    if (!empty($account['ref'])) {
                        self::$accounts[$account['id']] = $account;
                    }

                    $inc++;
                }
            }
        }

        return self::$accounts;
    }
}
