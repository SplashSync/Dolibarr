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

use _PHPStan_a2a733b6a\Nette\PhpGenerator\Method;
use Splash\Local\Local;

/**
 * Manage Access to Payment Methods
 *
 * @phpstan-type MethodDef array{id: int, code: string, label: string, type: int|string, active: int}
 */
class PaymentMethods
{
    const KNOWN = array(
        "ByBankTransferInAdvance" => array("VIR", "TIP", "PRO"),
        "CheckInAdvance" => array("CHQ"),
        "COD" => array("FAC"),
        "ByInvoice" => array("PRE"),
        "Cash" => array("LIQ"),
        "DirectDebit" => array("CB"),
        "CreditCard" => array("CB"),
        "PayPal" => array("VAD", "PPL"),
    );

    /**
     * @var null|array
     */
    private static ?array $methods;

    /**
     * Get Payment Method Choices for Fields
     *
     * @param null|bool  $active
     * @param null|array $types
     *
     * @return array<string, string>
     */
    public static function getChoices(?bool $active = true, ?array $types = array(1, 2)): array
    {
        $choices = array();
        //====================================================================//
        // Walk on Available Methods
        foreach (self::getMethods($active, $types) as $method) {
            //====================================================================//
            // Populate Choices
            $code = self::getSplashCode($method['code']) ?? $method['code'];
            if (!isset($choices[$code])) {
                $choices[$code] = sprintf("[%s] %s", $code, $method['label']);
            }
        }

        return  $choices;
    }

    /**
     * Get Payment Methods Infos with Filters
     *
     * @param null|bool  $active
     * @param null|array $types
     *
     * @return array<int|string, MethodDef>
     */
    public static function getMethods(?bool $active = true, ?array $types = array(1, 2)): array
    {
        $methods = self::getAllMethods();
        //====================================================================//
        // Filter Inactive
        if (isset($active)) {
            $methods = array_filter($methods, function (array $method) use ($active) {
                return ($method["active"] == $active);
            });
        }
        //====================================================================//
        // Filter By Type
        if (isset($types)) {
            $methods = array_filter($methods, function (array $method) use ($types) {
                return in_array((int) $method["type"], $types, true);
            });
        }

        return $methods;
    }

    /**
     * Decode Payment Method Code to Known Splash Codes
     *
     * @param null|string $doliCode
     *
     * @return null|string
     */
    public static function getSplashCode(?string $doliCode): ?string
    {
        //====================================================================//
        // Walk on Known Methods
        foreach (self::KNOWN as $name => $code) {
            if (in_array((string) $doliCode, $code, true)) {
                return $name;
            }
        }

        return $doliCode;
    }

    /**
     * Decode Splash Codes to Dolibarr Payment Method Code
     *
     * @param string $splashCode
     *
     * @return null|string
     */
    public static function getDoliCode(string $splashCode): ?string
    {
        //====================================================================//
        // Walk on Known Methods
        $typeCodes = self::KNOWN[$splashCode] ?? null;
        if (is_array($typeCodes)) {
            return array_shift($typeCodes);
        }
        //====================================================================//
        // Walk on Available Methods - Search by Code
        foreach (self::getAllMethods() as $method) {
            if ($method["code"] == $splashCode) {
                return $method["code"];
            }
        }
        //====================================================================//
        // Walk on Available Methods - Search by Label
        foreach (self::getAllMethods() as $method) {
            if ($method["label"] == $splashCode) {
                return $method["code"];
            }
        }

        //====================================================================//
        // Return Default Payment Method
        return Local::getParameter("SPLASH_DEFAULT_PAYMENT");
    }

    /**
     * Decode Splash Codes to Dolibarr Payment Method Code
     *
     * @param string $splashCode
     *
     * @return null|int
     */
    public static function getDoliId(string $splashCode): ?int
    {
        $doliCode = self::getDoliCode($splashCode);

        //====================================================================//
        // Walk on Available Methods - Search by Label
        foreach (self::getAllMethods() as $method) {
            if ($method["code"] == $doliCode) {
                return (int) $method["id"];
            }
        }

        return null;
    }

    /**
     * Get All Payment Methods Infos
     *
     * @return array<int|string, MethodDef>
     */
    private static function getAllMethods(): array
    {
        global $db;

        if (!isset(self::$methods)) {
            //====================================================================//
            // Include Object Dolibarr Class
            require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
            $form = new \Form($db);
            $form->load_cache_types_paiements();
            //====================================================================//
            // Safety Check
            if (empty($form->cache_types_paiements)) {
                return self::$methods = array();
            }

            return self::$methods = $form->cache_types_paiements;
        }

        return self::$methods;
    }
}
