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

namespace Splash\Local\Tests\Tools;

use Exception;
use PHPUnit\Framework\Assert;
use Splash\Client\Splash;
use Splash\Models\Objects\PrimaryKeysAwareInterface;

/**
 * Shared Helpers for Product References Tests
 */
trait ProductRefsTrait
{
    /**
     * Raw References for Storage Tests
     *
     * @return array<string, array{string}>
     */
    public function storageProvider(): array
    {
        $uniq = substr(uniqid(), -6);

        return array(
            'slash' => array("NORM/".$uniq."/A"),
            'space' => array("NORM ".$uniq." B"),
            'path chars' => array("NORM:".$uniq."*C?"),
        );
    }

    /**
     * Get Product Splash Object, as Primary Keys Aware
     *
     * @throws Exception
     *
     * @return PrimaryKeysAwareInterface
     */
    private function getPrimaryAware(): PrimaryKeysAwareInterface
    {
        $object = Splash::object("Product");
        Assert::assertInstanceOf(PrimaryKeysAwareInterface::class, $object);

        return $object;
    }

    /**
     * Create a Product through Splash with a Raw Reference
     *
     * @param string $ref Product reference, as received from the source
     *
     * @throws Exception
     *
     * @return string Created Product ID
     */
    private function createProduct(string $ref): string
    {
        Splash::object("Product")->lock();
        // Label is "base_label" when Variants are Enabled, "label" otherwise
        $productId = Splash::object("Product")->set(null, array(
            "ref" => $ref,
            "label" => "Reference Normalization Test",
            "base_label" => "Reference Normalization Test",
        ));
        Assert::assertIsString($productId);
        Assert::assertNotEmpty($productId);

        return $productId;
    }

    /**
     * Delete a Product Created by the Test
     *
     * @param string $productId Product ID
     *
     * @throws Exception
     *
     * @return void
     */
    private function deleteProduct(string $productId): void
    {
        Splash::object("Product")->lock($productId);
        Assert::assertTrue(Splash::object("Product")->delete($productId));
    }
}
