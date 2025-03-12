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

namespace Splash\Local\Services\Database;

use Categorie;

/**
 * Query Helper to search on Categories
 */
class CategoriesQuery
{
    const   TABLE_NAME = "categorie:";

    /**
     * Category Code to Use
     *
     * @var string
     */
    private string $catCode;

    /**
     * Categories Filters
     *
     * @var string
     */
    private string $catFilters;

    public function __construct(
        string $catId,
        ?string $catFilters = null
    ) {
        $this->catCode = self::toCategoryCode($catId);
        $this->catFilters = $catFilters ?? "";
    }

    /**
     * Get Values from Database.
     */
    public function getValues(): array
    {
        global $db;

        require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
        $form = new \Form($db);

        /** @var array|string $data */
        $data = $form->select_all_categories($this->catCode, '', 'parent', 64, $this->catFilters, 1, 1);

        return is_array($data) ? $data : array();
    }

    /**
     * Create a Category Query From Extra Fields Query String
     */
    public static function fromExtraFieldsQuery(string $queryString): ?self
    {
        //====================================================================//
        // Check if the given query represents a category request.
        if (!$queryArray = self::isExtraFieldsQueryString($queryString)) {
            return null;
        }

        return new self(
            $queryArray[5],
            $queryArray[6] ?? null
        );
    }

    /**
     * Determines if the given query represents a category request.
     */
    private static function isExtraFieldsQueryString(string $queryString): ?array
    {
        if (0 !== strpos($queryString, self::TABLE_NAME)) {
            return null;
        }
        $queryArray = explode(":", $queryString);
        if (count($queryArray) <= 5) {
            return null;
        }

        return $queryArray;
    }

    /**
     * Convert Category ID to Category Code
     *
     * @SuppressWarnings(CamelCaseVariableName)
     */
    private static function toCategoryCode(string $catId): string
    {
        require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

        return Categorie::$MAP_ID_TO_CODE[$catId] ?? $catId;
    }
}
