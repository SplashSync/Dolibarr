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
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Local\Services\MultiCompany;
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
        Splash::log()->trace();
        //====================================================================//
        // Init Object
        $object = new Societe($db);
        //====================================================================//
        // Replace Multi-Company Module Global to Allow Fetch
        MultiCompany::replaceMcGlobal();
        //====================================================================//
        // Fetch Object
        if (1 != $object->fetch((int) $objectId)) {
            $this->catchDolibarrErrors($object);

            return Splash::log()->errTrace("Unable to load ThirdParty (".$objectId.").");
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!MultiCompany::isAllowed($object)) {
            return Splash::log()->errTrace("Unable to load ThirdParty (".$objectId."). MC");
        }
        //====================================================================//
        // Restore Multi-Company Module Global
        MultiCompany::restoreMcGlobal();

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
        Splash::log()->trace();
        //====================================================================//
        // Check Customer Required Fields are given
        if (false == $this->isReadyForCreate()) {
            return false;
        }
        //====================================================================//
        // Init Object
        $this->object = new Societe($db);
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setupBeforeCreate();
        //====================================================================//
        // Create Object In Database
        if ($this->object->create($user) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->errTrace("Unable to create new ThirdParty.");
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
        Splash::log()->trace();
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

            return Splash::log()
                ->errTrace("Unable to Update ThirdParty (".$this->object->id.")");
        }
        //====================================================================//
        // Update Object Extra Fields
        if ($this->object->insertExtraFields() <= 0) {
            $this->catchDolibarrErrors();
        }

        return $this->getObjectIdentifier();
    }

    /**
     * Delete requested Object
     *
     * @param null|string $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function delete($objectId = null)
    {
        global $db,$user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
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
        $object->id = (int) $objectId;
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        $object->entity = 0;
        if (!MultiCompany::isAllowed($object)) {
            return Splash::log()->errTrace("Unable to Delete ThirdParty (".$objectId.").");
        }
        //====================================================================//
        // Delete Object
        if ($object->delete((int) $objectId) <= 0) {
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

    /**
     * Ensure Required Fields for Create are Available
     *
     * @return bool
     */
    private function isReadyForCreate()
    {
        global $user;
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }

        //====================================================================//
        // Check Customer Name is given
        if (empty($this->in["name"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "name");
        }

        //====================================================================//
        // If Mandatory, Check Email is given
        if (Local::getParameter("SOCIETE_EMAIL_MANDATORY") && empty($this->in["email"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "email");
        }

        //====================================================================//
        // Check Required Id Prof are given
        for ($i = 1; $i < 5; $i++) {
            //====================================================================//
            // If Mandatory, Check IdProf is given
            if (Local::getParameter("SOCIETE_IDPROF".$i."_MANDATORY") && empty($this->in["idprof".$i])) {
                return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "idprof".$i);
            }
        }

        return true;
    }

    /**
     * Setup Required Fields Before ThirdParty Creation
     *
     * @return void
     */
    private function setupBeforeCreate()
    {
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setSimple("name", $this->in["name"]);

        //====================================================================//
        // Dolibarr infos
        $this->object->client = 1;        // 0=no customer, 1=customer, 2=prospect
        $this->object->prospect = 0;        // 0=no prospect, 1=prospect
        $this->object->fournisseur = 0;        // 0=no supplier, 1=supplier
        $this->object->code_client = "auto";   // If not erased, will be created by system
        $this->object->code_fournisseur = "auto";   // If not erased, will be created by system

        //====================================================================//
        // Optionnal Mandatory Fields
        //====================================================================//

        //====================================================================//
        // Required ThirdParty Email
        if (Local::getParameter("SOCIETE_EMAIL_MANDATORY")) {
            $this->setSimple("email", $this->in["email"]);
        }
        //====================================================================//
        // Required ThirdParty Id Profs
        for ($i = 1; $i < 5; $i++) {
            //====================================================================//
            // If Mandatory, Check IdProf is given
            if (Local::getParameter("SOCIETE_IDPROF".$i."_MANDATORY")) {
                $this->setSimple("idprof".$i, $this->in["idprof".$i]);
            }
        }
    }
}
