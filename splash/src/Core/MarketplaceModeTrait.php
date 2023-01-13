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

namespace Splash\Local\Core;

use Splash\Local\Services\MultiCompany;
use Splash\Models\Objects\IntelParserTrait;

trait MarketplaceModeTrait
{
    use IntelParserTrait{
        IntelParserTrait::set as protected coreSet;
    }

    //====================================================================//
    // Override Intel Parser
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function set(?string $objectId, array $objectData): ?string
    {
        //====================================================================//
        // Detect & Force Entity
        MultiCompany::setupForMarketplace($objectData);

        return $this->coreSet($objectId, $objectData);
    }
}
