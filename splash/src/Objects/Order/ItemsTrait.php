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

use FactureLigne;
use OrderLine;

/**
 * Dolibarr Customer Orders Items Fields
 */
trait ItemsTrait
{
    /**
     * Create a New Line Item
     *
     * @return null|FactureLigne|OrderLine
     */
    protected function createItem()
    {
        global $db;

        $item = new  OrderLine($db);

        //====================================================================//
        // Pre-Setup of Item
        $item->fk_commande = $this->object->id;

        //====================================================================//
        // Pre-Setup of Item with Common Values & Insert
        return $this->insertItem($item);
    }

    /**
     * Delete a Line Item
     *
     * @param OrderLine $orderLine Order OrderLine Item
     *
     * @return bool
     */
    protected function deleteItem($orderLine)
    {
        global $user;
        //====================================================================//
        // Force Order Status To Draft
        $this->object->statut = 0;
        $this->object->brouillon = 1;
        //====================================================================//
        // Perform Line Delete
        if ($this->object->deleteline($user, $orderLine->id) <= 0) {
            return $this->catchDolibarrErrors();
        }

        return true;
    }
}
