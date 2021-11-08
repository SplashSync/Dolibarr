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

use Exception;
use FactureFournisseur;
use PaiementFourn;
use Splash\Client\Splash;
use SupplierInvoiceLine;

/**
 * Invoices Dolibarr Trigger trait
 */
trait TriggersTrait
{
    /**
     * Prepare Object Commit for Order
     *
     * @param string $action Code de l'évènement
     * @param object $object Objet concerne
     *
     * @throws Exception
     *
     * @return bool Commit is required
     */
    protected function doSupplierInvoiceCommit($action, $object)
    {
        //====================================================================//
        // Check if Commit is Required
        if (!$this->isSupplierInvoiceCommitRequired($action)) {
            return false;
        }
        //====================================================================//
        // Store Global Action Parameters
        $this->setSupplierInvoiceObjectId($object);
        $this->setSupplierInvoiceObjectType();
        //====================================================================//
        // Safety Check => ObjectType is Active
        if (!in_array($this->objectType, Splash::objects(), true)) {
            return false;
        }
        $this->setSupplierInvoiceParameters($action);

        if (empty($this->objectId)) {
            return false;
        }

        return true;
    }

    /**
     * Check if Commit is Required
     *
     * @param string $action Code de l'évènement
     *
     * @return bool
     */
    private function isSupplierInvoiceCommitRequired(string $action): bool
    {
        return in_array($action, array(
            // Supplier Invoice Actions
            'BILL_SUPPLIER_CREATE',
            'BILL_SUPPLIER_CLONE',
            'BILL_SUPPLIER_MODIFY',
            'BILL_SUPPLIER_UPDATE',
            'BILL_SUPPLIER_VALIDATE',
            'BILL_SUPPLIER_UNVALIDATE',
            'BILL_SUPPLIER_CANCEL',
            'BILL_SUPPLIER_DELETE',
            'BILL_SUPPLIER_PAYED',
            'BILL_SUPPLIER_UNPAYED',
            // Supplier Invoice Lines Actions
            'LINEBILL_SUPPLIER_CREATE',
            'LINEBILL_SUPPLIER_UPDATE',
            'LINEBILL_SUPPLIER_DELETE',
            // Supplier Invoice Payments Actions
            'PAYMENT_SUPPLIER_CREATE',
            'PAYMENT_SUPPLIER_DELETE',
        ), true);
    }

    /**
     * Identify Order Id from Given Object
     *
     * @param object $object Objet concerne
     *
     * @return void
     */
    private function setSupplierInvoiceObjectId(object $object): void
    {
        //====================================================================//
        // Identify Invoice Id from Invoice Line
        if ($object instanceof SupplierInvoiceLine) {
            $this->objectId = !empty($object->fk_facture_fourn)
                ? (string) $object->fk_facture_fourn
                : (string) $object->oldline->fk_facture_fourn;

            return;
        }
        //====================================================================//
        // Identify Invoice Id from Payment Line
        if ($object instanceof PaiementFourn) {
            //====================================================================//
            // Read Paiement Object Invoices Amounts
            $amounts = self::getSupplierPaiementAmounts($object->id);
            //====================================================================//
            // Create Impacted Invoices Ids Array
            $this->objectId = array_keys($amounts);

            return;
        }
        //====================================================================//
        // Invoice Given
        if ($object instanceof FactureFournisseur) {
            $this->objectId = (string) $object->id;
        }
    }

    /**
     * Identify Splash Object type from Given Object
     *
     * @return void
     */
    private function setSupplierInvoiceObjectType(): void
    {
        $this->objectType = "SupplierInvoice";
    }

    /**
     * Prepare Object Commit for Product
     *
     * @param string $action Code de l'évènement
     *
     * @throws Exception
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setSupplierInvoiceParameters(string $action): void
    {
        switch ($action) {
            case 'BILL_SUPPLIER_CREATE':
                $this->action = SPL_A_CREATE;
                $this->comment = "Supplier Invoice Created on Dolibarr";

                break;
            case 'BILL_SUPPLIER_MODIFY':
            case 'BILL_SUPPLIER_UPDATE':
            case 'BILL_SUPPLIER_CLONE':
            case 'BILL_SUPPLIER_VALIDATE':
            case 'BILL_SUPPLIER_UNVALIDATE':
            case 'BILL_SUPPLIER_CANCEL':
            case 'BILL_SUPPLIER_PAYED':
            case 'BILL_SUPPLIER_UNPAYED':
            case 'PAYMENT_SUPPLIER_CREATE':
            case 'PAYMENT_SUPPLIER_DELETE':
            case 'LINEBILL_SUPPLIER_CREATE':
            case 'LINEBILL_SUPPLIER_UPDATE':
            case 'LINEBILL_SUPPLIER_DELETE':
                $this->action = (Splash::object((string) $this->objectType)->isLocked() ? SPL_A_CREATE : SPL_A_UPDATE);
                $this->comment = "Supplier Invoice Updated on Dolibarr";

                break;
            case 'BILL_SUPPLIER_DELETE':
                $this->action = SPL_A_DELETE;
                $this->comment = "Supplier Invoice Deleted on Dolibarr";

                break;
        }
    }

    /**
     * Fetch List of Invoices Payments Amounts
     *
     * @param int $paiementId Payment Object Id
     *
     * @return array List Of Payment Object Amounts
     */
    private static function getSupplierPaiementAmounts(int $paiementId)
    {
        global $db;
        //====================================================================//
        // Init Result Array
        $amounts = array();
        //====================================================================//
        // SELECT SQL Request
        $sql = 'SELECT fk_facturefourn, amount';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn';
        $sql .= ' WHERE fk_paiementfourn = '.$paiementId;
        $resql = $db->query($sql);
        //====================================================================//
        // SQL Error
        if (!$resql) {
            Splash::log()->errTrace($db->error());

            return $amounts;
        }
        //====================================================================//
        // Populate Object
        for ($i = 0; $i < $db->num_rows($resql); $i++) {
            $obj = $db->fetch_object($resql);
            $amounts[$obj->fk_facturefourn] = $obj->amount;
        }
        $db->free($resql);

        return $amounts;
    }
}
