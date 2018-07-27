<?php
namespace Splash\Local\Tests;

use Splash\Tests\Tools\ObjectsCase;
use Splash\Client\Splash;

use Splash\Models\Helpers\ObjectsHelper;

/**
 * @abstract    Local Test Suite - Verify Guest Orders & Invoices Writing
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L04GuestOrdersTest extends ObjectsCase
{
    
    /**
     * @var array
     */
    protected $Fields;

    /**
     * @var array
     */
    private $Field;
    
    /**
     * @abstract    Test Objects Have Corrects Fields Definitions
     * @dataProvider ObjectTypesProvider
     */
    public function testFieldsDefinitions($Sequence, $ObjectType)
    {
        global $conf;
        
        //====================================================================//
        //   Only For Orders & Invoices
        if (!in_array($ObjectType, ["Order", "Invoice"])) {
            $this->assertTrue(true);
            return;
        }
        
        $this->loadLocalTestSequence($Sequence);
        
        //====================================================================//
        //   Load Object Fields
        $Fields =   Splash::object($ObjectType)->fields();
        $this->assertNotEmpty($Fields);
        
        //====================================================================//
        //   If Guest Mode not Active
        if (!$conf->global->SPLASH_GUEST_ORDERS_ALLOW) {
            //====================================================================//
            //   Verify SocId Field
            $SocId  = $this->findField($Fields, ["socid"]);
            $this->assertNotEmpty($SocId);
            $this->assertTrue($SocId->required);
            //====================================================================//
            //   Verify Email Field
            $this->assertEmpty($this->findField($Fields, ["email"]));
            
        //====================================================================//
        //   If Guest Mode is Active
        } else {
            //====================================================================//
            //   Verify SocId Field
            $SocId  = $this->findField($Fields, ["socid"]);
            $this->assertNotEmpty($SocId);
            $this->assertFalse($SocId->required);
            //====================================================================//
            //   Verify Email Field
            $Email  = $this->findField($Fields, ["email"]);
            $this->assertNotEmpty($Email);
            $this->assertEquals(SPL_T_EMAIL, $Email->type);
            $this->assertFalse($Email->required);
            $this->assertFalse($Email->read);
            $this->assertTrue($Email->write);
        }
    }
        
    /**
     * @abstract    Test Create & Update Without Customer Email
     * @dataProvider ObjectTypesProvider
     */
    public function testGuestWithoutEmailDetection($Sequence, $ObjectType)
    {
        global $db, $conf;
        
        //====================================================================//
        //   Safety Checks
        if (!$this->isAllowedGuestSequence($Sequence, $ObjectType)) {
            return;
        }
        //====================================================================//
        //   Disable Email Detection
        dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_EMAIL", 0, 'chaine', 0, '', $conf->entity);
        
        //====================================================================//
        //   Create Fake Order/Invoice Data
        $this->Fields   =   $this->fakeFieldsList($ObjectType, false, true);
        $this->Field    =   array($this->findField($this->Fields, ["socid"]));
        $FakeData       =   $this->fakeObjectData($this->Fields);

        //====================================================================//
        //   Setup Given
        $GivenData  =   $FakeData;
        unset($GivenData["socid"]);
        
        //====================================================================//
        //   Setup Expected Guest Customer Id
        $ExpectedData   =   ObjectsHelper::encode("ThirdParty", $conf->global->SPLASH_GUEST_ORDERS_CUSTOMER);
        
        //====================================================================//
        //   Verify On Create Operation
        $ObjectId = $this->verifyCreate($ObjectType, $GivenData, array("socid" => $ExpectedData));
        
        //====================================================================//
        //   Verify On Update Operation => With Provided SocId
        $this->verifyUpdate($ObjectType, $ObjectId, $FakeData, array("socid" => $FakeData["socid"]));
        //====================================================================//
        //   Verify On Update Operation => Without SocId
        $this->verifyUpdate($ObjectType, $ObjectId, $GivenData, array("socid" => $FakeData["socid"]));
        //====================================================================//
        //   Verify On Update Operation => With Empty SocId
        $GivenData["socid"] = null;
        $this->verifyUpdate($ObjectType, $ObjectId, $GivenData, array("socid" => $ExpectedData));
    }

    /**
     * @abstract    Test Create & Update With Customer Email
     * @dataProvider ObjectTypesProvider
     */
    public function testGuestWithEmailDetection($Sequence, $ObjectType)
    {
        global $db, $conf;
        
        //====================================================================//
        //   Safety Checks
        if (!$this->isAllowedGuestSequence($Sequence, $ObjectType)) {
            return;
        }
        //====================================================================//
        //   Disable Email Detection
        dolibarr_set_const($db, "SPLASH_GUEST_ORDERS_EMAIL", 1, 'chaine', 0, '', $conf->entity);
        
        
        //====================================================================//
        //   Create Fake Order/Invoice Data
        $this->Fields   =   $this->fakeFieldsList($ObjectType, false, true);
        $this->Field    =   array($this->findField($this->Fields, ["socid"]));
        $FakeData       =   $this->fakeObjectData($this->Fields);
        
        //====================================================================//
        //   Create Fake Customer with Email
        $CustomerData = $this->createThirdPartyWithEmail();
        
        //====================================================================//
        //   Setup Expected Guest Customer Id
        $ExpectedData   =   ObjectsHelper::encode("ThirdParty", $conf->global->SPLASH_GUEST_ORDERS_CUSTOMER);

        //====================================================================//
        //   Setup Given
        $GivenData  =   $FakeData;
        unset($GivenData["socid"]);
        $GivenData["email"] =   $CustomerData["Email"];
        
        //====================================================================//
        //   Verify On Create Operation => Without SocId (but Email)
        $ObjectId = $this->verifyCreate($ObjectType, $GivenData, array("socid" => $CustomerData["ObjectId"]));
        //====================================================================//
        //   Verify On Update Operation => With Provided SocId
        $this->verifyUpdate($ObjectType, $ObjectId, $FakeData, array("socid" => $FakeData["socid"]));
        //====================================================================//
        //   Verify On Update Operation => Without SocId
        $this->verifyUpdate($ObjectType, $ObjectId, $GivenData, array("socid" => $CustomerData["ObjectId"]));
        //====================================================================//
        //   Verify On Update Operation => With Provided SocId
        $this->verifyUpdate($ObjectType, $ObjectId, $FakeData, array("socid" => $FakeData["socid"]));
        //====================================================================//
        //   Verify On Update Operation => With Empty SocId  (but Email)
        $GivenData["socid"] = null;
        $this->verifyUpdate($ObjectType, $ObjectId, $GivenData, array("socid" => $CustomerData["ObjectId"]));
        //====================================================================//
        //   Verify On Update Operation => With Empty SocId and Empty Email
        $GivenData["email"] = null;
        $this->verifyUpdate($ObjectType, $ObjectId, $GivenData, array("socid" => $ExpectedData));
    }
    
    /**
     * @abstract    Ensure We are in Correct ObjectType & Guest Mode is Allowed
     */
    public function isAllowedGuestSequence($Sequence, $ObjectType)
    {
        global $conf;
        
        //====================================================================//
        //   Only For Orders & Invoices
        if (!in_array($ObjectType, ["Order", "Invoice"])) {
            $this->assertTrue(true);
            return false;
        }
        //====================================================================//
        //   Init Test Sequence
        $this->loadLocalTestSequence($Sequence);
        //====================================================================//
        //   Only If Guest Mode is Active
        if (!$conf->global->SPLASH_GUEST_ORDERS_ALLOW) {
            $this->assertTrue(true);
            return false;
        }
        
        return true;
    }
    
    private function verifyCreate($ObjectType, $GivenData, $ExpectedData)
    {
        //====================================================================//
        //   Create Object on Module
        Splash::object($ObjectType)->lock();
        $ObjectId = Splash::object($ObjectType)->set(null, $GivenData);
        $this->assertNotEmpty($ObjectId);
        
        //====================================================================//
        //   Verify Object Id Is Not Empty
        $this->assertNotEmpty($ObjectId, "Returned New Object Id is Empty");

        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($ObjectType, $ObjectId);

        //====================================================================//
        //   Read Object Data
        $ObjectData  =   Splash::object($ObjectType)
            ->get($ObjectId, ["socid"]);

        //====================================================================//
        //   Verify Object Data are Ok
        $this->compareDataBlocks($this->Field, $ExpectedData, $ObjectData, $ObjectType);
        
        return $ObjectId;
    }

    private function verifyUpdate($ObjectType, $ObjectId, $GivenData, $ExpectedData)
    {
        //====================================================================//
        //   Create Object on Module
        Splash::object($ObjectType)->lock();
        $WriteObjectId = Splash::object($ObjectType)->set($ObjectId, $GivenData);
        $this->assertNotEmpty($ObjectId);
        
        //====================================================================//
        //   Verify Object Id Is Not Empty
        $this->assertNotEmpty($WriteObjectId, "Returned New Object Id is Empty");

        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($ObjectType, $ObjectId);

        //====================================================================//
        //   Read Object Data
        $ObjectData  =   Splash::object($ObjectType)
            ->get($ObjectId, ["socid"]);

        //====================================================================//
        //   Verify Object Data are Ok
        $this->compareDataBlocks($this->Field, $ExpectedData, $ObjectData, $ObjectType);
        
        return $WriteObjectId;
    }
    
    private function createThirdPartyWithEmail()
    {
        //====================================================================//
        //   Create Fake ThirdParty Data
        $Fields   =   $this->fakeFieldsList("ThirdParty", false, true);
        $FakeData       =   $this->fakeObjectData($Fields);
        $this->assertNotEmpty($FakeData['email']);

        //====================================================================//
        //   Create ThirdParty on Module
        Splash::object("ThirdParty")->lock();
        $ObjectId = Splash::object("ThirdParty")->set(null, $FakeData);
        $this->assertNotEmpty($ObjectId);

        return array(
            "ObjectId"  =>  ObjectsHelper::encode("ThirdParty", $ObjectId),
            "Email"     =>  $FakeData['email']
        );
    }
}
