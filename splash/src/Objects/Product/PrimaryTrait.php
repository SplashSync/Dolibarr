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
        // Init Object
        $this->object = new Product($db);
        //====================================================================//
        // Fetch Object
        if (1 == $this->object->fetch(0, $ref)) {
            return $this->getObjectIdentifier();
        }

        return null;
    }
}
