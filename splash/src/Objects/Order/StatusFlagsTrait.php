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
use Splash\Local\Services\ShippingMethods;

/**
 * Dolibarr Customer Orders Status Flags Fields
 */
trait StatusFlagsTrait
{
    /**
     * @var null|bool
     */
    private ?bool $updateBilled = null;

    /**
     * Build Fields using FieldFactory
     */
    protected function buildStatusFlagsFields(): void
    {
        global $langs;

        $groupName = $langs->trans("Status");

        //====================================================================//
        // Is Draft
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isdraft")
            ->group($groupName)
            ->name($langs->trans("Order")." : ".$langs->trans("Draft"))
            ->microData("http://schema.org/OrderStatus", "OrderDraft")
            ->association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Canceled
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("iscanceled")
            ->group($groupName)
            ->name($langs->trans("Order")." : ".$langs->trans("Canceled"))
            ->microData("http://schema.org/OrderStatus", "OrderCancelled")
            ->association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isvalidated")
            ->group($groupName)
            ->name($langs->trans("Order")." : ".$langs->trans("Validated"))
            ->microData("http://schema.org/OrderStatus", "OrderProcessing")
            ->association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Closed
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isclosed")
            ->name($langs->trans("Order")." : ".$langs->trans("Closed"))
            ->group($groupName)
            ->microData("http://schema.org/OrderStatus", "OrderDelivered")
            ->association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Closed with Local Delivery
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("forceDelivered")
            ->name("Force Delivered")
            ->group($groupName)
            ->microData("http://schema.org/OrderStatus", "ForceDelivered")
            ->isReadOnly()
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
    protected function getStatusFlagsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//

            case 'isdraft':
                $this->out[$fieldName] = (Commande::STATUS_DRAFT == $this->getRawStatus());

                break;
            case 'iscanceled':
                $this->out[$fieldName] = (Commande::STATUS_CANCELED == $this->getRawStatus());

                break;
            case 'isvalidated':
                $this->out[$fieldName] = (Commande::STATUS_VALIDATED == $this->getRawStatus());

                break;
            case 'isclosed':
                $this->out[$fieldName] = (Commande::STATUS_CLOSED == $this->getRawStatus());

                break;
            case 'forceDelivered':
                $this->out[$fieldName] = (Commande::STATUS_CLOSED == $this->getRawStatus())
                    && ShippingMethods::isMySocMethod($this->object->shipping_method_id)
                ;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }
}
