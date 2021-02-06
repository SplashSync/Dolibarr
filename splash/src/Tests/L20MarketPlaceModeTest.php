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

use ArrayObject;
use Exception;
use Splash\Client\Splash;
use Splash\Local\Local;
use Splash\Local\Services\MultiCompany;
use Splash\Tests\Tools\ObjectsCase;
use Splash\Tests\Tools\Traits\ObjectsSetTestsTrait;
use stdClass;

/**
 * Local Test Suite - Verify Working with Marketplace Mode
 */
class L20MarketPlaceModeTest extends ObjectsCase
{
    use ObjectsSetTestsTrait{
        prepareForTesting as protected corePrepareForTesting;
        verifySetResponse as protected coreVerifySetResponse;
    }

    /**
     * @var stdClass
     */
    private $currentEntity;

    /**
     * Init Setup for Marketplace Tests
     *
     * @dataProvider sequencesProvider
     *
     * @param string $sequence
     *
     * @return void
     */
    public function testSequenceSetup(string $sequence)
    {
        //====================================================================//
        //   Ensure Marketplace Mode
        $this->initMarketplaceMode($sequence);
        //====================================================================//
        //   Ensure at Least Two Entities Configured
        $entities = MultiCompany::getMultiCompanyInfos();
        $this->assertIsArray($entities);
        $this->assertGreaterThanOrEqual(2, count($entities));
    }

    /**
     * Test Marketplace fields are Correctly Defined
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @throws Exception
     *
     * @return void
     */
    public function testMarketPlaceFieldsSetup(string $sequence, string $objectType)
    {
        //====================================================================//
        // Init Marketplace Mode
        $this->initMarketplaceMode($sequence);
        //====================================================================//
        // verify Fields Definition
        $this->fields = Splash::object($objectType)->fields();
        // Entity Identifier
        $this->assertFieldIsDefined("http://schema.org/Author", 'identifier');
        $this->assertFieldIsRead("http://schema.org/Author", 'identifier');
        $this->assertFieldIsWrite("http://schema.org/Author", 'identifier');
        $this->assertFieldHasFormat("http://schema.org/Author", 'identifier', array(SPL_T_INT));
        // Entity Code
        $this->assertFieldIsDefined("http://schema.org/Author", 'alternateName');
        $this->assertFieldIsRead("http://schema.org/Author", 'alternateName');
        $this->assertFieldIsWrite("http://schema.org/Author", 'alternateName');
        // Entity Name
        $this->assertFieldIsDefined("http://schema.org/Author", 'name');
        $this->assertFieldIsRead("http://schema.org/Author", 'name');
    }

    /**
     * Test Object CRUD with Forced MarketPlace Entity
     *
     * @dataProvider ObjectTypesProvider
     *
     * @param string $sequence
     * @param string $objectType
     *
     * @return void
     */
    public function testMarketPlaceCrud(string $sequence, string $objectType)
    {
        //====================================================================//
        // Init Marketplace Mode
        $this->initMarketplaceMode($sequence);
        //====================================================================//
        //   Walk on Actives Entity
        foreach (MultiCompany::getMultiCompanyInfos() as $companyInfo) {
            $this->currentEntity = $companyInfo;
            //====================================================================//
            //   Execute Core CRUD Tests
            $this->coreTestSetSingleFieldFromService($objectType, new ArrayObject());
        }
    }

    /**
     * Close Setup for Marketplace Tests
     *
     * @return void
     */
    public function testSequenceClose()
    {
        global $db;
        //====================================================================//
        // Check Dolibarr Version Is Compatible
        if (Local::dolVersionCmp("5.0.0") < 0) {
            $this->markTestSkipped('This Feature is Not Implemented on Current Dolibarr Release.');
        }
        //====================================================================//
        // Force Enable MultiCompany Module
        $this->assertEquals(
            1,
            dolibarr_set_const($db, "MAIN_MODULE_MULTICOMPANY", '0', 'chaine', 0, '', 0),
            $db->lasterror()
        );
        //====================================================================//
        // Force Enable MarketPlace Mode
        Splash::configuration()->MarketplaceMode = false;
        //====================================================================//
        // Verify Configuration
        $this->assertFalse(MultiCompany::isMultiCompany(true));
        $this->assertFalse(MultiCompany::isMarketplaceMode(true));
    }

    /**
     * Ensure Marketplace Mode is enabled
     *
     * @param string $sequence
     *
     * @return void
     */
    public function initMarketplaceMode(string $sequence)
    {
        global $db, $conf;
        //====================================================================//
        // Check Dolibarr Version Is Compatible
        if (Local::dolVersionCmp("5.0.0") < 0) {
            $this->markTestSkipped('This Feature is Not Implemented on Current Dolibarr Release.');
        }
        //====================================================================//
        // Setup All Entities for Test Sequence
        foreach (MultiCompany::getMultiCompanyInfos() as $companyInfo) {
            $conf->entity = $companyInfo->id;
            $this->loadLocalTestSequence($sequence);
        }
        //====================================================================//
        // Force Enable MultiCompany Module
        $this->assertEquals(
            1,
            dolibarr_set_const($db, "MAIN_MODULE_MULTICOMPANY", '1', 'chaine', 0, '', 0),
            $db->lasterror()
        );
        //====================================================================//
        // Force Enable MarketPlace Mode
        Splash::configuration()->MarketplaceMode = true;
        //====================================================================//
        // Verify Configuration
        $this->assertTrue(MultiCompany::isMultiCompany(true));
        $this->assertTrue(MultiCompany::isMarketplaceMode(true));
    }

    /**
     * Ensure Set/Write Test is Possible & Generate Fake Object Data
     * -> This Function uses Preloaded Fields
     * -> If Md5 provided, check Current Field was Modified
     *
     * @param string           $objectType Current Object Type
     * @param null|ArrayObject $field      Current Tested Field (ArrayObject)
     * @param bool             $unik       Ask for Unik Field Data
     *
     * @return array|false Generated Data Block or False if not Allowed
     */
    protected function prepareForTesting(string $objectType, ?ArrayObject $field, bool $unik = true)
    {
        //====================================================================//
        //   Generate Core Data
        $coreData = $this->corePrepareForTesting($objectType, $field, $unik);
        //====================================================================//
        //   Generate Core Data
        if (is_object($this->currentEntity)) {
            $coreData["entity_id"] = $this->currentEntity->id;
        }

        return $coreData;
    }

    /**
     * Verify Client Object Set Response.
     *
     * @param string $objectType
     * @param mixed  $objectId
     * @param string $action
     * @param array  $expectedData
     *
     * @throws Exception
     *
     * @return void
     */
    protected function verifySetResponse(string $objectType, $objectId, string $action, array $expectedData)
    {
        //====================================================================//
        //   Execute Core Data Verifications
        $this->coreVerifySetResponse($objectType, $objectId, $action, $expectedData);

        //====================================================================//
        //   Read Object Data
        $reducedFieldsList = $this->reduceFieldList(Splash::object($objectType)->fields(), true);
        $currentData = Splash::object($objectType)->get($objectId, $reducedFieldsList);
        $this->assertIsArray($currentData);
        //====================================================================//
        //   Verify Entity Data
        $this->assertEquals($this->currentEntity->id, $currentData["entity_id"]);
        $this->assertEquals($this->currentEntity->code, $currentData["entity_code"]);
        $this->assertEquals($this->currentEntity->name, $currentData["entity_name"]);
    }
}
