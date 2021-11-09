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

namespace Splash\Local\Objects\Invoice;

use ArrayObject;
use Paiement;
use PaiementFourn;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Local\Objects\SupplierInvoice;

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
    protected $payments = array();

    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildPaymentsFields()
    {
        global $langs;

        $listName = "" ;

        //====================================================================//
        // Payment Line Payment Method
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("mode")
            ->InList("payments")
            ->Name($listName.$langs->trans("PaymentMode"))
            ->MicroData("http://schema.org/Invoice", "PaymentMethod")
            ->AddChoice("ByBankTransferInAdvance", "By bank transfer in advance")
            ->AddChoice("CheckInAdvance", "Check in advance")
            ->AddChoice("COD", "Cash On Delivery")
            ->AddChoice("Cash", "Cash")
            ->AddChoice("PayPal", "Online Payments (PayPal, more..)")
            ->AddChoice("DirectDebit", "Credit Card")
            ->Association("date@payments", "mode@payments", "amount@payments");

        //====================================================================//
        // Payment Line Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->Identifier("date")
            ->InList("payments")
            ->Name($listName.$langs->trans("Date"))
            ->MicroData("http://schema.org/PaymentChargeSpecification", "validFrom")
            ->Association("date@payments", "mode@payments", "amount@payments");

        //====================================================================//
        // Payment Line Payment Identifier
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("number")
            ->InList("payments")
            ->Name($listName.$langs->trans('Numero'))
            ->MicroData("http://schema.org/Invoice", "paymentMethodId")
            ->Association("date@payments", "mode@payments", "amount@payments");

        //====================================================================//
        // Payment Line Amount
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("amount")
            ->InList("payments")
            ->Name($listName.$langs->trans("PaymentAmount"))
            ->MicroData("http://schema.org/PaymentChargeSpecification", "price")
            ->Association("date@payments", "mode@payments", "amount@payments");
    }

    /**
     * Fetch Invoice Payments List (Done after Load)
     *
     * @param mixed $invoiceId
     *
     * @return bool
     */
    protected function loadPayments($invoiceId): bool
    {
        global $db;

        //====================================================================//
        // Prepare SQL Request
        // Payments already done (from payment on this invoice)
        $sql = 'SELECT p.datep as date, p.num_paiement as number, p.rowid as id, p.fk_bank,';
        $sql .= ' c.code as code, c.libelle as payment_label,';
        $sql .= ' pf.amount as amount,';
        $sql .= ' ba.rowid as baid, ba.ref, ba.label';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'c_paiement as c, ' ;
        $sql .= ($this instanceof SupplierInvoice)
            ? MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf, '.MAIN_DB_PREFIX.'paiementfourn as p'
            : MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p'
        ;
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
        $sql .= ($this instanceof SupplierInvoice)
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
            $this->payments[$index]->method = $this->identifySplashPaymentMethod($this->payments[$index]->code);
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
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setPaymentLineFields(string $fieldName, $fieldData)
    {
        //====================================================================//
        // Safety Check
        if ("payments" !== $fieldName) {
            return;
        }
        //====================================================================//
        // Verify Lines List & Update if Needed
        if (is_array($fieldData) || is_a($fieldData, "ArrayObject")) {
            foreach ($fieldData as $lineData) {
                $this->setPaymentLineData($lineData);
            }
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
            if (count($payment->getBillsArray()) > 1) {
                continue;
            }
            //====================================================================//
            // Try to delete Payment Line
            $payment->delete();
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
            $billArray = $payment->getBillsArray();
            if (is_array($billArray) && count($billArray) > 1) {
                continue;
            }
            //====================================================================//
            // Try to delete Payment Line
            if ($payment->delete() <= 0) {
                $this->catchDolibarrErrors($payment);

                Splash::log()->errTrace("Unable to Delete Invoice Payment (".$paymentData->id.")");
            }
        }
    }

    /**
     * Update a Payment line Data
     *
     * @param array|ArrayObject $lineData Line Data Array
     *
     * @return bool
     */
    private function setPaymentLineData($lineData): bool
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
            // Update Payment Infos, If No Need to rRe-Create => EXIT
            if (!$this->updatePaymentItem($paymentData->id, $lineData)) {
                return false;
            }
        }

        //====================================================================//
        // Create New Line
        //====================================================================//

        return $this->createPaymentItem($lineData);
    }

    /**
     * Update an Exiting Payment
     *
     * @param int               $paymentId Payment Item ID
     * @param array|ArrayObject $lineData  Line Data Array
     *
     * @return bool Re-Create Payment Item or Exit?
     */
    private function updatePaymentItem(int $paymentId, $lineData): bool
    {
        //====================================================================//
        // Load Payment Item
        $payment = $this->newPayment();
        $payment->fetch($paymentId);

        //====================================================================//
        // Update Payment Values
        $this->updatePaymentItemDatas($payment, $lineData);

        //====================================================================//
        // Check If Payment impact another Bill => Too Late to Delete & recreate this payment
        if (count($payment->getBillsArray()) > 1) {
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
        // Try to delete Payment
        if ($payment->delete() <= 0) {
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
     * @param array|ArrayObject      $lineData Line Data Array
     *
     * @return void
     */
    private function updatePaymentItemDatas($payment, $lineData)
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
        /** @since V12.0 Field Renamed  */
        $number = (Local::dolVersionCmp("12.0.0") < 0)
            ? $payment->num_paiement
            : $payment->num_payment;
        if (isset($lineData["number"]) && ($number !== $lineData["number"])) {
            $payment->update_num($lineData["number"]);
            $this->catchDolibarrErrors($payment);
        }

        //====================================================================//
        // Update Payment Method
        if (isset($lineData["mode"])) {
            //====================================================================//
            // Detect Payment Method Id
            $newMethodId = $this->identifyPaymentMethod($lineData["mode"]);
            $currentMethodId = $this->identifyPaymentType($payment->type_code);
            if ($newMethodId && ($currentMethodId !== $newMethodId)) {
                $payment->setValueFrom("fk_paiement", $newMethodId);
                $this->catchDolibarrErrors($payment);
            }
        }
    }

    /**
     * Update an Exiting Payment
     *
     * @param array|ArrayObject $lineData Line Data Array
     *
     * @return bool Re-Create Payment Item or Exit?
     */
    private function createPaymentItem($lineData)
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
        $payment->datepaye = $lineData["date"];
        //====================================================================//
        // Setup Payment Method
        $payment->paiementid = $this->identifyPaymentMethod($lineData["mode"]);
        //====================================================================//
        // Setup Payment Reference
        /** @since V12.0 Field Renamed  */
        if (Local::dolVersionCmp("12.0.0") < 0) {
            $payment->num_paiement = $lineData["number"];
        } else {
            $payment->num_payment = $lineData["number"];
        }
        //====================================================================//
        // Setup Payment Amount
        $payment->amounts[$payment->facid] = self::parsePrice($lineData["amount"]);

        //====================================================================//
        // Create Payment Line
        if ($payment->create($user) <= 0) {
            Splash::log()->errTrace("Unable to create Invoice Payment. ");

            return $this->catchDolibarrErrors($payment);
        }

        //====================================================================//
        // Setup Payment Account Id
        $result = $payment->addPaymentToBank(
            $user,
            'payment',
            '(Payment)',
            $this->identifyBankAccountId($this->identifyPaymentMethod($lineData["mode"])),
            "",
            ""
        );

        if ($result < 0) {
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

        return ($this instanceof SupplierInvoice) ? new PaiementFourn($db) : new Paiement($db);
    }

    /**
     * Write Given Fields
     *
     * @param string $methodType
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function identifySplashPaymentMethod(string $methodType)
    {
        //====================================================================//
        // Detect Payment Method Type from Default Payment "known" methods
        switch ($methodType) {
            case "PRE":
            case "PRO":
            case "TIP":
            case "VIR":
                return "ByBankTransferInAdvance";
            case "CHQ":
                return "CheckInAdvance";
            case "FAC":
                return "COD";
            case "LIQ":
                return "Cash";
            case "CB":
                return "DirectDebit";
            case "VAD":
                return "PayPal";
            default:
                return "Unknown";
        }
    }

    /**
     * Write Given Fields
     *
     * @param string $methodType
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function identifyPaymentMethod(string $methodType)
    {
        global $conf;

        //====================================================================//
        // Detect Payment Method Type from Default Payment "known/standard" methods
        switch ($methodType) {
            case "ByBankTransferInAdvance":
                return $this->identifyPaymentType("VIR");
            case "CheckInAdvance":
                return $this->identifyPaymentType("CHQ");
            case "COD":
                return $this->identifyPaymentType("FAC");
            case "Cash":
                return $this->identifyPaymentType("LIQ");
            case "PayPal":
                return $this->identifyPaymentType("VAD");
            case "CreditCard":
            case "DirectDebit":
                return $this->identifyPaymentType("CB");
        }

        //====================================================================//
        // Return Default Payment Method or 0 (Default)
        if (isset($conf->global->SPLASH_DEFAULT_PAYMENT) && !empty($conf->global->SPLASH_DEFAULT_PAYMENT)) {
            return $this->identifyPaymentType($conf->global->SPLASH_DEFAULT_PAYMENT);
        }

        return $this->identifyPaymentType("VAD");
    }

    /**
     * Identify Payment Method ID using Payment Method Code
     *
     * @param string $paymentTypeCode Payment Method Code
     *
     * @return int
     */
    private function identifyPaymentType(string $paymentTypeCode)
    {
        global $db;

        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
        $form = new \Form($db);
        $form->load_cache_types_paiements();
        //====================================================================//
        // Safety Check
        if (empty($form->cache_types_paiements)) {
            return 0;
        }
        //====================================================================//
        // Detect Payment Method Id From Method Code
        foreach ($form->cache_types_paiements as $key => $paymentMethod) {
            if ($paymentMethod["code"] === $paymentTypeCode) {
                return $key;
            }
        }
        //====================================================================//
        // Default Payment Method Id
        return 0;
    }

    /**
     * Identify Bank Account ID using Splash Configuration
     *
     * @param int $paymentTypeId Payment Method ID
     *
     * @return int
     */
    private function identifyBankAccountId(int $paymentTypeId)
    {
        global $conf;

        //====================================================================//
        // Detect Bank Account Id From Method Code
        $parameterName = "SPLASH_BANK_FOR_".$paymentTypeId;
        if (isset($conf->global->{$parameterName}) && !empty($conf->global->{$parameterName})) {
            return $conf->global->{$parameterName};
        }

        //====================================================================//
        // Default Payment Account Id
        return $conf->global->SPLASH_BANK;
    }
}
