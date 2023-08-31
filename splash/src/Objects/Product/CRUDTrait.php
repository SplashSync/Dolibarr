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
     * @return null|Product
     */
    public function load(string $objectId, bool $force = false): ?Product
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Loading Variant Parent Product is Forbidden!
        if (!$force && VariantsManager::hasProductVariants((int) $objectId)) {
            return Splash::log()->errNull(Splash::trans("ProductIsVariantBase"));
        }
        //====================================================================//
        // Init Object
        $object = new Product($db);
        //====================================================================//
        // Fetch Object
        if (1 != $object->fetch((int) $objectId)) {
            $this->catchDolibarrErrors($object);

            return Splash::log()->errNull("Unable to load Product (".$objectId.").");
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!MultiCompany::isAllowed($object)) {
            return Splash::log()->errNull("Unable to load Product (".$objectId.").");
        }
        //====================================================================//
        // Load Product Combinations
        $this->baseProduct = null;
        $this->combination = VariantsManager::getProductCombination((int) $objectId);
        if ($this->combination) {
            $this->baseProduct = new Product($db);
            $this->baseProduct->fetch($this->combination->fk_product_parent);
        }
        //====================================================================//
        // Since DOL 17 - Force Loading of Old Copy
        /** @phpstan-ignore-next-line */
        $object->oldcopy = dol_clone($object);

        return $object;
    }

    /**
     * Create Request Object
     *
     * @return null|Product
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function create(): ?Product
    {
        global $user, $langs;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Check Product Ref is given
        if (empty($this->in["ref"]) || !is_string($this->in["ref"])) {
            return Splash::log()->errNull(
                "ErrLocalFieldMissing",
                __CLASS__,
                __FUNCTION__,
                $langs->trans("ProductRef")
            );
        }
        //====================================================================//
        // Check Product Label is given
        $labelKey = self::isVariantEnabled() ? "base_label" : "label";
        if (empty($this->in[$labelKey]) || !is_string($this->in[$labelKey])) {
            return Splash::log()->errNull(
                "ErrLocalFieldMissing",
                __CLASS__,
                __FUNCTION__,
                $langs->trans("ProductLabel")
            );
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        if (!($user instanceof User) || empty($user->login)) {
            return Splash::log()->errNull(
                "ErrLocalUserMissing",
                __CLASS__,
                __FUNCTION__
            );
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
     * {@inheritDoc}
     */
    public function delete(string $objectId): bool
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
            return Splash::log()->err("Unable to Delete Product (".$objectId.").");
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
    public function getObjectIdentifier(): ?string
    {
        if (empty($this->object->id)) {
            return null;
        }

        return (string) $this->object->id;
    }

    /**
     * {@inheritDoc}
     */
    protected function update(bool $needed): ?string
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
            return Splash::log()->errNull("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Update Product Object
        if ($this->object->update($this->object->id, $user) <= 0) {
            $this->catchDolibarrErrors();

            return Splash::log()->errNull("Unable to Update Product (".$this->object->id.")");
        }
        //====================================================================//
        // Update Variant Product Specific Objects & Return Object Id
        if (!$this->updateVariantProduct()) {
            return null;
        }
        //====================================================================//
        // Update Object Extra Fields
        if ($this->object->insertExtraFields() <= 0) {
            $this->catchDolibarrErrors();
        }

        return $this->getObjectIdentifier();
    }

    /**
     * Create Simple Product
     *
     * @param string $ref      Product Reference
     * @param string $label    Product Label
     * @param bool   $triggers Product Label
     *
     * @return null|Product
     */
    protected function createSimpleProduct(string $ref, string $label, bool $triggers = true): ?Product
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
        $product->fk_default_warehouse = (int) $this->detectDefaultLocation(null);
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
            $this->catchDolibarrErrors($product);

            return Splash::log()->errNull("Unable to create new Product.");
        }

        return $product;
    }
}
