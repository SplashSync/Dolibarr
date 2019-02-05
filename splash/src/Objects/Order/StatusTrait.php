<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
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

/**
 * Dolibarr Customer Order Status Field
 */
trait StatusTrait
{
    /**
     * Build Customer Order Status Fields using FieldFactory
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
            ->AddChoice("OrderCanceled", $langs->trans("StatusOrderCanceled"))
            ->AddChoice("OrderDraft", $langs->trans("StatusOrderDraftShort"))
            ->AddChoice("OrderInTransit", $langs->trans("StatusOrderSent"))
            ->AddChoice("OrderProcessing", $langs->trans("StatusOrderSentShort"))
            ->AddChoice("OrderDelivered", $langs->trans("StatusOrderProcessed"))
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
            $this->out[$fieldName]  = "OrderCanceled";
        } elseif (0 == $this->object->statut) {
            $this->out[$fieldName]  = "OrderDraft";
        } elseif (1 == $this->object->statut) {
            $this->out[$fieldName]  = "OrderProcessing";
        } elseif (2 == $this->object->statut) {
            $this->out[$fieldName]  = "OrderInTransit";
        } elseif (3 == $this->object->statut) {
            $this->out[$fieldName]  = "OrderDelivered";
        } else {
            $this->out[$fieldName]  = "Unknown";
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
    private function setStatusFields($fieldName, $fieldData)
    {
        global $conf, $langs, $user;
        
        if ('status' != $fieldName) {
            return true;
        }
        unset($this->in[$fieldName]);

        //====================================================================//
        // Safety Check
        if (empty($this->object->id)) {
            return false;
        }
        //====================================================================//
        // Verify Stock Is Defined if Required
        // If stock is incremented on validate order, we must increment it
        if (!empty($conf->stock->enabled) && (1 == $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER)) {
            if (empty($conf->global->SPLASH_STOCK)) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    $langs->trans("WarehouseSourceNotDefined")
                );
            }
        }
        //====================================================================//
        // Statut Canceled
        //====================================================================//
        // Statut Canceled
        if (("OrderCanceled" == $fieldData) && (-1 != $this->object->statut)) {
            //====================================================================//
            // If Previously Closed => Set Draft
            if ((3 == $this->object->statut)
                    && (1 != $this->object->set_draft($user, $conf->global->SPLASH_STOCK))) {
                return $this->catchDolibarrErrors();
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
        //====================================================================//
        // If Previously Canceled => Re-Validate
        if ((-1 == $this->object->statut) && (1 != $this->object->valid($user, $conf->global->SPLASH_STOCK))) {
            return $this->catchDolibarrErrors();
        }
        //====================================================================//
        // Statut Draft
        if ("OrderDraft" == $fieldData) {
            //====================================================================//
            // If Not Draft (Validated or Closed)
            if ((0 != $this->object->statut) && 1 != $this->object->set_draft($user, $conf->global->SPLASH_STOCK)) {
                return $this->catchDolibarrErrors();
            }
            $this->object->statut = \Commande::STATUS_DRAFT;

            return true;
        }
        //====================================================================//
        // Statut Validated || Closed => Go Valid if Draft
        if ((0 == $this->object->statut) && (1 != $this->object->valid($user, $conf->global->SPLASH_STOCK))) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, "Set Validated", $langs->trans($this->object->error));
        }
        //====================================================================//
        // Statut Not Closed but Validated Only => ReOpen
        if ("OrderDelivered" != $fieldData) {
            //====================================================================//
            // If Previously Closed => Re-Open
            if ((3 == $this->object->statut) && (1 != $this->object->set_reopen($user))) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, "Re-Open", $langs->trans($this->object->error));
            }
            $this->object->statut = \Commande::STATUS_VALIDATED;
        }
        //====================================================================//
        // Statut Closed => Go Closed
        if (("OrderDelivered" == $fieldData) && (3 != $this->object->statut)) {
            //====================================================================//
            // If Previously Validated => Close
            if ((1 == $this->object->statut) && (1 != $this->object->cloture($user))) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, "Set Closed", $langs->trans($this->object->error));
            }
            $this->object->statut = \Commande::STATUS_CLOSED;
        }
        //====================================================================//
        // Redo Billed flag Update if Impacted by Status Change
        $this->updateBilledFlag();

        return true;
    }
}
