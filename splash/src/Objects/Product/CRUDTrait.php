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

namespace Splash\Local\Objects\Product;

use Product;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
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
     * @param string $objectId Object id
     *
     * @return false|Product
     */
    public function load($objectId)
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Init Object
        $object = new Product($db);
        //====================================================================//
        // Fetch Object
        if (1 != $object->fetch((int) $objectId)) {
            $this->catchDolibarrErrors($object);

            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Product (" . $objectId . ")."
            );
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!self::isMultiCompanyAllowed($object)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Product (" . $objectId . ")."
            );
        }
        
        //====================================================================//
        // Load Product Combinations
        $this->combination = VariantsManager::getProductCombination((int) $objectId);
        if($this->combination) {
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
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Product Ref is given
        if (empty($this->in["ref"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, $langs->trans("ProductRef"));
        }
        //====================================================================//
        // Check Product Label is given
        if (empty($this->in["label"])) {
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
        return $this->createSimpleProduct($this->in["ref"], $this->in["label"], true);
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

            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Update Product (" . $this->object->id . ")"
            ) ;
        }
        
        //====================================================================//
        // Update Base Product
        if ($this->isToUpdate("baseProduct")) {
            if ($this->baseProduct->update($this->baseProduct->id, $user) <= 0) {
                $this->catchDolibarrErrors($this->baseProduct);

                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    " Unable to Update Base Product (" . $this->baseProduct->id . ")"
                ) ;
            }
        } 
        
        //====================================================================//
        // Update Product Combination
        if ($this->isToUpdate("combination")) {
            if ($this->combination->update($user) <= 0) {
                $this->catchDolibarrErrors($this->combination);

                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    " Unable to Update Product Combination (" . $this->combination->id . ")"
                ) ;
            }
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
     * @param string $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function delete($objectId = null)
    {
        global $db,$user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
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
        $object->entity = null;
        if (!self::isMultiCompanyAllowed($object)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Delete Product (" . $objectId . ")."
            );
        }
        //====================================================================//
        // Load Product Combination
        $combination = VariantsManager::getProductCombination($objectId);
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
        if ($combination->countNbOfCombinationForFkProductParent($combination->fk_product_parent) == 0) {
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
     * @param string $ref   Product Reference
     * @param string $label Product Label
     * @param bool $triggers Product Label
     *
     * @return false|Product
     */
    protected function createSimpleProduct($ref, $label, $triggers = true)
    {
        global $db, $user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Init Object
        $this->object = new Product($db);
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setSimple("ref", $ref);
        $this->setSimple("label", $label);
        $this->setSimple("weight", 0);
        //====================================================================//
        // Required For Dolibarr Below 3.6
        $this->object->type        = 0;
        //====================================================================//
        // Required For Dolibarr BarCode Module
        $this->object->barcode     = -1;
        //====================================================================//
        // Create Object In Database
        /** @var User $user */
        if ($this->object->create($user, $triggers ? 0 : 1) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create new Product. ");
        }
        
        return $this->object;
    }
}
