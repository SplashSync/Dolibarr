<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Product;

use Splash\Local\Services\CategoryManager;
use Splash\Models\Helpers\InlineHelper;

trait CategoriesTrait
{
    /** @var string */
    protected static $categoryType = 'product';

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCategoriesFields()
    {
        //====================================================================//
        // Categories Slugs
        $this->fieldsFactory()->create(SPL_T_INLINE)
            ->identifier("categories")
            ->name("Category")
            ->description("Category Codes")
            ->microData("http://schema.org/Product", "category")
            ->addChoices(CategoryManager::getAllCategoriesChoices(self::$categoryType))
            ->setPreferNone()
            ->isNotTested()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCategoriesFields(string $key, string $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'categories':
                $this->out[$fieldName] = InlineHelper::fromArray(
                    CategoryManager::getCategories($this->object, self::$categoryType)
                );

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setCategoriesFields(string $fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'categories':
                CategoryManager::setCategories(
                    $this->object,
                    self::$categoryType,
                    InlineHelper::toArray($fieldData)
                );

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);
    }
}
