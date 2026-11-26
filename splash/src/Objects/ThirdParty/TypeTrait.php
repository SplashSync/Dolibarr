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

use Splash\Core\SplashCore as Splash;
use Splash\Local\Services\ThirdPartyTypeManager;

/**
 * Dolibarr ThirdParty Type Fields (Customer Type)
 *
 * Third-party types are stored in Dolibarr dictionary llx_c_typent:
 * the code is the stable identifier, the name is only displayed.
 * The type code field also accepts the exact name of a type.
 */
trait TypeTrait
{
    /**
     * Build Type Fields using FieldFactory
     *
     * @return void
     */
    protected function buildTypeFields(): void
    {
        global $langs;

        //====================================================================//
        // ThirdParty Type Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("typent_code")
            ->name($langs->trans("ThirdPartyType")." (Code)")
            ->description("ThirdParty type code. The exact name of a type is also accepted.")
            ->addChoices(ThirdPartyTypeManager::getChoices())
        ;
        //====================================================================//
        // ThirdParty Type Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("typent_name")
            ->name($langs->trans("ThirdPartyType"))
            ->isReadOnly()
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
    protected function getTypeFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'typent_code':
                $this->out[$fieldName] = $this->getThirdPartyTypeCode();

                break;
            case 'typent_name':
                $this->out[$fieldName] = ThirdPartyTypeManager::getName($this->getThirdPartyTypeCode());

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setTypeFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'typent_code':
                $this->setThirdPartyType(trim((string) $fieldData));

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);
    }

    /**
     * Get Current ThirdParty Type Code
     *
     * @return string
     */
    private function getThirdPartyTypeCode(): string
    {
        return empty($this->object->typent_id) ? "" : (string) $this->object->typent_code;
    }

    /**
     * Update ThirdParty Type from a Received Code or Name
     *
     * An empty value clears the type, an unknown or inactive type is skipped.
     *
     * @param string $value Received ThirdParty Type Code or Name
     *
     * @return void
     */
    private function setThirdPartyType(string $value): void
    {
        //====================================================================//
        // Clear ThirdParty Type
        if ("" === $value) {
            if ("" !== $this->getThirdPartyTypeCode()) {
                $this->object->typent_id = 0;
                $this->object->typent_code = "";
                $this->needUpdate();
            }

            return;
        }
        //====================================================================//
        // Resolve Received Code or Name
        $code = ThirdPartyTypeManager::resolve($value);
        if (null === $code) {
            Splash::log()->war(sprintf("Unknown or inactive ThirdParty Type %s, skipped", $value));

            return;
        }
        //====================================================================//
        // No Changes
        if ($code === $this->getThirdPartyTypeCode()) {
            return;
        }
        $this->object->typent_id = (int) ThirdPartyTypeManager::getId($code);
        $this->object->typent_code = $code;
        $this->needUpdate();
    }
}
