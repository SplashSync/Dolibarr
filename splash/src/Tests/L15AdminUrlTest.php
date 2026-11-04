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
use Splash\Client\Splash;
use Splash\Local\Dictionary\AdminUrls;
use Splash\Models\Helpers\ObjectsHelper;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify Back-Office Urls of Objects
 */
class L15AdminUrlTest extends ObjectsCase
{
    /**
     * Test Back-Office Url of Each Object Type
     *
     * @dataProvider objectTypesProvider
     *
     * @param string $objectType
     * @param string $cardUrl
     *
     * @throws Exception
     *
     * @return void
     */
    public function testAdminUrl(string $objectType, string $cardUrl): void
    {
        $this->loadLocalTestSequence("Monolangual");

        //====================================================================//
        // Addresses & Documents are Attached to a ThirdParty
        $overrides = array();
        if (!in_array($objectType, array("ThirdParty", "Product"), true)) {
            $overrides["socid"] = ObjectsHelper::encode("ThirdParty", $this->createObject("ThirdParty"));
        }
        $objectId = $this->createObject($objectType, $overrides);
        $this->assertAdminUrl($objectType, $objectId, $cardUrl);
    }

    /**
     * Object Types & Expected Card Urls
     *
     * @return array<string, string[]>
     */
    public function objectTypesProvider(): array
    {
        return array(
            "ThirdParty" => array("ThirdParty", AdminUrls::SOCIETE),
            "Address" => array("Address", AdminUrls::CONTACT),
            "Product" => array("Product", AdminUrls::PRODUCT),
            "Order" => array("Order", AdminUrls::COMMANDE),
            "Invoice" => array("Invoice", AdminUrls::FACTURE),
            "CreditNote" => array("CreditNote", AdminUrls::FACTURE),
        );
    }

    /**
     * Verify Back-Office Url of an Object is Absolute & Points to its Card
     *
     * @param string $objectType
     * @param string $objectId
     * @param string $cardUrl
     *
     * @return void
     */
    private function assertAdminUrl(string $objectType, string $objectId, string $cardUrl): void
    {
        $data = Splash::object($objectType)->get($objectId, array("admin_url"));
        Assert::assertIsArray($data);
        $adminUrl = $data["admin_url"] ?? null;
        Assert::assertIsString($adminUrl);
        //====================================================================//
        // Absolute Url, Directly Usable in a Browser
        Assert::assertNotEmpty(parse_url($adminUrl, PHP_URL_SCHEME));
        Assert::assertNotEmpty(parse_url($adminUrl, PHP_URL_HOST));
        //====================================================================//
        // Points to the Object Card
        Assert::assertStringStartsWith(rtrim(DOL_MAIN_URL_ROOT, "/"), $adminUrl);
        Assert::assertStringEndsWith($cardUrl.$objectId, $adminUrl);
    }

    /**
     * Create an Object with Fake Data
     *
     * @param string               $objectType
     * @param array<string, string> $overrides
     *
     * @throws Exception
     *
     * @return string
     */
    private function createObject(string $objectType, array $overrides = array()): string
    {
        $fields = $this->fakeFieldsList($objectType, array(), true);
        $fakeData = $this->fakeObjectData($fields);
        foreach ($overrides as $fieldId => $value) {
            $fakeData[$fieldId] = $value;
        }

        Splash::object($objectType)->lock();
        $objectId = Splash::object($objectType)->set(null, $fakeData);
        Assert::assertIsString($objectId);
        $this->addTestedObject($objectType, $objectId);

        return $objectId;
    }
}
