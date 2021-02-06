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

namespace Splash\Local\Tests;

use Account;
use Facture;
use Splash\Client\Splash;
use Splash\Components\FieldsFactory;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify Mapping of Invoices Payments Lines to Selected Bank Accounts
 */
class L03PaymentBanksTest extends ObjectsCase
{
    use \Splash\Models\Objects\ListsTrait;
    use \Splash\Local\Core\ErrorParserTrait;
    use \Splash\Local\Core\CreditModeTrait;
    use \Splash\Local\Objects\Invoice\PaymentsTrait;

    /** @var array */
    protected $in;

    /** @var array */
    protected $out;

    /** @var Facture */
    protected $object;

    /**
     * @dataProvider paymentsTypesProvider
     *
     * @param string $objectType
     * @param string $paymentType
     * @param string $splashMethod
     *
     * @return void
     */
    public function testSetupBankAccount($objectType, $paymentType, $splashMethod)
    {
        //====================================================================//
        //   Create Bank Account for this Payment Type
        $account = $this->createBankAccount($paymentType);
        $this->setupBankAccount($paymentType, $account);

        //====================================================================//
        //   Create Fake Invoice Data
        $fields = $this->fakeFieldsList($objectType, array(), true);
        $fakeData = $this->fakeObjectData($fields);

        //====================================================================//
        //   Setup Tax Names
        foreach ($fakeData["payments"] as $index => $data) {
            $fakeData["payments"][$index]["mode"] = $splashMethod;
        }

        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($objectType)->lock();
        $objectId = Splash::object($objectType)->set(null, $fakeData);
        $this->assertNotEmpty($objectId);

        //====================================================================//
        //   Add Object Id to Created List
        $this->assertIsString($objectId);
        $this->addTestedObject($objectType, $objectId);

        //====================================================================//
        //   Read Order Data
        $objectData = Splash::object($objectType)
            ->get($objectId, array("mode@payments", "number@payments", "amount@payments"));
        $this->assertNotEmpty($objectData);
        $this->assertIsArray($objectData);

        //====================================================================//
        //   verify Tax Values
        foreach ($objectData["payments"] as $data) {
            $this->assertEquals($splashMethod, $data["mode"]);
        }

        //====================================================================//
        //   Load Invoice Payments
        $this->loadPayments($objectId);
        //====================================================================//
        //   Verify Payments Are in Correct Bank Account
        foreach ($this->payments as $payment) {
            $this->assertEquals($payment->baid, $account->rowid);
            $this->assertEquals($payment->code, $paymentType);
        }
    }

    /**
     * Create a Dedicated Bank Account
     *
     * @param string $paymentType
     *
     * @return Account
     */
    public function createBankAccount($paymentType)
    {
        global $db, $user;

        require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");

        //====================================================================//
        //   Load Bank Account for this Payment Type
        $account = new Account($db);

        $account->fetch(0, $paymentType);
        if ($account->rowid) {
            $this->assertNotEmpty($account);
            $this->assertEquals($paymentType, $account->ref);
            $this->assertEquals($paymentType, $account->label);

            return $account;
        }

        //====================================================================//
        //   Create Bank Account for this Payment Type
        $account->state_id = 40;
        $account->country_id = 1;
        $account->date_solde = dol_now();
        $account->entity = 1;
        $account->ref = $paymentType;
        $account->label = $paymentType;
        $account->courant = \Account::TYPE_CURRENT;
        $account->currency_code = "EUR";

        $this->assertGreaterThan(0, $account->create($user, 0));
        $this->assertEquals($paymentType, $account->ref);
        $this->assertEquals($paymentType, $account->label);
        $account->rowid = $account->id;

        return $account;
    }

    /**
     * Setup Payment Method to Bank Account
     *
     * @param string  $paymentType
     * @param Account $account
     *
     * @return void
     */
    public function setupBankAccount($paymentType, $account)
    {
        global $db, $conf, $user;

        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

        $this->assertNotEmpty($account->rowid);

        //====================================================================//
        //   Identify Payment Method Id
        $paymentMethodId = $this->identifyPaymentType($paymentType);
        $this->assertNotEmpty($paymentMethodId);

        //====================================================================//
        //   Activate Payment Method if Disabled
        require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/cpaiement.class.php");
        $payment = new \Cpaiement($db);
        $payment->fetch($paymentMethodId);
        if (!$payment->active) {
            $payment->active = 1;
            $payment->update($user, false);
        }

        //====================================================================//
        //   Map Payment Method to Dedicated Account
        $parameterName = "SPLASH_BANK_FOR_".$paymentMethodId;
        dolibarr_set_const($db, $parameterName, (string) $account->rowid, 'chaine', 0, '', $conf->entity);

        $this->assertEquals(
            $account->rowid,
            $conf->global->{$parameterName}
        );
    }

    /**
     * @return array
     */
    public function paymentsTypesProvider()
    {
        return array(
            //====================================================================//
            //   Tests For Invoices Objects
            array("Invoice",    "VIR",    "ByBankTransferInAdvance"),
            array("Invoice",    "CHQ",    "CheckInAdvance"),
            array("Invoice",    "LIQ",    "Cash"),
            array("Invoice",    "CB",     "DirectDebit"),
            array("Invoice",    "VAD",    "PayPal"),
            array("Invoice",    "FAC",    "COD"),
        );
    }

    /**
     * @return FieldsFactory
     */
    protected static function fieldsFactory()
    {
        return new FieldsFactory();
    }
}
