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

namespace Splash\Local\Objects\Invoice;

use FactureLigne;
use Splash\Core\SplashCore      as Splash;

/**
 * Dolibarr Customer Invoice Items Fields
 */
trait ItemsTrait
{
    /**
     * Create a New Line Item
     *
     * @return null|FactureLigne
     */
    protected function createItem(): ?FactureLigne
    {
        global $db;

        $item = new  FactureLigne($db);

        //====================================================================//
        // Pre-Setup of Item
        $item->fk_facture = $this->object->id;

        //====================================================================//
        // Pre-Setup of Item with Common Values & Insert
        return $this->insertItem($item) ? $item : null;
    }

    /**
     * Delete a Line Item
     *
     * @param FactureLigne $factureLigne Order FactureLigne Item
     *
     * @return bool
     */
    protected function deleteItem(FactureLigne $factureLigne): bool
    {
        //====================================================================//
        // Debug Mode => Force Allow Delete
        if (Splash::isDebugMode()) {
            //====================================================================//
            // Force Invoice Status To Draft
            $this->setInvoiceStatus(\Facture::STATUS_DRAFT);
        }
        //====================================================================//
        // Perform Line Delete
        if ($this->object->deleteline($factureLigne->id) <= 0) {
            return $this->catchDolibarrErrors();
        }

        return true;
    }
}
