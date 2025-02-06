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

namespace Splash\Local\Core;

use Splash\Core\SplashCore as Splash;
use Splash\Local\Objects\Order;
use Splash\Local\Services\ThirdPartyIdentifier;

/**
 * Dolibarr Customer Fields (Required)
 */
trait CustomerTrait
{
    /**
     * Detected SocId
     *
     * @var null|int
     */
    private ?int $socId;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCustomerFields(): void
    {
        global $langs;

        //====================================================================//
        // Customer Object Link
        $this->fieldsFactory()->create((string) self::objects()->encode("ThirdParty", SPL_T_ID))
            ->identifier("socid")
            ->name($langs->trans("Company"))
            ->isRequired(!$this->isAllowedGuest())
        ;
        //====================================================================//
        // Metadata are Specific to Object Type (Order/Invoice)
        if ($this instanceof Order) {
            $this->fieldsFactory()->microData("http://schema.org/Organization", "ID");
        } else {
            $this->fieldsFactory()->microData("http://schema.org/Invoice", "customer");
        }
        //====================================================================//
        // Customer Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
            ->Identifier("email")
            ->Name($langs->trans("Email"))
            ->MicroData("http://schema.org/ContactPoint", "email")
            ->isReadOnly(!$this->isAllowedEmailDetection())
            ->isNotTested()
        ;
    }

    /**
     * Init Customer SocId Detection
     *
     * @return void
     */
    protected function initCustomerDetection()
    {
        //====================================================================//
        // Order/Invoice Create Mode => Init SocId detection
        $this->socId = null;
    }

    /**
     * Init Customer SocId with Guest Mode Management
     *
     * @param array $receivedData Received Data
     *
     * @return void
     */
    protected function doCustomerDetection(array $receivedData): void
    {
        //====================================================================//
        // Order/Invoice Create Mode => Init SocId detection
        $this->initCustomerDetection();
        //====================================================================//
        // Standard Mode => A SocId is Given
        /** @var null|scalar $socId */
        $socId = $receivedData["socid"] ?? null;
        if ($socId && !empty(self::objects()->id((string) $socId))) {
            $this->setSimple("socid", self::objects()->id((string) $socId));

            return;
        }
        //====================================================================//
        // Guest Mode is Disabled => Error
        if (!$this->isAllowedGuest()) {
            Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "socid");

            return;
        }
        //====================================================================//
        // Guest Mode => Detect SocId in Guest Mode
        $this->setSimple("socid", $this->getGuestCustomer($receivedData));
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCustomerFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // ThirdParty Id
            case 'socid':
                $this->out[$fieldName] = self::objects()
                    ->encode("ThirdParty", (string) $this->object->socid)
                ;

                break;
                //====================================================================//
                // ThirdParty Email
            case 'email':
                $this->object->thirdparty ?? $this->object->fetch_thirdparty();
                $this->out[$fieldName] = !empty($this->object->thirdparty->email)
                    ? $this->object->thirdparty->email
                    : null
                ;

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
    protected function setCustomerFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Customer Id
            case 'socid':
                //====================================================================//
                // Standard Mode => A SocId is Required
                if (!$this->isAllowedGuest()) {
                    $this->setSimple($fieldName, self::objects()->id((string) $fieldData));

                    break;
                }
                $this->setSimple($fieldName, $this->getGuestCustomer($this->in));

                break;
                //====================================================================//
                // Customer Email
            case 'email':
                if (!$this->isAllowedGuest() || !$this->isAllowedEmailDetection()) {
                    break;
                }
                $this->setSimple("socid", $this->getGuestCustomer($this->in));

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);
    }

    /**
     * Detect Guest Customer To Use for This Order/Invoice
     *
     * @param array $receivedData Received Data
     *
     * @return int
     */
    protected function getGuestCustomer(array $receivedData): int
    {
        global $conf;

        //====================================================================//
        // Customer detection Already Done
        if (!is_null($this->socId)) {
            return $this->socId;
        }

        //====================================================================//
        // Standard Mode => A SocId is Given
        if (isset($receivedData["socid"]) && !empty(self::objects()->id($receivedData["socid"]))) {
            Splash::log()->deb("Customer Id Given : Id ".self::objects()->id($receivedData["socid"]));
            $this->socId = (int) self::objects()->id($receivedData["socid"]);

            return $this->socId;
        }
        //====================================================================//
        // Detect ThirdParty Using Given Email
        if ($this->isAllowedEmailDetection() && isset($receivedData["email"]) && !empty($receivedData["email"])) {
            $societe = ThirdPartyIdentifier::findOneByEmail($receivedData["email"]);
            $this->socId = $societe ? $societe->id : null;
            Splash::log()->deb("Customer Email Identified : Id ".$this->socId);
        }
        //====================================================================//
        // Select ThirdParty Using Default Parameters
        if (empty($this->socId)) {
            $this->socId = $conf->global->SPLASH_GUEST_ORDERS_CUSTOMER;
            Splash::log()->deb("Default Customer Used : Id ".$this->socId);
        }

        return $this->socId;
    }

    /**
     * Check if Guest Orders Are Allowed
     *
     * @return bool
     */
    private function isAllowedGuest(): bool
    {
        global $conf;
        if (!isset($conf->global->SPLASH_GUEST_ORDERS_ALLOW) || empty($conf->global->SPLASH_GUEST_ORDERS_ALLOW)) {
            return false;
        }

        if (!isset($conf->global->SPLASH_GUEST_ORDERS_CUSTOMER) || empty($conf->global->SPLASH_GUEST_ORDERS_CUSTOMER)) {
            Splash::log()->errTrace(
                "To use Guest Orders/Invoices mode, you must select a default Customer."
            );

            return false;
        }

        return true;
    }

    /**
     * Check if Email Detection is Active
     *
     * @return bool
     */
    private function isAllowedEmailDetection(): bool
    {
        global $conf;
        if (!$this->isAllowedGuest() || !isset($conf->global->SPLASH_GUEST_ORDERS_EMAIL)) {
            return false;
        }

        return (bool) $conf->global->SPLASH_GUEST_ORDERS_EMAIL;
    }
}
