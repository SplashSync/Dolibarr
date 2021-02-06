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

namespace Splash\Local\Core;

use ArrayObject;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Objects\Order;

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
    private $socId;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCustomerFields()
    {
        global $langs;

        //====================================================================//
        // Customer Object Link
        $this->fieldsFactory()->create((string) self::objects()->Encode("ThirdParty", SPL_T_ID))
            ->Identifier("socid")
            ->Name($langs->trans("Company"));

        //====================================================================//
        // Metadata are Specific to Object Type (Order/Invoice)
        if ($this instanceof Order) {
            $this->fieldsFactory()->MicroData("http://schema.org/Organization", "ID");
        } else {
            $this->fieldsFactory()->MicroData("http://schema.org/Invoice", "customer");
        }

        //====================================================================//
        // Not Allowed Guest Orders/Invoices Mode
        if (!$this->isAllowedGuest()) {
            $this->fieldsFactory()->isRequired();

            return;
        }

        //====================================================================//
        // Is Allowed Customer Email Detection
        if ($this->isAllowedEmailDetection()) {
            //====================================================================//
            // Customer Email
            $this->fieldsFactory()->create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name($langs->trans("Email"))
                ->MicroData("http://schema.org/ContactPoint", "email")
                ->isWriteOnly()
                ->isNotTested();
        }
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
     * Init Customer SocId with Guste Mode Management
     *
     * @param Array|ArrayObject $receivedData Received Data
     *
     * @return void
     */
    protected function doCustomerDetection($receivedData)
    {
        //====================================================================//
        // Order/Invoice Create Mode => Init SocId detection
        $this->initCustomerDetection();

        //====================================================================//
        // Standard Mode => A SocId is Given
        if (isset($receivedData["socid"]) && !empty(self::objects()->id($receivedData["socid"]))) {
            $this->setSimple("socid", self::objects()->id($receivedData["socid"]));

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
    protected function getCustomerFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // ThirdParty Id
            case 'socid':
                $this->out[$fieldName] = self::objects()->Encode("ThirdParty", $this->object->{$fieldName});

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
    protected function setCustomerFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Customer Id
            case 'socid':
                //====================================================================//
                // Standard Mode => A SocId is Requiered
                if (!$this->isAllowedGuest()) {
                    $this->setSimple($fieldName, self::objects()->Id($fieldData));

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
     * @param Array|ArrayObject $receivedData Received Data
     *
     * @return int
     */
    protected function getGuestCustomer($receivedData)
    {
        global $conf;

        //====================================================================//
        // Customer detection Already Done
        if (!is_null($this->socId)) {
            return $this->socId;
        }

        //====================================================================//
        // Standard Mode => A SocId is Given
        if (isset($receivedData["socid"]) && !empty(self::objects()->Id($receivedData["socid"]))) {
            Splash::log()->deb("Customer Id Given : Id ".self::objects()->Id($receivedData["socid"]));
            $this->socId = (int) self::objects()->Id($receivedData["socid"]);

            return $this->socId;
        }
        //====================================================================//
        // Detect ThirdParty Using Given Email
        if ($this->isAllowedEmailDetection() && isset($receivedData["email"]) && !empty($receivedData["email"])) {
            $this->socId = $this->getCustomerByEmail($receivedData["email"]);
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
    private function isAllowedGuest()
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
     * Check if Email Detyection is Active
     *
     * @return bool
     */
    private function isAllowedEmailDetection()
    {
        global $conf;
        if (!$this->isAllowedGuest() || !isset($conf->global->SPLASH_GUEST_ORDERS_EMAIL)) {
            return false;
        }

        return (bool) $conf->global->SPLASH_GUEST_ORDERS_EMAIL;
    }

    /**
     * Detect Guest Customer To Use for This Order/Invoice
     *
     * @param string $email Customer Email
     *
     * @return int Customer Id
     */
    private function getCustomerByEmail($email)
    {
        global $db;

        //====================================================================//
        // Prepare Sql Query
        $sql = 'SELECT s.rowid';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
        $sql .= ' WHERE s.entity IN ('.getEntity('societe').')';
        $sql .= " AND s.email = '".$db->escape($email)."'";

        //====================================================================//
        // Execute Query
        $resql = $db->query($sql);
        if (!$resql || (1 != $db->num_rows($resql))) {
            return 0;
        }
        $customer = $db->fetch_object($resql);
        Splash::log()->deb("Customer Detected by Email : ".$email." => Id ".$customer->rowid);

        //====================================================================//
        // Return Customer Id
        return $customer->rowid;
    }
}
