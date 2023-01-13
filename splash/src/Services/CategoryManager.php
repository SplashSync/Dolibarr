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

use ArrayObject;
use Categorie;
use CommonObject;

/**
 * Product Categories Manager
 */
class CategoryManager
{
    /**
     * Full Categories List Cache
     *
     * @var array<string, Categorie[]>
     */
    private static array $cache = array();

    /**
     * Service Constructor
     *
     * @return void
     */
    public static function init(): void
    {
        //====================================================================//
        // Load Categories Class
        dol_include_once("/categories/class/categorie.class.php");
    }

    /**
     * Get Object Categories List
     *
     * @param CommonObject $object
     * @param string       $type
     *
     * @return array<int, string>
     */
    public static function getCategories(CommonObject $object, string $type): array
    {
        global $db;
        self::init();
        //====================================================================//
        // Load Categories Objects List
        $categories = (new Categorie($db))->containing($object->id, $type, 'object');
        if (!is_array($categories)) {
            return array();
        }
        //====================================================================//
        // Parse Categories List
        return self::toLabels($categories);
    }

    /**
     * Set Categories List for Object
     *
     * @param CommonObject      $object
     * @param string            $type
     * @param array|ArrayObject $data
     *
     * @return void
     */
    public static function setCategories(CommonObject $object, string $type, $data)
    {
        //====================================================================//
        // Load Product Current Categories List
        $current = self::getCategories($object, $type);
        //====================================================================//
        // Detect ArrayObjects
        $data = ($data instanceof ArrayObject) ? $data->getArrayCopy() : $data;
        //====================================================================//
        // Build list of New Categories
        foreach ($data as $categoryLabel) {
            //====================================================================//
            // Already Associated
            if (in_array($categoryLabel, $current, true)) {
                continue;
            }
            //====================================================================//
            // Search for Category Id
            $category = self::getCategory($type, $categoryLabel);
            if ($category) {
                $category->add_type($object, $type);
            }
        }
        //====================================================================//
        // Walk on Current List for REMOVE
        foreach ($current as $categoryLabel) {
            //====================================================================//
            // NOT Already Associated
            if (!in_array($categoryLabel, $data, true)) {
                //====================================================================//
                // Search for Category Id
                $category = self::getCategory($type, $categoryLabel);
                if ($category) {
                    $category->del_type($object, $type);
                }
            }
        }
    }

    /**
     * Search in All Categories for a Given Name/Code
     *
     * @param string $type
     * @param string $label
     *
     * @return null|Categorie
     */
    public static function getCategory(string $type, string $label): ?Categorie
    {
        //====================================================================//
        // Map List to Requested Field
        foreach (self::loadAllCategories($type) as $category) {
            if (strtolower($category->label) == strtolower($label)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Get List of All Categories
     *
     * @param string $type
     *
     * @return array<string, string>
     */
    public static function getAllCategoriesChoices(string $type): array
    {
        return self::toChoices(self::loadAllCategories($type));
    }

    /**
     * Get List of All Categories
     *
     * @param string $type
     *
     * @return Categorie[]
     */
    private static function loadAllCategories(string $type): array
    {
        global $db;
        //====================================================================//
        // Cache Not  Loaded
        if (!isset(self::$cache[$type])) {
            self::init();
            //====================================================================//
            // Load All Categories
            /** @phpstan-ignore-next-line */
            $categories = (new Categorie($db))->get_all_categories($type);
            self::$cache[$type] = is_array($categories) ? $categories : array();
        }

        return self::$cache[$type];
    }

    /**
     * Extract Categories Labels List
     *
     * @param Categorie[] $categories
     *
     * @return array<int, string>
     */
    private static function toLabels(array $categories): array
    {
        //====================================================================//
        // Parse Categories List
        $categoriesList = array();
        foreach ($categories as $category) {
            $categoriesList[$category->id] = $category->label;
        }

        return $categoriesList;
    }

    /**
     * Extract Categories Labels List
     *
     * @param Categorie[] $categories
     *
     * @return array<string, string>
     */
    private static function toChoices(array $categories): array
    {
        //====================================================================//
        // Parse Categories List
        $categoriesList = array();
        foreach ($categories as $category) {
            $categoriesList[$category->label] = $category->label;
        }

        return $categoriesList;
    }
}
