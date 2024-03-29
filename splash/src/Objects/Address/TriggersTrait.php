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

namespace Splash\Local\Objects\Address;

use Contact;
use Splash\Client\Splash;

/**
 * Address Dolibarr Trigger trait
 */
trait TriggersTrait
{
    /**
     * Prepare Object Commit for Address
     *
     * @param string $action Event Code
     * @param object $object Impacted Objet
     *
     * @return bool Commit is required
     */
    protected function doAddressCommit(string $action, object $object): bool
    {
        //====================================================================//
        // Check if Commit is Required
        if (!$this->isAddressCommitRequired($action)) {
            return false;
        }
        if (!($object instanceof Contact)) {
            return false;
        }
        //====================================================================//
        // Store Global Action Parameters
        $this->objectType = "Address";
        $this->objectId = (string) $object->id;

        if ('CONTACT_CREATE' == $action) {
            $this->action = SPL_A_CREATE;
            $this->comment = "Contact Created on Dolibarr";
        } elseif ('CONTACT_MODIFY' == $action) {
            $this->action = SPL_A_UPDATE;
            $this->comment = "Contact Updated on Dolibarr";
        } elseif ('CONTACT_DELETE' == $action) {
            $this->action = SPL_A_DELETE;
            $this->comment = "Contact Deleted on Dolibarr";
        }

        return true;
    }

    /**
     * Prepare Object Secondary Commit for Address
     *
     * @param string $action Event Code
     * @param object $object Impacted Objet
     *
     * @return bool Commit is required
     */
    protected function doAddressSecondaryCommit(string $action, object $object): bool
    {
        //====================================================================//
        // Check if Feature is Active
        if (empty(Splash::configuration()->PropagateContactCommits)) {
            return false;
        }
        //====================================================================//
        // Check if Commit is Required
        if (!$this->isAddressCommitRequired($action)) {
            return false;
        }
        //====================================================================//
        // Check Object
        if (!($object instanceof Contact) || empty($object->socid)) {
            return false;
        }
        //====================================================================//
        // Store Global Action Parameters
        $this->objectType = "ThirdParty";
        $this->objectId = (string) $object->socid;
        $this->action = SPL_A_UPDATE;

        if ('CONTACT_CREATE' == $action) {
            $this->comment = "Contact Created on Dolibarr";
        } elseif ('CONTACT_MODIFY' == $action) {
            $this->comment = "Contact Updated on Dolibarr";
        } elseif ('CONTACT_DELETE' == $action) {
            $this->comment = "Contact Deleted on Dolibarr";
        }

        return true;
    }

    /**
     * Check if Commit is Required
     *
     * @param string $action Event Code
     *
     * @return bool
     */
    private function isAddressCommitRequired(string $action): bool
    {
        return in_array($action, array(
            'CONTACT_CREATE',
            'CONTACT_MODIFY',
            'CONTACT_DELETE'
        ), true);
    }
}
