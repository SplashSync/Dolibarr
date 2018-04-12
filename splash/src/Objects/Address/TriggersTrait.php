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

namespace Splash\Local\Objects\Address;

/**
 * @abstract    Address Dolibarr Trigger trait
 */
trait TriggersTrait
{
    
    
    /**
     *      @abstract      Prepare Object Commit for Address
     *
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     *
     *      @return bool        Commit is required
     */
    protected function doAddressCommit($Action, $Object)
    {
        global $db;
        
        //====================================================================//
        // Check if Commit is Requierd
        if (!$this->isAddressCommitRequired($Action)) {
            return false;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters
        $this->Type      = "Address";
        $this->Id        = $Object->id;
        
        if ($Action        == 'CONTACT_CREATE') {
            $this->Action   = SPL_A_CREATE;
            $this->Comment  = "Contact Created on Dolibarr";
        } elseif ($Action  == 'CONTACT_MODIFY') {
            $this->Action   = SPL_A_UPDATE;
            $this->Comment  = "Contact Updated on Dolibarr";
        } elseif ($Action  == 'CONTACT_DELETE') {
            $this->Action   = SPL_A_DELETE;
            $this->Comment  = "Contact Deleted on Dolibarr";
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
    private function isAddressCommitRequired($Action)
    {
        return in_array($Action, array(
            'CONTACT_CREATE',
            'CONTACT_MODIFY',
            'CONTACT_DELETE'
        ));
    }
}
