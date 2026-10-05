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

namespace Splash\Local\Objects\Product;

use Product;
use Splash\Client\Splash;
use Splash\Local\Services\ProductIdentifier;

/**
 * Products Search by Primary Field
 */
trait PrimaryTrait
{
    /**
     * {@inheritdoc}
     */
    public function getByPrimary(array $keys): ?string
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Detect Primary Keys
        $ref = $keys['ref'] ?? null;
        if (empty($ref)) {
            return null;
        }

        //====================================================================//
        // Search by Raw Reference
        // Kept first: references written without Dolibarr business objects
        // (SQL imports, older data) may be stored unsanitized.
        $this->object = new Product($db);
        if (1 == $this->object->fetch(0, $ref)) {
            return $this->getObjectIdentifier();
        }
        //====================================================================//
        // Search by Normalized Reference
        // Dolibarr sanitizes references on create & update ("A/B" => "A_B"):
        // without this, a product created by Splash is never found again.
        $normalizedRef = ProductIdentifier::normalizeRef($ref);
        if ($normalizedRef === $ref) {
            return null;
        }
        $this->object = new Product($db);
        if (1 == $this->object->fetch(0, $normalizedRef)) {
            return $this->getObjectIdentifier();
        }

        return null;
    }
}
