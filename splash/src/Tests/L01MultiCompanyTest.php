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

namespace Splash\Local\Tests;

use Exception;
use Splash\Client\Splash;
use Splash\Components\CommitsManager;
use Splash\Local\Local;
use Splash\Local\Services\MultiCompany;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify Access to MultiCompany Objects
 */
class L01MultiCompanyTest extends ObjectsCase
{
    /**
     * @var array
     */
    private array $objectList = array();

    /**
     * @var array
     */
    private array $objectCount = array();

    /**
     * Ensure Minimal Modules are Enabled
     */
    public function testMinimalModulesEnabled(): void
    {
        //====================================================================//
        //   Enable MultiCompany Mode
        $this->changeMultiCompanyMode(true);
        //====================================================================//
        // Setup All Entities for Test Sequence
        foreach (MultiCompany::getMultiCompanyInfos() as $companyInfo) {
            //====================================================================//
            // Simulate Logged on Main Entity
            $this->changeEntityId($companyInfo->id);
            //====================================================================//
            // Enable Module on Entity
            activateModule("modSociete");
            activateModule("modProduct");
            activateModule("modCommande");
            activateModule("modFacture");
        }
    }

    /**
     * Test Loading of Object that are not on Selected Entity
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
    public function testLoadAccess(string $sequence, string $objectType): void
    {
        $this->loadLocalTestSequence($sequence);

        //====================================================================//
        //   Enable MultiCompany Mode
        $this->changeMultiCompanyMode(true);

        //====================================================================//
        //   Simulate Logged on Main Entity
        $this->changeEntityId(1);

        //====================================================================//
        //   Get next Available Object ID from Module
        $objectId = $this->getNextObjectId($objectType);
        $this->assertNotEmpty($objectId);
        $this->assertIsString($objectId);

        //====================================================================//
        //   Get Readable Object Fields List
        $fields = $this->reduceFieldList(Splash::object($objectType)->Fields(), true, false);

        //====================================================================//
        //   Execute Action Directly on Module
        $allowed = Splash::object($objectType)->get($objectId, $fields);

        //====================================================================//
        //   Verify Response
        $this->assertNotEmpty($allowed);

        //====================================================================//
        //   Simulate Logged on another Entity
        $this->changeEntityId();

        //====================================================================//
        //   Execute Action Directly on Module
        $rejected = Splash::object($objectType)->get($objectId, $fields);

        //====================================================================//
        //   Verify Response
        $this->assertNull($rejected);

        //====================================================================//
        //   Simulate Logged on Main Entity
        $this->changeEntityId(1);
    }

    /**
     * Test Delete of Object that are not on Selected Entity
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
    public function testDeleteAccess(string $sequence, string $objectType): void
    {
        $this->loadLocalTestSequence($sequence);

        //====================================================================//
        //   Enable MultiCompany Mode
        $this->changeMultiCompanyMode(true);

        //====================================================================//
        //   Simulate Logged on Main Entity
        $this->changeEntityId(1);

        //====================================================================//
        //   Generate Dummy Object Data (Required Fields Only)
        $dummyData = $this->prepareForTesting($objectType);
        if (false == $dummyData) {
            return;
        }

        //====================================================================//
        //   Create a New Object on Module
        $objectId = Splash::object($objectType)->Set(null, $dummyData);
        $this->assertIsString($objectId);

        //====================================================================//
        // Lock New Objects To Avoid Action Commit
        Splash::object($objectType)->Lock($objectId);

        //====================================================================//
        //   Simulate Logged on another Entity
        $this->changeEntityId();

        //====================================================================//
        //   Delete Object on Module
        $rejected = Splash::object($objectType)->Delete($objectId);

        //====================================================================//
        //   Verify Response
        $this->assertFalse($rejected);

        //====================================================================//
        //   Simulate Logged on Main Entity
        $this->changeEntityId(1);

        //====================================================================//
        //   Delete Object on Module
        $allowed = Splash::object($objectType)->Delete($objectId);

        //====================================================================//
        //   Verify Response
        $this->assertTrue($allowed);
    }

    /**
     * Simulate MultiCompany Mode
     *
     * @param bool $state
     *
     * @return void
     */
    public function changeMultiCompanyMode(bool $state = false)
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
            dolibarr_set_const($db, "MAIN_MODULE_MULTICOMPANY", ($state?'1':'0'), 'chaine', 0, '', 0),
            (string) $db->lasterror()
        );
        //====================================================================//
        // Force Disable MarketPlace Mode
        Splash::configuration()->MarketplaceMode = false;
        //====================================================================//
        // Verify Configuration
        $this->assertEquals($state, MultiCompany::isMultiCompany(true));
        $this->assertEquals(false, MultiCompany::isMarketplaceMode(true));
    }

    /**
     * Simulate Change of MultiCompany Entity
     *
     * @param int $entityId
     *
     * @return int
     */
    public function changeEntityId(int $entityId = 10): int
    {
        global $conf, $db, $user;

        //====================================================================//
        // Check MultiCompany Module
        $this->assertTrue(MultiCompany::isMultiCompany());

        //====================================================================//
        // Switch Entity
        $conf->entity = $entityId;
        $conf->setValues($db);
        $user->entity = $conf->entity;

        //====================================================================//
        // Disable BackLog for Dolibarr Version below 9.0
        if (Local::dolVersionCmp("9.0.0") < 0) {
            $conf->blockedlog->enabled = 0;
        }

        return $conf->entity;
    }

    /**
     * @param string $objectType
     *
     * @throws Exception
     *
     * @return bool|string
     */
    public function getNextObjectId(string $objectType)
    {
        //====================================================================//
        //   If Object List Not Loaded
        if (!isset($this->objectList[$objectType])) {
            //====================================================================//
            //   Get Object List from Module
            $list = Splash::object($objectType)->ObjectsList();

            //====================================================================//
            //   Get Object Count
            $this->objectCount[$objectType] = $list["meta"]["current"];

            //====================================================================//
            //   Remove Meta Datas form Objects List
            unset($list["meta"]);

            //====================================================================//
            //   Convert Store List
            $this->objectList[$objectType] = $list;
        }

        //====================================================================//
        //   Verify Objects List is Not Empty
        if ($this->objectCount[$objectType] <= 0) {
            $this->markTestSkipped('No Objects in Database.');
        }

        //====================================================================//
        //   Return First Object of List
        $nextObject = array_shift($this->objectList[$objectType]);

        return $nextObject["id"];
    }

    /**
     * @param string $objectType
     *
     * @throws Exception
     *
     * @return bool
     */
    public function verifyTestIsAllowed(string $objectType): bool
    {
        $definition = Splash::object($objectType)->Description();

        $this->assertNotEmpty($definition);
        //====================================================================//
        //   Verify Create is Allowed
        if (!$definition["allow_push_created"]) {
            return false;
        }
        //====================================================================//
        //   Verify Delete is Allowed
        if (!$definition["allow_push_deleted"]) {
            return false;
        }

        return true;
    }

    /**
     * @param string $objectType
     *
     * @throws Exception
     *
     * @return null|array
     */
    public function prepareForTesting(string $objectType): ?array
    {
        //====================================================================//
        //   Verify Test is Required
        if (!$this->verifyTestIsAllowed($objectType)) {
            return null;
        }

        //====================================================================//
        // Read Required Fields & Prepare Dummy Data
        //====================================================================//
        $write = false;
        $fields = Splash::object($objectType)->fields();
        foreach ($fields as $key => $field) {
            //====================================================================//
            // Skip Non Required Fields
            if (!$field['required']) {
                unset($fields[$key]);
            }
            //====================================================================//
            // Check if Write Fields
            if ($field['write']) {
                $write = true;
            }
        }

        //====================================================================//
        // If No Writable Fields
        if (!$write) {
            return null;
        }

        //====================================================================//
        // Lock New Objects To Avoid Action Commit
        Splash::object($objectType)->Lock();

        //====================================================================//
        // Clean Objects Committed Array
        CommitsManager::reset();

        return $this->fakeObjectData($fields);
    }
}
