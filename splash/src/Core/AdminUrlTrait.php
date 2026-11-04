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

use Splash\Local\Dictionary\AdminUrls;

/**
 * Access to Dolibarr Back-Office Url of Objects
 *
 * Absolute url of the object card, for a logged-in user. Never embeds a session or a token.
 */
trait AdminUrlTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildAdminUrlFields(): void
    {
        //====================================================================//
        // Back-Office Url
        $this->fieldsFactory()->create(SPL_T_URL)
            ->identifier("admin_url")
            ->name("Admin URL")
            ->description("Back-office page of the object on this application")
            ->group("Meta")
            ->microData("http://schema.org/Thing", "adminUrl")
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
    protected function getAdminUrlFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'admin_url':
                $this->out[$fieldName] = $this->getAdminUrl();

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Get Absolute Back-Office Url of Current Object
     *
     * @return null|string
     */
    private function getAdminUrl(): ?string
    {
        $cardUrl = AdminUrls::BY_ELEMENT[(string) $this->object->element] ?? null;
        if (!$cardUrl || empty($this->object->id)) {
            return null;
        }

        return rtrim((string) DOL_MAIN_URL_ROOT, "/").$cardUrl.((int) $this->object->id);
    }
}
