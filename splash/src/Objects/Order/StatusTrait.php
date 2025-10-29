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

namespace Splash\Local\Objects\Order;

use Commande;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Local;
use Splash\Models\Objects\Order\Status;

/**
 * Dolibarr Customer Order Status Field
 */
trait StatusTrait
{
    /**
     * Build Customer Order Status Fields using FieldFactory
     *
     * @return void
     */
    protected function buildStatusFields(): void
    {
        global $langs;

        //====================================================================//
        // Order Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("status")
            ->name($langs->trans("Status"))
            ->group($langs->trans("Status"))
            ->microData("http://schema.org/Order", "orderStatus")
            ->addChoice(Status::CANCELED, $langs->trans("StatusOrderCanceled"))
            ->addChoice(Status::DRAFT, $langs->trans("StatusOrderDraftShort"))
            ->addChoice(Status::IN_TRANSIT, $langs->trans("StatusOrderSent"))
            ->addChoice(Status::PROCESSING, $langs->trans("StatusOrderSentShort"))
            ->addChoice(Status::DELIVERED, $langs->trans("StatusOrderProcessed"))
            ->isNotTested()
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
    protected function getStatusFields(string $key, string $fieldName): void
    {
        if ('status' != $fieldName) {
            return;
        }

        $rawStatus = $this->getRawStatus();
        if (-1 == $rawStatus) {
            $this->out[$fieldName] = Status::CANCELED;
        } elseif (0 == $rawStatus) {
            $this->out[$fieldName] = Status::DRAFT;
        } elseif (1 == $rawStatus) {
            $this->out[$fieldName] = Status::PROCESSING;
        } elseif (2 == $rawStatus) {
            $this->out[$fieldName] = Status::IN_TRANSIT;
        } elseif (3 == $rawStatus) {
            $this->out[$fieldName] = Status::DELIVERED;
        } else {
            $this->out[$fieldName] = Status::UNKNOWN;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function setStatusFields(string $fieldName, ?string $fieldData): bool
    {
        global $conf, $langs;

        if ('status' != $fieldName) {
            return true;
        }
        unset($this->in[$fieldName]);

        //====================================================================//
        // Safety Check
        if (empty($this->object->id) || empty($fieldData)) {
            return false;
        }
        //====================================================================//
        // Verify Stock Is Defined if Required
        // If stock is incremented on validate order, we must increment it
        if (!empty($conf->stock->enabled) && (1 == $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER)) {
            if (empty($conf->global->SPLASH_STOCK)) {
                return Splash::log()->errTrace($langs->trans("WarehouseSourceNotDefined"));
            }
        }

        //====================================================================//
        // Statut Canceled
        if (Status::isCanceled($fieldData) && (Commande::STATUS_CANCELED != $this->getRawStatus())) {
            return $this->setStatusCancel();
        }
        //====================================================================//
        // If Previously Canceled => Re-Validate
        if (!$this->setStatusReValidate()) {
            return false;
        }
        //====================================================================//
        // Statut Draft
        if (Status::isDraft($fieldData)) {
            return $this->setStatusDraft();
        }
        //====================================================================//
        // Statut Validated || Closed => Go Valid if Draft
        if (!$this->setStatusValidated()) {
            return false;
        }
        //====================================================================//
        // Statut Not Closed but Validated Only => ReOpen
        if (!Status::isDelivered($fieldData) && !Status::isShipped($fieldData)) {
            //====================================================================//
            // If Previously Closed => Re-Open
            if (!$this->setStatusReOpen()) {
                return false;
            }
        }
        //====================================================================//
        // Statut Shipped but Not Delivered
        if (Status::isShipped($fieldData) && (Commande::STATUS_VALIDATED == $this->getRawStatus())) {
            //====================================================================//
            // Set Shipped
            $this->setRawStatus(Commande::STATUS_SHIPMENTONPROCESS);
            $this->needUpdate();
        }
        //====================================================================//
        // Statut Closed => Go Closed
        if (Status::isDelivered($fieldData) && (Commande::STATUS_CLOSED != $this->getRawStatus())) {
            //====================================================================//
            // If Previously Validated => Close
            if (!$this->setStatusClosed()) {
                return false;
            }
        }
        //====================================================================//
        // Redo Billed flag Update if Impacted by Status Change
        $this->updateBilledFlag();

        return true;
    }

    /**
     * Get Order Raw Status Code
     */
    protected function getRawStatus(): ?int
    {
        //====================================================================//
        // Since Dolibarr V21 => use $status, $statut is deprecated
        if (Local::dolVersionCmp("21.0.0") >= 0) {
            if (property_exists($this->object, "status")) {
                assert(is_scalar($this->object->status));

                return $this->object->status;
            }
        }
        if (property_exists($this->object, "statut")) {
            return $this->object->statut;
        }

        return Commande::STATUS_DRAFT;
    }

    /**
     * Set Order State as Canceled
     *
     * @return bool
     */
    private function setStatusCancel(): bool
    {
        global $conf, $user;

        //====================================================================//
        // If Previously Closed => Set Draft
        if (3 == $this->getRawStatus()) {
            if (method_exists($this->object, "set_draft")
                    && (1 != $this->object->set_draft($user, $conf->global->SPLASH_STOCK))) {
                return $this->catchDolibarrErrors();
            }
            if (method_exists($this->object, "setDraft")
                    && (1 != $this->object->setDraft($user, $conf->global->SPLASH_STOCK))) {
                return $this->catchDolibarrErrors();
            }
        }
        //====================================================================//
        // If Previously Draft => Valid
        if ((0 == $this->getRawStatus()) && (1 != $this->object->valid($user, $conf->global->SPLASH_STOCK))) {
            return $this->catchDolibarrErrors();
        }
        //====================================================================//
        // Set Canceled
        if (1 != $this->object->cancel($conf->global->SPLASH_STOCK)) {
            return $this->catchDolibarrErrors();
        }
        $this->setRawStatus(Commande::STATUS_CANCELED);

        return true;
    }

    /**
     * If Previously Canceled => Re-Validate
     *
     * @return bool
     */
    private function setStatusReValidate(): bool
    {
        global $conf, $user;

        //====================================================================//
        // Must Be Canceled
        if (-1 != $this->getRawStatus()) {
            return true;
        }
        //====================================================================//
        // Do Validate
        if (1 != $this->object->valid($user, $conf->global->SPLASH_STOCK)) {
            return $this->catchDolibarrErrors();
        }
        //====================================================================//
        // Mark Main Document Download Url as Updated
        $this->setDownloadUrlsUpdated();

        return true;
    }

    /**
     * Set Order State as Draft
     *
     * @return bool
     */
    private function setStatusDraft(): bool
    {
        global $conf, $user;

        //====================================================================//
        // If Not Draft (Validated or Closed)
        if (0 != $this->getRawStatus()) {
            if (method_exists($this->object, "set_draft")
                    && (1 != $this->object->set_draft($user, $conf->global->SPLASH_STOCK))) {
                return $this->catchDolibarrErrors();
            }
            if (method_exists($this->object, "setDraft")
                    && (1 != $this->object->setDraft($user, $conf->global->SPLASH_STOCK))) {
                return $this->catchDolibarrErrors();
            }
            $this->setRawStatus(Commande::STATUS_DRAFT);
        }

        return true;
    }

    /**
     * Set Order State as Validated
     *
     * @return bool
     */
    private function setStatusValidated(): bool
    {
        global $conf, $user, $langs;

        //====================================================================//
        // Must Be Draft
        if (0 != $this->getRawStatus()) {
            return true;
        }
        //====================================================================//
        // Do Validate
        if (1 != $this->object->valid($user, $conf->global->SPLASH_STOCK)) {
            return Splash::log()->errTrace($langs->trans($this->object->error));
        }
        //====================================================================//
        // Mark Main Document Download Url as Updated
        $this->setDownloadUrlsUpdated();

        return true;
    }

    /**
     * Set Order State as ReOpen
     *
     * @return bool
     */
    private function setStatusReOpen(): bool
    {
        global $user, $langs;

        //====================================================================//
        // If Previously Closed => Re-Open
        if ((Commande::STATUS_CLOSED == $this->getRawStatus())) {
            if (1 != $this->object->set_reopen($user)) {
                return Splash::log()->errTrace($langs->trans($this->object->error));
            }
            $this->setRawStatus(Commande::STATUS_VALIDATED);
        }

        return true;
    }

    /**
     * Set Order State as Closed
     *
     * @return bool
     */
    private function setStatusClosed(): bool
    {
        global $user, $langs;

        //====================================================================//
        // If Previously Validated => Close
        $validatedStatuses = array(Commande::STATUS_VALIDATED, Commande::STATUS_SHIPMENTONPROCESS);
        if (in_array((int) $this->getRawStatus(), $validatedStatuses, false)) {
            if (1 != $this->object->cloture($user)) {
                return Splash::log()->errTrace($langs->trans($this->object->error));
            }
            $this->setRawStatus(Commande::STATUS_CLOSED);
        }

        return true;
    }

    /**
     * Set Order Raw Status Code
     */
    private function setRawStatus(int $rawStatus): void
    {
        //====================================================================//
        // Since Dolibarr V21 => use $status, $statut is deprecated
        if (property_exists($this->object, "statut")) {
            $this->object->statut = $rawStatus;
        }
        if (property_exists($this->object, "status")) {
            $this->object->status = $rawStatus;
        }
    }
}
