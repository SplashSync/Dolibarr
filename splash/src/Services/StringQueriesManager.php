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

namespace Splash\Local\Services;

use Splash\Local\Services\Database\CategoriesQuery;
use Splash\Local\Services\Database\TableQuery;

/**
 * Helper to Manage Simplified String Query Request
 */
class StringQueriesManager
{
    /**
     * Get Values from ExtraFields Query Strings
     *
     * @return null|array<string, string>
     */
    public static function getValuesFromExtraFields(
        string $queryString,
        ?int $objectId = null,
        bool $translated = true
    ): ?array {
        //====================================================================//
        // This is a Category Query
        if ($catQuery = CategoriesQuery::fromExtraFieldsQuery($queryString)) {
            return $catQuery->getValues();
        }
        //====================================================================//
        // This is a Table Query
        if ($tableQuery = TableQuery::fromExtraFieldsQuery($queryString)) {
            return $tableQuery->getValues($objectId, $translated);
        }

        return null;
    }
}
