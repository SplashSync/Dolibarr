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

namespace Splash\Local\Objects\ThirdParty;

use Societe;
use Splash\Client\Splash;

/**
 * ThirdParty Search by Primary Field
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
        $name = $keys['name'] ?? '';
        $email = $keys['email'] ?? '';
        if (!(empty($name) xor empty($email))) {
            return null;
        }

        //====================================================================//
        // Init Object
        $this->object = new Societe($db);
        //====================================================================//
        // Fetch Object
        if (1 == $this->object->fetch(0, $name, '', '', '', '', '', '', '', '', $email)) {
            return $this->getObjectIdentifier();
        }

        return null;
    }
}
