<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\ThirdParty;

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
        global $db;

        //====================================================================//
        // Check if Commit is Requierd
        if (!$this->isThirdPartyCommitRequired($action)) {
            return false;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters
        $this->Type      = "ThirdParty";
        $this->Id        = $object->id;
        if ('COMPANY_CREATE' == $action) {
            $this->Action    = SPL_A_CREATE;
            $this->Comment   = "Company Created on Dolibarr";
        } elseif ('COMPANY_MODIFY' == $action) {
            $this->Action    = SPL_A_UPDATE;
            $this->Comment   = "Company Updated on Dolibarr";
        } elseif ('COMPANY_DELETE' == $action) {
            $this->Action    = SPL_A_DELETE;
            $this->Comment   = "Company Deleted on Dolibarr";
        }
        
        return true;
    }

    /**
     * Check if Commit is Requiered
     *
     * @param string $action Code de l'evenement
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
