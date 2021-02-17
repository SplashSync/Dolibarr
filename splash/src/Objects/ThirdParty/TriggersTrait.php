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

use Societe;

/**
 * TrirdParty Dolibarr Trigger trait
 */
trait TriggersTrait
{
    /**
     * Prepare Object Commit for ThirdParty
     *
     * @param string $action Code de l'evenement
     * @param object $object Objet concerne
     *
     * @return bool Commit is required
     */
    protected function doThirdPartyCommit($action, $object)
    {
        //====================================================================//
        // Check if Commit is Required
        if (!$this->isThirdPartyCommitRequired($action)) {
            return false;
        }
        if (!($object instanceof Societe)) {
            return false;
        }
        //====================================================================//
        // Store Global Action Parameters
        $this->objectType = "ThirdParty";
        $this->objectId = (string) $object->id;
        if ('COMPANY_CREATE' == $action) {
            $this->action = SPL_A_CREATE;
            $this->comment = "Company Created on Dolibarr";
        } elseif ('COMPANY_MODIFY' == $action) {
            $this->action = SPL_A_UPDATE;
            $this->comment = "Company Updated on Dolibarr";
        } elseif ('COMPANY_DELETE' == $action) {
            $this->action = SPL_A_DELETE;
            $this->comment = "Company Deleted on Dolibarr";
        }

        return true;
    }

    /**
     * Check if Commit is Required
     *
     * @param string $action Code de l'évènement
     *
     * @return bool
     */
    private function isThirdPartyCommitRequired($action)
    {
        return in_array($action, array(
            'COMPANY_CREATE',
            'COMPANY_MODIFY',
            'COMPANY_DELETE'
        ), true);
    }
}
