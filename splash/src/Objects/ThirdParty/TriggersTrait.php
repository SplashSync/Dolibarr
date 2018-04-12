<?php

/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace Splash\Local\Objects\ThirdParty;

/**
 * @abstract    TrirdParty Dolibarr Trigger trait
 */
trait TriggersTrait
{
    
    /**
     *      @abstract      Prepare Object Commit for ThirdParty
     *
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     *
     *      @return bool        Commit is required
     */
    protected function doThirdPartyCommit($Action, $Object)
    {
        global $db;

        //====================================================================//
        // Check if Commit is Requierd
        if (!$this->isThirdPartyCommitRequired($Action)) {
            return false;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters
        $this->Type      = "ThirdParty";
        $this->Id        = $Object->id;
        if ($Action == 'COMPANY_CREATE') {
            $this->Action    = SPL_A_CREATE;
            $this->Comment   = "Company Created on Dolibarr";
        } elseif ($Action == 'COMPANY_MODIFY') {
            $this->Action    = SPL_A_UPDATE;
            $this->Comment   = "Company Updated on Dolibarr";
        } elseif ($Action == 'COMPANY_DELETE') {
            $this->Action    = SPL_A_DELETE;
            $this->Comment   = "Company Deleted on Dolibarr";
        }
        
        return true;
    }

    /**
     * @abstract      Check if Commit is Requiered
     *
     * @param  string      $Action      Code de l'evenement
     *
     * @return bool
     */
    private function isThirdPartyCommitRequired($Action)
    {
        return in_array($Action, array(
            'COMPANY_CREATE',
            'COMPANY_MODIFY',
            'COMPANY_DELETE'
        ));
    }
}
