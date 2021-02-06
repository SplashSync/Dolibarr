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

namespace Splash\Local\Objects\Product;

use Product;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\MultiCompany;
use Splash\Local\Services\VariantsManager;
use User;

/**
 * Dolibarr Product CRUD Functions
 */
trait CRUDTrait
{
    /**
     * Load Request Object
     *
     * @param string $objectId Dolibarr Product Id
     * @param bool   $force    Force Loading of Variant Base Product
     *
     * @return false|Product
     */
    public function load($objectId, $force = false)
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Loading Variant Parent Product is Forbidden!
        if (!$force && VariantsManager::hasProductVariants((int) $objectId)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, Splash::trans("ProductIsVariantBase"));
        }
        //====================================================================//
        // Init Object
        $object = new Product($db);
        //====================================================================//
        // Fetch Object
        if (1 != $object->fetch((int) $objectId)) {
            $this->catchDolibarrErrors($object);

            return Splash::log()->errTrace("Unable to load Product (".$objectId.").");
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!MultiCompany::isAllowed($object)) {
            return Splash::log()->errTrace("Unable to load Product (".$objectId.").");
        }
        //====================================================================//
        // Load Product Combinations
        $this->combination = VariantsManager::getProductCombination((int) $objectId);
        if ($this->combination) {
            $this->baseProduct = new Product($db);
            $this->baseProduct->fetch($this->combination->fk_product_parent);
        }

        return $object;
    }

    /**
     * Create Request Object
     *
     * @return false|Product
     */
    public function create()
    {
        global $user, $langs;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Check Product Ref is given
        if (empty($this->in["ref"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, $langs->trans("ProductRef"));
        }
        //====================================================================//
        // Check Product Label is given
        $labelKey = self::isVariantEnabled() ? "base_label" : "label";
        if (empty($this->in[$labelKey])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, $langs->trans("ProductLabel"));
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }

        //====================================================================//
        // Create Variant Product
        if (isset($this->in["attributes"]) && !empty($this->in["attributes"])) {
            return $this->createVariantProduct();
        }

        //====================================================================//
        // Create Simple Product
        return $this->createSimpleProduct($this->in["ref"], $this->in[$labelKey], true);
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
            Splash::log()->deb("Product Update not Needed");

            return $this->getObjectIdentifier();
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Update Product Object
        if ($this->object->update($this->object->id, $user) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->errTrace("Unable to Update Product (".$this->object->id.")");
        }
        //====================================================================//
        // Update Variant Product Specific Objects & Return Object Id
        if (false == $this->updateVariantProduct()) {
            return false;
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
     * @param string $objectId Object Id.  If NULL, Object needs to be created.
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
        $object = new Product($db);
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
            return Splash::log()->errTrace("Unable to Delete Product (".$objectId.").");
        }
        //====================================================================//
        // Load Product Combination
        $combination = VariantsManager::getProductCombination((int) $objectId);
        //====================================================================//
        // Fetch Object
        if ($object->fetch((int) $objectId) <= 0) {
            return $this->catchDolibarrErrors($object);
        }
        //====================================================================//
        // Delete Object
        if ($object->delete($user) <= 0) {
            return $this->catchDolibarrErrors($object);
        }
        //====================================================================//
        // Parent Object if Last Product Variant
        if (empty($combination)) {
            return true;
        }
        if (0 == $combination->countNbOfCombinationForFkProductParent($combination->fk_product_parent)) {
            //====================================================================//
            // Also Delete Parent Product
            $object->id = $combination->fk_product_parent;
            if ($object->delete($user) <= 0) {
                return $this->catchDolibarrErrors($object);
            }
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
     * Create Simple Product
     *
     * @param string $ref      Product Reference
     * @param string $label    Product Label
     * @param bool   $triggers Product Label
     *
     * @return false|Product
     */
    protected function createSimpleProduct($ref, $label, $triggers = true)
    {
        global $db, $user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Init Object
        $product = new Product($db);
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $product->ref = $ref;
        $product->label = $label;
        $product->weight = 0;
        //====================================================================//
        // Detect Location Id from Default Configuration
        $product->fk_default_warehouse = $this->detectDefaultLocation(null);
        //====================================================================//
        // Required For Dolibarr Below 3.6
        $product->type = 0;
        //====================================================================//
        // Required For Dolibarr BarCode Module
        $product->barcode = "-1";
        //====================================================================//
        // Create Object In Database
        /** @var User $user */
        if ($product->create($user, $triggers ? 0 : 1) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create new Product. ");
        }

        return $product;
    }
}
