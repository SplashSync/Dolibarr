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

namespace Splash\Local\Objects\SupplierInvoice;

use DateTime;
use Exception;
use FactureFournisseur;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\MultiCompany;
use User;

/**
 * Dolibarr Supplier Invoice CRUD Functions
 */
trait CRUDTrait
{
    /**
     * Load Request Object
     *
     * @param string $objectId Object id
     *
     * @return FactureFournisseur|false
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
        $object = new FactureFournisseur($db);
        //====================================================================//
        // Fetch Object
        if (1 != $object->fetch((int) $objectId)) {
            $this->catchDolibarrErrors($object);
            Splash::log()->errTrace("Current Entity is : ".$conf->entity);

            return Splash::log()->errTrace("Unable to load Supplier Invoice (".$objectId.").");
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!MultiCompany::isAllowed($object)) {
            return Splash::log()->errTrace("Unable to load Supplier Invoice (".$objectId.").");
        }
        //====================================================================//
        // Check Object Type Access (Invoices| Credit Notes)
        if (!in_array((int) $object->type, static::$dolibarrTypes, true)) {
            return Splash::log()->errTrace("Wrong Invoice Object Type.");
        }
        //====================================================================//
        // fetch SocId from fk_soc
        // @phpstan-ignore-next-line
        $this->object->socid = $this->object->fk_soc;

        $object->fetch_lines();
        $this->loadPayments($objectId);
        $this->initCustomerDetection();

        return $object;
    }

    /**
     * Create Request Object
     *
     * @throws Exception
     *
     * @return FactureFournisseur|false
     */
    public function create()
    {
        global $db, $user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Check Supplier Ref is given
        if (empty($this->in["ref_supplier"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "ref_supplier");
        }
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
        $this->object = new FactureFournisseur($db);
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $dateTime = new DateTime($this->in["date"]);
        $this->setSimple('date', $dateTime->getTimestamp());
        $this->doCustomerDetection($this->in);
        $this->setSimple("statut", FactureFournisseur::STATUS_DRAFT);
        $this->object->entity = MultiCompany::getCurrentId();
        $this->object->ref_supplier = $this->in["ref_supplier"];
        $this->object->statut = FactureFournisseur::STATUS_DRAFT;
        $this->object->paye = 0;
        //====================================================================//
        // Create Object In Database
        if ($this->object->create($user) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->errTrace("Unable to create new Supplier Invoice.");
        }

        return $this->object;
    }

    /**
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return false|string Object ID
     */
    public function update(bool $needed)
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
        // Migrate SocId to fk_soc
        // @phpstan-ignore-next-line
        $this->object->fk_soc = $this->object->socid;

        //====================================================================//
        // Update Object
        if ($this->object->update($user) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->errTrace("Unable to Update Supplier Invoice (".$this->object->id.")") ;
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
     * @param string $objectId Object ID.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function delete($objectId = null)
    {
        global $db,$user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Load Object
        $object = new FactureFournisseur($db);
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Set Object Id, fetch not needed
        $object->id = (int) $objectId;
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        $object->entity = 0;
        if (!MultiCompany::isAllowed($object)) {
            return Splash::log()->errTrace("Unable to Delete Supplier Invoice (".$objectId.").");
        }
        //====================================================================//
        // Delete Object
        if ($object->delete($user) <= 0) {
            $this->catchDolibarrErrors($object);

            return Splash::log()->errTrace("Unable to Delete Supplier Invoice (".$objectId.")");
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
        global $langs, $conf;
        //====================================================================//
        // Only if Feature is Not Disabled
        if (!empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            return;
        }
        //====================================================================//
        // Only if Supplier Invoice is Valid
        if (0 >= $this->object->statut) {
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
