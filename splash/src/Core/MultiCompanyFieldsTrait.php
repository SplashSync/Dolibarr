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

namespace Splash\Local\Core;

use Splash\Local\Services\MultiCompany;

/**
 * Access to Dolibarr Multi Company Fields
 */
trait MultiCompanyFieldsTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMultiCompanyFields()
    {
        //====================================================================//
        // Only if Multi Company Feature is Active
        if (!MultiCompany::isMultiCompany()) {
            return;
        }
        $marketplaceMode = MultiCompany::isMarketplaceMode();

        //====================================================================//
        // Dolibarr Entity ID
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("entity_id")
            ->name("Entity ID")
            ->group("Meta")
            ->microData("http://schema.org/Author", "identifier")
            ->isListed($marketplaceMode)
            ->isReadOnly(!$marketplaceMode)
            ->isNotTested()
        ;
        //====================================================================//
        // Dolibarr Entity Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("entity_code")
            ->name("Entity Code")
            ->group("Meta")
            ->microData("http://schema.org/Author", "alternateName")
            ->isReadOnly(!$marketplaceMode)
            ->isNotTested()
        ;
        //====================================================================//
        // Dolibarr Entity Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("entity_label")
            ->name("Entity Name")
            ->group("Meta")
            ->microData("http://schema.org/Author", "name")
            ->isReadOnly(!$marketplaceMode)
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
    protected function getMultiCompanyFields(string $key, string $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'entity_id':
                $this->out[$fieldName] = MultiCompany::getCurrentId();

                break;
            case 'entity_code':
                $entity = MultiCompany::getInfos(MultiCompany::getCurrentId());
                $this->out[$fieldName] = $entity ? $entity->code : "";

                break;
            case 'entity_label':
                $entity = MultiCompany::getInfos(MultiCompany::getCurrentId());
                $this->out[$fieldName] = $entity ? html_entity_decode($entity->label) : "";

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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setMultiCompanyFields($fieldName, $fieldData)
    {
        if (in_array($fieldName, array('entity_id', 'entity_code', 'entity_label'), true)) {
            unset($this->in[$fieldName]);
        }
    }
}
