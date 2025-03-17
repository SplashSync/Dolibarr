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
use Exception;
use OrderLine;
use Splash\Client\Splash;

/**
 * Order Dolibarr Trigger trait
 */
trait TriggersTrait
{
    /**
     * Prepare Object Commit for Order
     *
     * @param string $action Event Code
     * @param object $object Impacted Objet
     *
     * @throws Exception
     *
     * @return bool Commit is required
     */
    protected function doOrderCommit(string $action, object $object): bool
    {
        //====================================================================//
        // Check if Commit is Required
        if (!$this->isOrderCommitRequired($action)) {
            return false;
        }

        //====================================================================//
        // Store Global Action Parameters
        $this->setOrderObjectId($object);
        $this->setOrderParameters($action);

        return true;
    }

    /**
     * Check if Commit is Required
     *
     * @param string $action Event Code
     *
     * @return bool
     */
    private function isOrderCommitRequired(string $action): bool
    {
        //====================================================================//
        // Filter Triggered Actions
        return in_array($action, array(
            // Order Actions
            'ORDER_CREATE',
            'ORDER_VALIDATE',
            'ORDER_MODIFY',
            'COMMANDE_MODIFY',
            'ORDER_UPDATE',
            'ORDER_DELETE',
            'ORDER_CLOSE',
            'ORDER_REOPEN',
            'ORDER_CLASSIFY_BILLED',
            'ORDER_CANCEL',
            // Order Line Actions
            'LINEORDER_INSERT',
            'LINEORDER_MODIFY',
            'LINEORDER_UPDATE',
            'LINEORDER_DELETE',
            // Order Contacts Actions
            'COMMANDE_ADD_CONTACT',
            'COMMANDE_DELETE_CONTACT',
        ), true);
    }

    /**
     * Identify Order Id from Given Object
     *
     * @param object $object Impacted Objet
     *
     * @return void
     */
    private function setOrderObjectId(object $object): void
    {
        //====================================================================//
        // Identify Order Id from Order Line
        if ($object instanceof OrderLine) {
            $this->objectId = !empty($object->fk_commande)
                ? (string) $object->fk_commande
                : (string) $object->oldline->fk_commande;

            return;
        }
        //====================================================================//
        // Identify Order Id
        if ($object instanceof Commande) {
            $this->objectId = (string) $object->id;
        }
    }

    /**
     * Prepare Object Commit for Product
     *
     * @param string $action Event Code
     *
     * @throws Exception
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setOrderParameters(string $action): void
    {
        //====================================================================//
        // Store Global Action Parameters
        $this->objectType = "Order";

        switch ($action) {
            case 'ORDER_CREATE':
                $this->action = SPL_A_CREATE;
                $this->comment = "Order Created on Dolibarr";

                break;
            case 'ORDER_VALIDATE':
            case 'ORDER_MODIFY':
            case 'ORDER_UPDATE':
            case 'COMMANDE_MODIFY':
            case 'ORDER_CLOSE':
            case 'ORDER_REOPEN':
            case 'ORDER_CLASSIFY_BILLED':
            case 'ORDER_CANCEL':
            case 'LINEORDER_INSERT':
            case 'LINEORDER_UPDATE':
            case 'LINEORDER_MODIFY':
            case 'LINEORDER_DELETE':
            case 'COMMANDE_ADD_CONTACT':
            case 'COMMANDE_DELETE_CONTACT':
                $this->action = (Splash::object("Order")->isLocked() ?   SPL_A_CREATE : SPL_A_UPDATE);
                $this->comment = "Order Updated on Dolibarr";

                break;
            case 'ORDER_DELETE':
                $this->action = SPL_A_DELETE;
                $this->comment = "Order Deleted on Dolibarr";

                break;
        }
    }
}
