<?php
namespace Splash\Local\Tests;

use Splash\Tests\Tools\ObjectsCase;
use Splash\Client\Splash;

/**
 * @abstract    Local Test Suite - Verify Writing of Orders & Invoices Lines with VAT Taxe Names Options
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L02TaxesByCodesTest extends ObjectsCase
{
    use \Splash\Local\Objects\Invoice\PaymentsTrait;
    
    /**
     * @dataProvider paymentsTypesProvider
     */
    public function testSetupBankAccount($ObjectType, $paymentType, $SplashMethod)
    {
        
//        if (Splash::local()->dolVersionCmp("5.0.0") < 0) {
//            $this->markTestIncomplete('Feature Not Available in This Version.');
//            return;
//        }
//
        //====================================================================//
        //   Create Bank Account for this Payment Type
        $Account = $this->createBankAccount($paymentType);
        $this->setupBankAccount($paymentType, $Account);
        
        //====================================================================//
        //   Create Fake Invoice Data
        $Fields         =   $this->fakeFieldsList($ObjectType, [], true);
        $FakeData       =   $this->fakeObjectData($Fields);
        
        //====================================================================//
        //   Setup Tax Names
        foreach ($FakeData["payments"] as $Index => $Data) {
            $FakeData["payments"][$Index]["mode"]   =   $SplashMethod;
        }
        
        //====================================================================//
        //   Execute Action Directly on Module
        Splash::object($ObjectType)->lock();
        $ObjectId = Splash::object($ObjectType)->set(null, $FakeData);
        $this->assertNotEmpty($ObjectId);

        //====================================================================//
        //   Read Order Data
        $ObjectData  =   Splash::object($ObjectType)
                ->get($ObjectId, ["mode@payments", "number@payments", "amount@payments"]);
        
        //====================================================================//
        //   verify Tax Values
        foreach ($ObjectData["payments"] as $Data) {
            $this->assertEquals($SplashMethod, $Data["mode"]);
        }

        //====================================================================//
        //   Load Invoice Payments
        $this->loadPayments($ObjectId);
        //====================================================================//
        //   Verify Payments Are in Correct Bank Account
        foreach ($this->Payments as $Payment) {
            $this->assertEquals($Payment->baid, $Account->rowid);
            $this->assertEquals($Payment->code, $paymentType);
        }
    }
    
    /**
     * @abstract    Create a Dedicated Bank Account
     *
     * @return  Account
     */
    public function createBankAccount($paymentType)
    {
        global $db, $user;

        require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");
        
        //====================================================================//
        //   Load Bank Account for this Payment Type
        $Account = new \Account($db);
        
        $Account->fetch(null, $paymentType);
        if ($Account->rowid) {
            $this->assertNotEmpty($Account);
            $this->assertEquals($paymentType, $Account->ref);
            $this->assertEquals($paymentType, $Account->label);
            return $Account;
        }
        
        //====================================================================//
        //   Create Bank Account for this Payment Type
        $Account->state_id      = 40;
        $Account->country_id    = 1;
        $Account->date_solde    = dol_now();
        $Account->entity        = 1;
        $Account->ref           = $paymentType;
        $Account->label         = $paymentType;
        $Account->courant       = \Account::TYPE_CURRENT;
        $Account->currency_code = "EUR";
        
        $this->assertGreaterThan(0, $Account->create($user, 0));
        $this->assertEquals($paymentType, $Account->ref);
        $this->assertEquals($paymentType, $Account->label);
        $Account->rowid = $Account->id;
        return $Account;
    }
    
    /**
     * @abstract    Setup Payment Method to Bank Account
     *
     * @return  Account
     */
    public function setupBankAccount($paymentType, $Account)
    {
        global $db, $conf, $user;

        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
        
        $this->assertNotEmpty($Account->rowid);
        
        //====================================================================//
        //   Identify Payment Method Id
        $PaymentMethodId    = $this->identifyPaymentType($paymentType);
        $this->assertNotEmpty($PaymentMethodId);

        //====================================================================//
        //   Activate Payment Method if Disabled
        require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/cpaiement.class.php");
        $Payment    =   new \Cpaiement($db);
        $Payment->fetch($PaymentMethodId);
        if (!$Payment->active) {
            $Payment->active = 1;
            $Payment->update($user, false);
        }
        
        //====================================================================//
        //   Map Payment Method to Dedicated Account
        $ParameterName  =    "SPLASH_BANK_FOR_".$PaymentMethodId;
        dolibarr_set_const($db, $ParameterName, $Account->rowid, 'chaine', 0, '', $conf->entity);
        
        $this->assertEquals(
            $Account->rowid,
            $conf->global->$ParameterName
        );
    }

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
        );
    }
}
