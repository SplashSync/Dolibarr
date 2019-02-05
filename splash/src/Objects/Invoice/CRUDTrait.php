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

namespace Splash\Local\Objects\Invoice;

use User;
use DateTime;
use Facture;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;

/**
 * Dolibarr Customer Invoice CRUD Functions
 */
trait CRUDTrait
{
    /**
     * Load Request Object
     *
     * @param string $objectId Object id
     *
     * @return Facture|false
     */
    public function load($objectId)
    {
        global $db, $user, $conf;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Init Object
        $object = new Facture($db);
        //====================================================================//
        // Fatch Object
        if (1 != $object->fetch((int) $objectId)) {
            $this->catchDolibarrErrors($object);
            Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Current Entity is : " . $conf->entity);

            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Customer Invoice (" . $objectId . ")."
            );
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!self::isMultiCompanyAllowed($object)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Customer Invoice (" . $objectId . ")."
            );
        }
        $object->fetch_lines();
        $this->loadPayments($objectId);
        $this->initCustomerDetection();

        return $object;
    }

    /**
     * Create Request Object
     *
     * @return Facture|false
     */
    public function create()
    {
        global $db, $user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Order Date is given
        if (empty($this->in["date"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "date");
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Init Object
        $this->object = new Facture($db);
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $dateTime   =   new DateTime($this->in["date"]);
        $this->setSimple('date', $dateTime->getTimestamp());
        $this->setSimple('date_commande', $dateTime->getTimestamp());
        $this->doCustomerDetection($this->in);
        $this->setSimple("statut", Facture::STATUS_DRAFT);
        $this->object->statut = Facture::STATUS_DRAFT;
        $this->object->paye = 0;
        
        //====================================================================//
        // Create Object In Database
        if ($this->object->create($user) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "Unable to create new Customer Invoice. "
            );
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
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        if (!$needed) {
            return (string) $this->object->id;
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Update Object
        if ($this->object->update($user)  <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Update Customer Invoice (" . $this->object->id . ")"
            ) ;
        }
        //====================================================================//
        // Update Object Extra Fields
        if ($this->object->insertExtraFields()  <= 0) {
            $this->catchDolibarrErrors();
        }

        return (string) $this->object->id;
    }
    
    /**
     * Delete requested Object
     *
     * @param string $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function delete($objectId = null)
    {
        global $db,$user,$conf;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Object
        $object = new Facture($db);
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Debug Mode => Force Allow Delete
        if (defined("SPLASH_DEBUG") && !empty(SPLASH_DEBUG)) {
            $conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED = 1;
            $this->clearPayments((int) $objectId);
        }
        //====================================================================//
        // Set Object Id, fetch not needed
        $object->id = (int) $objectId;
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        $object->entity = null;
        if (!self::isMultiCompanyAllowed($object)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Delete Customer Invoice (" . $objectId . ")."
            );
        }
        //====================================================================//
        // Delete Object
        if ($object->delete($user) <= 0) {
            $this->catchDolibarrErrors($object);

            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Delete Customer Invoice (" . $objectId . ")"
            ) ;
        }

        return true;
    }
}
