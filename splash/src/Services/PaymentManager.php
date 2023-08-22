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

namespace Splash\Local\Services;

use Facture;
use Paiement;
use Splash\Core\SplashCore as Splash;

/**
 * Perform Complex Operations on Invoice Payments
 */
class PaymentManager
{
    /**
     * Fetch List of Invoices Payments Amounts
     *
     * @param Paiement $payment Payment Object
     *
     * @return array List Of Payment Object Amounts
     */
    public static function getPaymentAmounts(Paiement $payment): array
    {
        global $db;
        //====================================================================//
        // Init Result Array
        $amounts = array();
        //====================================================================//
        // SELECT SQL Request
        $sql = 'SELECT fk_facture, amount';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture';
        $sql .= ' WHERE fk_paiement = '.$payment->id;
        $resql = $db->query($sql);
        //====================================================================//
        // SQL Error
        if (!$resql) {
            \Splash\Client\Splash::log()->errTrace($db->error());

            return $amounts;
        }
        //====================================================================//
        // Populate Object
        for ($i = 0; $i < $db->num_rows($resql); $i++) {
            $obj = $db->fetch_object($resql);
            $amounts[$obj->fk_facture] = $obj->amount;
        }
        $db->free($resql);

        return $amounts;
    }

    /**
     * Collect Total Amounts for All Invoices Attached to a Payment
     * Return Null if One of the Invoices has Multiple Payments
     *
     * @param Paiement $payment Payment Object
     *
     * @return null|array
     */
    public static function getPaymentInvoicesTotals(Paiement $payment): ?array
    {
        global $db;

        //====================================================================//
        // Load All Invoices Connected to the Payment
        $billArray = $payment->getBillsArray();
        $amountsArray = $payment->getAmountsArray();
        if (!is_array($billArray) || !is_array($amountsArray)) {
            return null;
        }
        //====================================================================//
        // Walk on All Connected Invoices
        foreach ($billArray as $invoiceId) {
            $invoice = new Facture($db);
            $invoice->fetch($invoiceId);
            //====================================================================//
            // Ensure All Connected Invoices have only a Single Payment
            if (1 != count($invoice->getListOfPayments())) {
                return null;
            }
            $amountsArray[$invoiceId] = array(
                "charged" => $amountsArray[$invoiceId],
                "invoiced" => $invoice->total_ttc,
                "invoice" => $invoice,
            );
        }

        return $amountsArray;
    }

    /**
     * Search for Similar Payment based on Inputs
     *
     * @param string $date
     * @param string $number
     * @param float  $amount
     * @param int    $methodId
     *
     * @return null|Paiement
     */
    public static function searchForSimilarPayment(
        string $date,
        string $number,
        float $amount,
        int $methodId
    ): ?Paiement {
        global $db;

        if (!class_exists("Paiement")) {
            require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
        }

        /** @var Paiement $payment */
        $payment = new Paiement($db);
        //====================================================================//
        // Search for Similar payment in Database
        $sql = 'SELECT p.rowid as id, p.ref, p.num_paiement as number,';
        $sql .= ' p.datep as date, p.amount, p.statut, p.fk_paiement';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'paiement as p';
        $sql .= ' WHERE p.entity IN ('.getEntity('payment').')';
        $sql .= ' AND p.fk_paiement = '.((int) $methodId);
        $sql .= " AND p.num_paiement = '".$number."'";
        $sql .= " AND p.datep = '".$db->idate($date)."'";
        $sql .= " AND p.amount = '".((float) $amount)."'";
        $resql = $db->query($sql);
        if (!$resql || (1 != $db->num_rows($resql))) {
            return null;
        }
        //====================================================================//
        // Fetch Payment Object
        if (1 != $payment->fetch((int) $db->fetch_object($resql)->id)) {
            Splash::log()->err($db->lasterror());

            return null;
        }

        //====================================================================//
        // Return Identified Payment
        return $payment;
    }

    /**
     * Search for Similar Payment based on Inputs
     *
     * @param Paiement $payment Payment Object
     * @param Facture  $invoice
     * @param float    $amount
     *
     * @return bool
     */
    public static function updatePaymentInvoiceAmount(Paiement $payment, Facture $invoice, float $amount): bool
    {
        global $db;

        $sql = "UPDATE ".MAIN_DB_PREFIX."paiement_facture";
        $sql .= " SET amount = ".$amount;
        $sql .= " WHERE fk_paiement = ".$payment->id;
        $sql .= " AND fk_facture = ".$invoice->id;
        $resql = $db->query($sql);
        if (!$resql) {
            return Splash::log()->err("Unable to Update Invoice Payment Amount: ".$db->lasterror());
        }

        return true;
    }

    /**
     * Search for Similar Payment based on Inputs
     *
     * @param Paiement $payment Payment Object
     * @param Facture  $invoice
     * @param float    $amount
     *
     * @return bool
     */
    public static function addPaymentInvoiceAmount(Paiement $payment, Facture $invoice, float $amount): bool
    {
        global $db;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount, multicurrency_amount)";
        $sql .= " VALUES (".((int) $invoice->id).", ".((int) $payment->id).",";
        $sql .= " ".((float) $amount).", ".((float) $amount).")";
        $resql = $db->query($sql);
        if (!$resql) {
            return Splash::log()->err("Unable to Add Invoice Payment Amount: ".$db->lasterror());
        }

        return true;
    }
}
