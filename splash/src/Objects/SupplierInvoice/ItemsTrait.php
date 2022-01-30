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

namespace Splash\Local\Objects\SupplierInvoice;

use FactureLigne;
use OrderLine;
use Splash\Core\SplashCore      as Splash;
use SupplierInvoiceLine;

/**
 * Dolibarr Supplier Invoice Items Fields
 */
trait ItemsTrait
{
    /**
     * Create a New Line Item
     *
     * @return null|FactureLigne|OrderLine|SupplierInvoiceLine
     */
    protected function createItem()
    {
        global $db;

        $item = new SupplierInvoiceLine($db);

        //====================================================================//
        // Pre-Setup of Item
        $item->fk_facture_fourn = $this->object->id;
        $item->product_type = 0;

        //====================================================================//
        // Pre-Setup of Item with Common Values & Insert
        return $this->insertItem($item); // @phpstan-ignore-line
    }

    /**
     * Delete a Line Item
     *
     * @param SupplierInvoiceLine $factureLigne Supplier Invoice Line Item
     *
     * @return bool
     */
    protected function deleteItem(SupplierInvoiceLine $factureLigne): bool
    {
        //====================================================================//
        // Debug Mode => Force Allow Delete
        if (Splash::isDebugMode()) {
            //====================================================================//
            // Force Invoice Status To Draft
            $this->object->statut = 0;
        }
        //====================================================================//
        // Perform Line Delete
        if ($this->object->deleteline($factureLigne->id) <= 0) {
            return $this->catchDolibarrErrors();
        }

        return true;
    }
}
