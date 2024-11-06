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

use DateTime;
use Exception;
use Paiement;
use PaiementFourn;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Local\Services\BankAccounts;
use Splash\Local\Services\PaymentManager;
use Splash\Local\Services\PaymentMethods;

/**
 * Dolibarr Customer/Supplier Invoice Payments Fields
 */
trait PaymentsTrait
{
    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var array
     */
    protected array $payments = array();

    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildPaymentsFields(): void
    {
        global $langs;

        $listName = "" ;

        //====================================================================//
        // Payment Line Payment Method
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("mode")
            ->inList("payments")
            ->name($listName.$langs->trans("PaymentMode"))
            ->microData("http://schema.org/Invoice", "PaymentMethod")
            ->addChoices(PaymentMethods::getChoices())
            ->association("date@payments", "mode@payments", "amount@payments")
        ;
        //====================================================================//
        // Payment Line Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier("date")
            ->inList("payments")
            ->name($listName.$langs->trans("Date"))
            ->microData("http://schema.org/PaymentChargeSpecification", "validFrom")
            ->association("date@payments", "mode@payments", "amount@payments")
        ;
        //====================================================================//
        // Payment Line Payment Identifier
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("number")
            ->inList("payments")
            ->name($listName.$langs->trans('Numero'))
            ->microData("http://schema.org/Invoice", "paymentMethodId")
            ->association("date@payments", "mode@payments", "amount@payments")
        ;
        //====================================================================//
        // Payment Line Amount
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("amount")
            ->inList("payments")
            ->name($listName.$langs->trans("PaymentAmount"))
            ->microData("http://schema.org/PaymentChargeSpecification", "price")
            ->association("date@payments", "mode@payments", "amount@payments")
        ;
    }

    /**
     * Fetch Invoice Payments List (Done after Load)
     *
     * @param int  $invoiceId
     * @param bool $isSupplier
     *
     * @return bool
     */
    protected function loadPayments(int $invoiceId, bool $isSupplier = false): bool
    {
        global $db;

        //====================================================================//
        // Detect Supplier Invoices Mode
        $isSupplier |= is_a($this, Local::CLASS_SUPPLIER_INVOICE);
        //====================================================================//
        // Prepare SQL Request
        // Payments already done (from payment on this invoice)
        $sql = 'SELECT p.datep as date, p.num_paiement as number, p.rowid as id, p.fk_bank,';
        $sql .= ' c.code as code, c.libelle as payment_label,';
        $sql .= ' pf.amount as amount,';
        $sql .= ' ba.rowid as baid, ba.ref, ba.label';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'c_paiement as c, ' ;
        $sql .= $isSupplier
            ? MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf, '.MAIN_DB_PREFIX.'paiementfourn as p'
            : MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p'
        ;
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
        $sql .= $isSupplier
            ? ' WHERE pf.fk_facturefourn = '.$invoiceId.' AND p.fk_paiement = c.id AND pf.fk_paiementfourn = p.rowid'
            : ' WHERE pf.fk_facture = '.$invoiceId.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid'
        ;
        $sql .= ' ORDER BY p.rowid';

        //====================================================================//
        // Execute SQL Request
        $result = $db->query($sql);
        if (!$result) {
            $this->catchDolibarrErrors();

            return false;
        }
        //====================================================================//
        // Count Results
        $count = $db->num_rows($result);
        if (0 == $count) {
            return true;
        }
        //====================================================================//
        // Fetch Results
        $index = 0;
        while ($index < $count) {
            $this->payments[$index] = $db->fetch_object($result);
            //====================================================================//
            // Detect Payment Method Type from Default Payment "known" methods
            $this->payments[$index]->method = PaymentMethods::getSplashCode($this->payments[$index]->code);
            $index ++;
        }
        $db->free($result);

        return true;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getPaymentsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "payments", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Fill List with Data
        foreach ($this->payments as $index => $paymentLine) {
            //====================================================================//
            // READ Fields
            switch ($fieldName) {
                //====================================================================//
                // Payment Line - Payment Mode
                case 'mode@payments':
                    $value = $paymentLine->method;

                    break;
                    //====================================================================//
                    // Payment Line - Payment Date
                case 'date@payments':
                    $value = !empty($paymentLine->date)?dol_print_date($paymentLine->date, '%Y-%m-%d'):null;

                    break;
                    //====================================================================//
                    // Payment Line - Payment Identification Number
                case 'number@payments':
                    $value = $paymentLine->number;

                    break;
                    //====================================================================//
                    // Payment Line - Payment Amount
                case 'amount@payments':
                    $value = self::parsePrice($paymentLine->amount);

                    break;
                default:
                    return;
            }
            //====================================================================//
            // Insert Data in List
            self::lists()->insert($this->out, "payments", $fieldName, $index, $value);
        }
        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string     $fieldName Field Identifier / Name
     * @param null|array $fieldData Field Data
     *
     * @return void
     */
    protected function setPaymentLineFields(string $fieldName, ?array $fieldData): void
    {
        global $user;
        //====================================================================//
        // Safety Check
        if ("payments" !== $fieldName) {
            return;
        }
        //====================================================================//
        // Verify Lines List & Update if Needed
        $firstMethodId = null;
        foreach ($fieldData ?? array() as $lineData) {
            $this->setPaymentLineData($lineData);
            //====================================================================//
            // Detect First Valid Payment Method
            $firstMethodId = $firstMethodId ?: PaymentMethods::getDoliId($lineData["mode"] ?? "");
        }
        //====================================================================//
        // Setup Invoice Payment Method
        if (!Splash::isTravisMode()) {
            $this->setSimple('mode_reglement_id', $firstMethodId);
        }
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->payments as $paymentData) {
            //====================================================================//
            // Fetch Payment Line Entity
            $payment = $this->newPayment();
            $payment->fetch($paymentData->id);
            //====================================================================//
            // Check If Payment impact another Bill
            /** @var array|int $billArray */
            $billArray = $payment->getBillsArray();
            if (is_array($billArray) && (count($billArray) > 1)) {
                continue;
            }
            //====================================================================//
            // Prepare Args
            $arg1 = (Local::dolVersionCmp("20.0.0") > 0) ? $user : 0;
            //====================================================================//
            // Try to delete Payment Line
            $payment->delete($arg1);
        }

        unset($this->in[$fieldName]);
    }

    /**
     * Delete All Invoices Payments (Only Used for Debug in PhpUnit)
     *
     * @param int $invoiceId Invoice Object ID
     *
     * @return void
     */
    protected function clearPayments(int $invoiceId): void
    {
        global $user;
        //====================================================================//
        // Load Invoice Payments
        $this->loadPayments($invoiceId);
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->payments as $paymentData) {
            //====================================================================//
            // Fetch Payment Line Entity
            $payment = $this->newPayment();
            $payment->fetch($paymentData->id);
            //====================================================================//
            // Check If Payment impact another Bill
            /** @var array|int $billArray */
            $billArray = $payment->getBillsArray();
            if (is_array($billArray) && count($billArray) > 1) {
                continue;
            }
            //====================================================================//
            // Prepare Args
            $arg1 = (Local::dolVersionCmp("20.0.0") > 0) ? $user : 0;
            //====================================================================//
            // Try to delete Payment Line
            if ($payment->delete($arg1) <= 0) {
                $this->catchDolibarrErrors($payment);

                Splash::log()->errTrace("Unable to Delete Invoice Payment (".$paymentData->id.")");
            }
        }
    }

    /**
     * Update a Payment line Data
     *
     * @param array $lineData Line Data Array
     *
     * @return bool
     */
    private function setPaymentLineData(array $lineData): bool
    {
        //====================================================================//
        // Read Next Payment Line
        $paymentData = array_shift($this->payments);

        //====================================================================//
        // Existing Line
        //
        // => Update Date & Payment reference (Number)
        // => If Amount is Different, delete Payment & Re-Create
        // => If Payment method is different => Do nothing!!
        //====================================================================//
        if ($paymentData) {
            //====================================================================//
            // Update Payment Infos, If No Need to Re-Create => EXIT
            if (!$this->updatePaymentItem($paymentData->id, $lineData)) {
                return false;
            }
        }

        //====================================================================//
        // Try Multi Invoice Payments Identification
        //====================================================================//
        if ($this->identifyExistingPayment((array) $lineData)) {
            return true;
        }

        //====================================================================//
        // Create New Line
        //====================================================================//
        return $this->createPaymentItem($lineData);
    }

    /**
     * Update an Exiting Payment
     *
     * @param int   $paymentId Payment Item ID
     * @param array $lineData  Line Data Array
     *
     * @return bool Re-Create Payment Item or Exit?
     */
    private function updatePaymentItem(int $paymentId, array $lineData): bool
    {
        global $user;
        //====================================================================//
        // Load Payment Item
        $payment = $this->newPayment();
        $payment->fetch($paymentId);

        //====================================================================//
        // Update Payment Values
        $this->updatePaymentItemDatas($payment, $lineData);

        //====================================================================//
        // Check If Payment impact another Bill => Too Late to Delete & recreate this payment
        /** @var array|int $billArray */
        $billArray = $payment->getBillsArray();
        if (is_array($billArray) && (count($billArray) > 1)) {
            // Payment is Used by Another Bill => No Recreate
            return false;
        }
        //====================================================================//
        // Safety Check => Amount is Defined
        if (!isset($lineData["amount"])) {
            return false;
        }
        //====================================================================//
        // Check If Payment Amount are Different
        if (abs($payment->amount - self::parsePrice($lineData["amount"])) < 1E-6) {
            // Amounts are Similar => No Recreate
            return false;
        }
        //====================================================================//
        // Prepare Args
        $arg1 = (Local::dolVersionCmp("20.0.0") > 0) ? $user : 0;
        //====================================================================//
        // Try to delete Payment
        if ($payment->delete($arg1) <= 0) {
            $this->catchDolibarrErrors($payment);

            // Unable to Delete Payment => No Recreate
            return false;
        }

        //====================================================================//
        // Payment Was Deleted => Recreate
        return true;
    }

    /**
     * Update an Exiting Payment Datas
     *
     * @param Paiement|PaiementFourn $payment  Payment Item ID
     * @param array                  $lineData Line Data Array
     *
     * @return void
     */
    private function updatePaymentItemDatas($payment, array $lineData): void
    {
        //====================================================================//
        // Update Payment Date
        if (isset($lineData["date"])
            && (dol_print_date($payment->datepaye, 'standard') !== $lineData["date"])) {
            $payment->update_date($lineData["date"]);
            $this->catchDolibarrErrors($payment);
        }

        //====================================================================//
        // Update Payment Number
        $number = $payment->num_payment;
        if (isset($lineData["number"]) && ($number !== $lineData["number"])) {
            $payment->update_num($lineData["number"]);
            $this->catchDolibarrErrors($payment);
        }

        //====================================================================//
        // Update Payment Method
        if (isset($lineData["mode"])) {
            //====================================================================//
            // Detect Payment Method Id
            $newMethodId = PaymentMethods::getDoliId($lineData["mode"]);
            $currentMethodId = PaymentMethods::getDoliId($payment->type_code);
            if ($newMethodId && ($currentMethodId !== $newMethodId)) {
                $payment->setValueFrom("fk_paiement", $newMethodId);
                $this->catchDolibarrErrors($payment);
            }
        }
    }

    /**
     * Create a NEW Payment Item
     *
     * @param array $lineData Line Data Array
     *
     * @return bool Re-Create Payment Item or Exit?
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function createPaymentItem(array $lineData): bool
    {
        global $user;
        //====================================================================//
        // Verify Minimal Fields Ar available
        if (!isset($lineData["mode"])
            || !isset($lineData["date"])
            || !isset($lineData["amount"])
            || empty((double) $lineData["amount"])) {
            return false;
        }
        $payment = $this->newPayment();
        //====================================================================//
        // Setup Payment Invoice Id
        $payment->facid = $this->object->id;

        //====================================================================//
        // Setup Payment Date
        try {
            $payment->datepaye = (new DateTime($lineData["date"]))->getTimestamp();
        } catch (Exception $e) {
            Splash::log()->report($e);
        }
        //====================================================================//
        // Setup Payment Method
        if ($methodId = PaymentMethods::getDoliId($lineData["mode"])) {
            $payment->paiementid = $methodId;
        }
        //====================================================================//
        // Setup Payment Reference
        $payment->num_payment = $lineData["number"] ?? "";
        //====================================================================//
        // Setup Payment Amount
        $payment->amounts[$payment->facid] = self::parsePrice($lineData["amount"]);
        //====================================================================//
        // Take Care of Payment Currency
        if (property_exists($payment, "multicurrency_code")
            && is_string($this->object->multicurrency_code)
        ) {
            $payment->multicurrency_code[$payment->facid] = $this->object->multicurrency_code;
        }
        //====================================================================//
        // Create Payment Line
        if ($payment->create($user) <= 0) {
            Splash::log()->errTrace("Unable to create Invoice Payment. ");

            return $this->catchDolibarrErrors($payment);
        }

        //====================================================================//
        // Setup Payment Account Id
        return $this->addPaymentItemToBank($payment, $lineData);
    }

    /**
     * Update an Exiting Payment
     *
     * @param array $lineData Line Data Array
     *
     * @return bool Re-Create Payment Item or Exit?
     */
    private function addPaymentItemToBank(Paiement $payment, array $lineData): bool
    {
        global $user;
        //====================================================================//
        // Detect Account Id
        $accountId = BankAccounts::getDoliIdFromMethodId(PaymentMethods::getDoliId($lineData["mode"]));
        if (!$accountId) {
            return Splash::log()->err("Unable to detect Invoice Payment Account ID.");
        }
        //====================================================================//
        // Setup Payment Account Id
        if ($payment->addPaymentToBank($user, 'payment', '(Payment)', $accountId, "", "") < 0) {
            Splash::log()->errTrace("Unable to add Invoice Payment to Bank Account.");

            return $this->catchDolibarrErrors($payment);
        }

        return true;
    }

    /**
     * Create a New Payment Object
     *
     * @return Paiement|PaiementFourn
     */
    private function newPayment()
    {
        global $db;
        static $isInitDone;

        if (!isset($isInitDone)) {
            //====================================================================//
            // Include Object Dolibarr Class
            require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
            require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
            require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
            $isInitDone = true;
        }

        return is_a($this, Local::CLASS_SUPPLIER_INVOICE) ? new PaiementFourn($db) : new Paiement($db);
    }

    /**
     * Identify Bank Account ID using Splash Configuration
     *
     * @param array $lineData Payment Line data
     *
     * @return null|Paiement
     */
    private function identifyExistingPayment(array $lineData): ?Paiement
    {
        //====================================================================//
        // Search for Similar Payment in Database
        $payment = $this->getSimilarPayment($lineData);
        if (!$payment) {
            return null;
        }
        //====================================================================//
        // Collect Payment Invoice/Credit Paid Totals
        $paymentDetails = PaymentManager::getPaymentInvoicesTotals($payment);
        if (!$paymentDetails || isset($paymentDetails[$this->object->id])) {
            return null;
        }
        //====================================================================//
        // Attach Payment to this Invoice/Credit Note
        Splash::log()->war("An Existing Payment was Identified: ".$payment->ref);
        //====================================================================//
        // Update Payment Amounts for All Invoice/Credit Note
        $consumed = $payment->amount;
        foreach ($paymentDetails as $paymentDetail) {
            PaymentManager::updatePaymentInvoiceAmount(
                $payment,
                $paymentDetail['invoice'],
                (float) $paymentDetail['invoiced']
            );
            $consumed -= (float) $paymentDetail['invoiced'];
        }
        //====================================================================//
        // Add Remaining Amount to Current Invoice
        PaymentManager::addPaymentInvoiceAmount(
            $payment,
            $this->object,
            (float) $consumed
        );

        //====================================================================//
        // Return Identified Payment
        return $payment;
    }

    /**
     * Search for Similar Payment based on Inputs
     *
     * @param array $lineData Payment Line data
     *
     * @return null|Paiement
     */
    private function getSimilarPayment(array $lineData): ?Paiement
    {
        //====================================================================//
        // Safety Checks
        if (is_a($this, Local::CLASS_SUPPLIER_INVOICE)
            || empty($lineData["mode"])
            || empty($lineData["date"])
            || empty($lineData["number"])
            || empty($lineData["amount"])) {
            return null;
        }
        $methodId = PaymentMethods::getDoliId($lineData["mode"]);
        if (!$methodId) {
            return null;
        }

        //====================================================================//
        // Return Identified Payment
        return PaymentManager::searchForSimilarPayment(
            $lineData["date"],
            $lineData["number"],
            (float) $lineData["amount"],
            $methodId
        );
    }
}
