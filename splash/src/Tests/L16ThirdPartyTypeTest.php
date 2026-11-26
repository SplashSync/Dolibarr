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
use PHPUnit\Framework\Assert;
use Societe;
use Splash\Client\Splash;
use Splash\Local\Services\ThirdPartyTypeManager;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify ThirdParty Type (Customer Type) Code & Name
 */
class L16ThirdPartyTypeTest extends ObjectsCase
{
    /**
     * Test ThirdParty Type is Written by Code & Read as Code and Name
     *
     * @throws Exception
     *
     * @return void
     */
    public function testWriteAndReadType(): void
    {
        $this->loadLocalTestSequence("Monolangual");
        $types = $this->getThirdPartyTypes();
        Assert::assertNotEmpty($types);
        $objectId = $this->createThirdParty();
        //====================================================================//
        // Write & Read Each Available Type
        foreach ($types as $code => $name) {
            $this->updateThirdParty($objectId, array("typent_code" => $code));
            Assert::assertSame($code, $this->readTypeCode($objectId));
            $data = $this->readFields($objectId);
            Assert::assertSame($code, $data["typent_code"]);
            Assert::assertSame($name, $data["typent_name"]);
        }
    }

    /**
     * Test ThirdParty Type is Resolved from its Exact Name
     *
     * @throws Exception
     *
     * @return void
     */
    public function testWriteTypeByName(): void
    {
        $this->loadLocalTestSequence("Monolangual");
        //====================================================================//
        // Only Non-Empty Names Identifying a Single Type, Never Equal to a Code
        $types = $this->getThirdPartyTypes();
        $counts = array_count_values(array_filter($types));
        $types = array_filter($types, static function (string $name) use ($counts, $types): bool {
            return !empty($name) && (1 == $counts[$name]) && !isset($types[$name]);
        });
        if (count($types) < 2) {
            $this->markTestSkipped("Not enough named ThirdParty Types to test resolution by name.");
        }
        $objectId = $this->createThirdParty();
        //====================================================================//
        // Write Each Type by Name, Read its Code
        foreach ($types as $code => $name) {
            $this->updateThirdParty($objectId, array("typent_code" => $name));
            Assert::assertSame((string) $code, $this->readTypeCode($objectId));
            Assert::assertSame($name, $this->readFields($objectId)["typent_name"]);
        }
    }

    /**
     * Test an Unknown Type Code is Skipped & an Empty Code Clears the Type
     *
     * @throws Exception
     *
     * @return void
     */
    public function testUnknownAndEmptyType(): void
    {
        $this->loadLocalTestSequence("Monolangual");
        $types = $this->getThirdPartyTypes();
        Assert::assertNotEmpty($types);
        $code = (string) array_key_first($types);
        $objectId = $this->createThirdParty();
        $this->updateThirdParty($objectId, array("typent_code" => $code));
        //====================================================================//
        // Unknown Code: Type is Left Unchanged
        $this->updateThirdParty($objectId, array("typent_code" => "SPL_UNKNOWN"));
        Assert::assertSame($code, $this->readTypeCode($objectId));
        //====================================================================//
        // Empty Code: Type is Cleared
        $this->updateThirdParty($objectId, array("typent_code" => ""));
        Assert::assertSame("", $this->readTypeCode($objectId));
        $data = $this->readFields($objectId);
        Assert::assertSame("", $data["typent_code"]);
        Assert::assertSame("", $data["typent_name"]);
    }

    /**
     * Get Active ThirdParty Types Names, Indexed by Code
     *
     * @return array<string, string>
     */
    private function getThirdPartyTypes(): array
    {
        return ThirdPartyTypeManager::getChoices();
    }

    /**
     * Read Raw Dolibarr ThirdParty Type Code
     *
     * @param string $objectId
     *
     * @return string
     */
    private function readTypeCode(string $objectId): string
    {
        global $db;

        $thirdParty = new Societe($db);
        Assert::assertGreaterThan(0, $thirdParty->fetch((int) $objectId));

        return empty($thirdParty->typent_id) ? "" : (string) $thirdParty->typent_code;
    }

    /**
     * Read ThirdParty Type Fields using Splash
     *
     * @param string $objectId
     *
     * @return array<string, mixed>
     */
    private function readFields(string $objectId): array
    {
        $data = Splash::object("ThirdParty")->get($objectId, array("typent_code", "typent_name"));
        Assert::assertIsArray($data);

        return $data;
    }

    /**
     * Create a ThirdParty
     *
     * @throws Exception
     *
     * @return string
     */
    private function createThirdParty(): string
    {
        $fields = $this->fakeFieldsList("ThirdParty", array(), true);
        $fakeData = $this->fakeObjectData($fields);

        Splash::object("ThirdParty")->lock();
        $objectId = Splash::object("ThirdParty")->set(null, $fakeData);
        Assert::assertIsString($objectId);
        $this->addTestedObject("ThirdParty", $objectId);

        return $objectId;
    }

    /**
     * Update a ThirdParty
     *
     * @param string                $objectId
     * @param array<string, string> $data
     *
     * @throws Exception
     *
     * @return void
     */
    private function updateThirdParty(string $objectId, array $data): void
    {
        Splash::object("ThirdParty")->lock($objectId);
        Assert::assertSame($objectId, Splash::object("ThirdParty")->set($objectId, $data));
    }
}
