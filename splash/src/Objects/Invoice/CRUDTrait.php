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

namespace Splash\Local\Objects\Invoice;

use DateTime;
use Facture;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Objects\CreditNote;
use Splash\Local\Services\MultiCompany;
use User;

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
        Splash::log()->trace();
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
            Splash::log()->errTrace("Current Entity is : ".$conf->entity);

            return Splash::log()->errTrace("Unable to load Customer Invoice (".$objectId.").");
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!MultiCompany::isAllowed($object)) {
            return Splash::log()->errTrace("Unable to load Customer Invoice (".$objectId.").");
        }
        //====================================================================//
        // Check Object Type Access (Invoices| Credit Notes)
        if (!in_array((int) $object->type, static::$dolibarrTypes, true)) {
            return Splash::log()->errTrace("Wrong Invoice Object Type.");
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
        Splash::log()->trace();
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
        $dateTime = new DateTime($this->in["date"]);
        $this->setSimple('date', $dateTime->getTimestamp());
        $this->setSimple('date_commande', $dateTime->getTimestamp());
        $this->doCustomerDetection($this->in);
        $this->setSimple("statut", Facture::STATUS_DRAFT);
        $this->object->statut = Facture::STATUS_DRAFT;
        $this->object->paye = 0;
        //====================================================================//
        // If Credit Note => Setup Type
        if ($this instanceof CreditNote) {
            $this->object->type = Facture::TYPE_CREDIT_NOTE;
        }

        //====================================================================//
        // Create Object In Database
        if ($this->object->create($user) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->errTrace("Unable to create new Customer Invoice.");
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
        Splash::log()->trace();
        if (!$needed) {
            return $this->getObjectIdentifier();
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Update Object
        if ($this->object->update($user) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->errTrace("Unable to Update Customer Invoice (".$this->object->id.")") ;
        }
        //====================================================================//
        // Update Object Extra Fields
        if ($this->object->insertExtraFields() <= 0) {
            $this->catchDolibarrErrors();
        }
        //====================================================================//
        // Update Object Pdf Document
        $this->updateObjectPdf();

        return $this->getObjectIdentifier();
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
        Splash::log()->trace();
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
        if (Splash::isDebugMode()) {
            $facture = $this->load((string) $objectId);
            if ($facture && ($facture->statut > 1)) {
                $this->object = $facture;
                $this->setStatusDraft();
            }
            $conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED = 1;
            $this->clearPayments((int) $objectId);
        }
        //====================================================================//
        // Set Object Id, fetch not needed
        $object->id = (int) $objectId;
        //====================================================================//
        // If Credit Note => Setup Type
        if ($this instanceof CreditNote) {
            $object->type = Facture::TYPE_CREDIT_NOTE;
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        $object->entity = 0;
        if (!MultiCompany::isAllowed($object)) {
            return Splash::log()->errTrace("Unable to Delete Customer Invoice (".$objectId.").");
        }
        //====================================================================//
        // Delete Object
        if ($object->delete($user) <= 0) {
            $this->catchDolibarrErrors($object);

            return Splash::log()->errTrace("Unable to Delete Customer Invoice (".$objectId.")");
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
     * Re-Generate Invoice Pdf if Needed
     *
     * @return void
     */
    public function updateObjectPdf()
    {
        global $conf, $langs;
        //====================================================================//
        // Only if Feature is Not Disabled
        if (!empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            return;
        }
        //====================================================================//
        // Only if Invoice is Valid
        if ($this->object->statut <= 0) {
            return;
        }
        //====================================================================//
        // Reload to get new records
        $this->object->fetch($this->object->id);
        //====================================================================//
        // Generate Pdf Document
        $result = $this->object->generateDocument("", $langs);
        if ($result < 0) {
            $this->catchDolibarrErrors();
        }
    }
}
