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

use Splash\Local\Local;

/**
 * Manage Access to Payment Terms / Conditions
 *
 * @phpstan-type TermDef array{code: string, label: string}
 */
class PaymentTerms
{
    /**
     * @var null|array<int|string, TermDef>
     */
    private static ?array $terms;

    /**
     * Get Payment Terms Choices for Fields
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        $choices = array();
        //====================================================================//
        // Walk on Available Terms
        foreach (self::getAllTerms() as $term) {
            $choices[$term['code']] = sprintf("[%s] %s", $term['code'], $term['label']);
        }

        return $choices;
    }

    /**
     * Get Dolibarr Payment Term ID from Code
     *
     * @param string $code
     *
     * @return null|int
     */
    public static function getDoliId(string $code): ?int
    {
        //====================================================================//
        // Walk on Available Terms - Search by Code
        foreach (self::getAllTerms() as $id => $term) {
            if ($term["code"] == $code) {
                return (int) $id;
            }
        }
        //====================================================================//
        // Walk on Available Terms - Search by Label
        foreach (self::getAllTerms() as $id => $term) {
            if ($term["label"] == $code) {
                return (int) $id;
            }
        }

        //====================================================================//
        // Return Default Payment Term
        return self::getDefaultId();
    }

    /**
     * Get Payment Term Code from Dolibarr ID
     *
     * @param null|int $doliId
     *
     * @return null|string
     */
    public static function getCode(?int $doliId): ?string
    {
        if (!$doliId) {
            return null;
        }
        $terms = self::getAllTerms();

        return $terms[$doliId]['code'] ?? null;
    }

    /**
     * Get Default Payment Term ID (verified as existing and active)
     *
     * @return null|int
     */
    public static function getDefaultId(): ?int
    {
        $terms = self::getAllTerms();
        //====================================================================//
        // Check Splash Default Payment Term
        $splashDefault = Local::getParameter("SPLASH_DEFAULT_PAYMENT_TERM");
        if ($splashDefault && isset($terms[(int) $splashDefault])) {
            return (int) $splashDefault;
        }

        return null;
    }

    /**
     * Get All Payment Terms Infos
     *
     * @return array<int|string, TermDef>
     */
    private static function getAllTerms(): array
    {
        global $db;

        if (!isset(self::$terms)) {
            //====================================================================//
            // Include Object Dolibarr Class
            require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
            $form = new \Form($db);
            $form->load_cache_conditions_paiements();
            //====================================================================//
            // Safety Check
            if (empty($form->cache_conditions_paiements)) {
                return self::$terms = array();
            }

            return self::$terms = $form->cache_conditions_paiements;
        }

        return self::$terms;
    }
}
