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

namespace Splash\Local\Objects\ThirdParty;

use Splash\Local\Services\CategoryManager;
use Splash\Models\Helpers\InlineHelper;

/**
 * Access to Customers Categories
 */
trait CategoriesTrait
{
    /**
     * Customer Category Type Code
     *
     * @var string
     */
    private static $customerType = 'customer';

    /**
     * Supplier Category Type Code
     *
     * @var string
     */
    private static $supplierType = 'supplier';

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCategoriesFields(): void
    {
        global $langs;

        $langs->load('categories');

        //====================================================================//
        // Customer Categories
        $this->fieldsFactory()->create(SPL_T_INLINE)
            ->identifier("categories")
            ->name($langs->trans("CustomersProspectsCategoriesShort"))
            ->description($langs->trans("CatCusList"))
            ->microData("http://schema.org/Organization", "category")
            ->addChoices(CategoryManager::getAllCategoriesChoices(self::$customerType))
            ->setPreferNone()
            ->isNotTested()
        ;

        //====================================================================//
        // Supplier Categories
        $this->fieldsFactory()->create(SPL_T_INLINE)
            ->identifier("supplier_categories")
            ->name($langs->trans("SuppliersCategoriesShort"))
            ->description($langs->trans("CatSupList"))
            ->microData("http://schema.org/Organization", "supplierCategory")
            ->addChoices(CategoryManager::getAllCategoriesChoices(self::$supplierType))
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
    protected function getCategoriesFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'categories':
                $this->out[$fieldName] = InlineHelper::fromArray(
                    CategoryManager::getCategories($this->object, self::$customerType)
                );

                break;
            case 'supplier_categories':
                $this->out[$fieldName] = InlineHelper::fromArray(
                    CategoryManager::getCategories($this->object, self::$supplierType)
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
    protected function setCategoriesFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'categories':
                CategoryManager::setCategories(
                    $this->object,
                    self::$customerType,
                    InlineHelper::toArray($fieldData)
                );

                break;
            case 'supplier_categories':
                CategoryManager::setCategories(
                    $this->object,
                    self::$supplierType,
                    InlineHelper::toArray($fieldData)
                );

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);
    }
}
