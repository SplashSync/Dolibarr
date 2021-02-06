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

namespace Splash\Local\Objects\Order;

use Splash\Core\SplashCore      as Splash;
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
    protected function buildStatusFields()
    {
        global $langs;

        //====================================================================//
        // Order Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("status")
            ->Name($langs->trans("Status"))
            ->Group(html_entity_decode($langs->trans("Status")))
            ->MicroData("http://schema.org/Order", "orderStatus")
            ->AddChoice(Status::CANCELED, $langs->trans("StatusOrderCanceled"))
            ->AddChoice(Status::DRAFT, $langs->trans("StatusOrderDraftShort"))
            ->AddChoice(Status::IN_TRANSIT, $langs->trans("StatusOrderSent"))
            ->AddChoice(Status::PROCESSING, $langs->trans("StatusOrderSentShort"))
            ->AddChoice(Status::DELIVERED, $langs->trans("StatusOrderProcessed"))
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
    protected function getStatusFields($key, $fieldName)
    {
        if ('status' != $fieldName) {
            return;
        }

        if (-1 == $this->object->statut) {
            $this->out[$fieldName] = Status::CANCELED;
        } elseif (0 == $this->object->statut) {
            $this->out[$fieldName] = Status::DRAFT;
        } elseif (1 == $this->object->statut) {
            $this->out[$fieldName] = Status::PROCESSING;
        } elseif (2 == $this->object->statut) {
            $this->out[$fieldName] = Status::IN_TRANSIT;
        } elseif (3 == $this->object->statut) {
            $this->out[$fieldName] = Status::DELIVERED;
        } else {
            $this->out[$fieldName] = Status::UNKNOWN;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function setStatusFields($fieldName, $fieldData)
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
        if (Status::isCanceled($fieldData) && (-1 != $this->object->statut)) {
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
        if (!Status::isDelivered($fieldData)) {
            //====================================================================//
            // If Previously Closed => Re-Open
            if (!$this->setStatusReOpen()) {
                return false;
            }
        }
        //====================================================================//
        // Statut Closed => Go Closed
        if (Status::isDelivered($fieldData) && (3 != $this->object->statut)) {
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
     * Set Order State as Canceled
     *
     * @return bool
     */
    private function setStatusCancel()
    {
        global $conf, $user;

        //====================================================================//
        // If Previously Closed => Set Draft
        if (3 == $this->object->statut) {
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
        if ((0 == $this->object->statut) && (1 != $this->object->valid($user, $conf->global->SPLASH_STOCK))) {
            return $this->catchDolibarrErrors();
        }
        //====================================================================//
        // Set Canceled
        if (1 != $this->object->cancel($conf->global->SPLASH_STOCK)) {
            return $this->catchDolibarrErrors();
        }
        $this->object->statut = \Commande::STATUS_CANCELED;

        return true;
    }

    /**
     * If Previously Canceled => Re-Validate
     *
     * @return bool
     */
    private function setStatusReValidate()
    {
        global $conf, $user;

        if ((-1 == $this->object->statut) && (1 != $this->object->valid($user, $conf->global->SPLASH_STOCK))) {
            return $this->catchDolibarrErrors();
        }

        return true;
    }

    /**
     * Set Order State as Draft
     *
     * @return bool
     */
    private function setStatusDraft()
    {
        global $conf, $user;

        //====================================================================//
        // If Not Draft (Validated or Closed)
        if (0 != $this->object->statut) {
            if (method_exists($this->object, "set_draft")
                    && (1 != $this->object->set_draft($user, $conf->global->SPLASH_STOCK))) {
                return $this->catchDolibarrErrors();
            }
            if (method_exists($this->object, "setDraft")
                    && (1 != $this->object->setDraft($user, $conf->global->SPLASH_STOCK))) {
                return $this->catchDolibarrErrors();
            }
        }
        $this->object->statut = \Commande::STATUS_DRAFT;

        return true;
    }

    /**
     * Set Order State as Validated
     *
     * @return bool
     */
    private function setStatusValidated()
    {
        global $conf, $user, $langs;

        if ((0 == $this->object->statut) && (1 != $this->object->valid($user, $conf->global->SPLASH_STOCK))) {
            return Splash::log()->errTrace($langs->trans($this->object->error));
        }

        return true;
    }

    /**
     * Set Order State as ReOpen
     *
     * @return bool
     */
    private function setStatusReOpen()
    {
        global $user, $langs;

        //====================================================================//
        // If Previously Closed => Re-Open
        if ((3 == $this->object->statut) && (1 != $this->object->set_reopen($user))) {
            return Splash::log()->errTrace($langs->trans($this->object->error));
        }
        $this->object->statut = \Commande::STATUS_VALIDATED;

        return true;
    }

    /**
     * Set Order State as Closed
     *
     * @return bool
     */
    private function setStatusClosed()
    {
        global $user, $langs;

        //====================================================================//
        // If Previously Validated => Close
        if ((1 == $this->object->statut) && (1 != $this->object->cloture($user))) {
            return Splash::log()->errTrace($langs->trans($this->object->error));
        }
        $this->object->statut = \Commande::STATUS_CLOSED;

        return true;
    }
}
