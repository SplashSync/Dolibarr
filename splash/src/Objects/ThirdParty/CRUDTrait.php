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

use Societe;
use Splash\Core\SplashCore      as Splash;
use User;

/**
 * Dolibarr Contacts Address CRUD Functions
 */
trait CRUDTrait
{
    /**
     * Load Request Object
     *
     * @param string $objectId Object id
     *
     * @return false|Societe
     */
    public function load($objectId)
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Init Object
        $object = new Societe($db);
        //====================================================================//
        // Fetch Object
        if (1 != $object->fetch((int) $objectId)) {
            $this->catchDolibarrErrors($object);

            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load ThirdParty (" . $objectId . ")."
            );
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!self::isMultiCompanyAllowed($object)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load ThirdParty (" . $objectId . ")."
            );
        }

        return $object;
    }

    /**
     * Create Request Object
     *
     * @return false|Societe
     */
    public function create()
    {
        global $db, $user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Customer Name is given
        if (empty($this->in["name"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "name");
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Init Object
        $this->object = new Societe($db);
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setSimple("name", $this->in["name"]);
        //====================================================================//
        // Dolibarr infos
        $this->object->client             = 1;        // 0=no customer, 1=customer, 2=prospect
        $this->object->prospect           = 0;        // 0=no prospect, 1=prospect
        $this->object->fournisseur        = 0;        // 0=no supplier, 1=supplier
        $this->object->code_client        = "auto";   // If not erased, will be created by system
        $this->object->code_fournisseur   = "auto";   // If not erased, will be created by system
        //====================================================================//
        // Create Object In Database
        if ($this->object->create($user) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create new ThirdParty. ");
        }
        
        return $this->object;
    }
    
    /**
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return false|string Object Id
     */
    public function update($needed)
    {
        global $user;
        //====================================================================//
        // Compute Changes on Customer Name
        $this->updateFullName();
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        if (!$needed && !$this->isToUpdate()) {
            return $this->getObjectIdentifier();
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Update Object
        if ($this->object->update($this->object->id, $user, 1, 1) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Update ThirdParty (" . $this->object->id . ")"
            );
        }
        //====================================================================//
        // Update Object Extra Fields
        if ($this->object->insertExtraFields()  <= 0) {
            $this->catchDolibarrErrors();
        }

        return $this->getObjectIdentifier();
    }
    
    /**
     * Delete requested Object
     *
     * @param int $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function delete($objectId = null)
    {
        global $db,$user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        if (null === $objectId) {
            return false;
        }
        //====================================================================//
        // Load Object
        $object = new Societe($db);
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Set Object Id, fetch not needed
        $object->id = $objectId;
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        $object->entity = null;
        if (!self::isMultiCompanyAllowed($object)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Delete ThirdParty (" . $objectId . ")."
            );
        }
        //====================================================================//
        // Delete Object
//        $Arg1 = ( Local::dolVersionCmp("6.0.0") > 0 ) ? $user : 0;
        if ($object->delete($objectId) <= 0) {
            return $this->catchDolibarrErrors($object);
        }

        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getObjectIdentifier()
    {
        if (!isset($this->object->id)) {
            return false;
        }

        return (string) $this->object->id;
    }
}
