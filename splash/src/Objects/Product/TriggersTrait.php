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

namespace Splash\Local\Objects\Product;

use Splash\Client\Splash;

/**
 * @abstract    Product Dolibarr Trigger trait
 */
trait TriggersTrait
{
    
    /**
     *      @abstract      Prepare Object Commit for Product
     *
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     *
     *      @return bool        Commit is required
     */
    protected function doProductCommit($Action, $Object)
    {
        global $db;
        
        //====================================================================//
        // Filter Triggered Actions
        if (!$this->isProductCommitRequired($Action)) {
            return false;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters
        $this->setProductObjectId($Object);
        $this->setProductParameters($Action);
        
        return true;
    }

    /**
     * @abstract      Check if Commit is Requiered
     *
     * @param  string      $Action      Code de l'evenement
     *
     * @return bool
     */
    private function isProductCommitRequired($Action)
    {
        return in_array($Action, array(
            'PRODUCT_CREATE',
            'PRODUCT_MODIFY',
            'PRODUCT_DELETE',
            'PRODUCT_SET_MULTILANGS',
            'PRODUCT_PRICE_MODIFY',
            'STOCK_MOVEMENT',
        ));
    }
    
    /**
     *      @abstract      Identify Order Id from Given Object
     *
     *      @param  object      $Object      Objet concerne
     *
     *      @return void
     */
    private function setProductObjectId($Object)
    {
        //====================================================================//
        // Identify Product Id
        if (is_a($Object, "Product")) {
            $this->Id   = $Object->id;
        } elseif (is_a($Object, "MouvementStock")) {
            $this->Id   = $Object->product_id;
        }
    }
    
    /**
     * @abstract      Prepare Object Commit for Product
     *
     * @param  string      $Action      Code de l'evenement
     * @param  object      $Object      Objet concerne
     *
     * @return void
     *
     *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setProductParameters($Action)
    {
        //====================================================================//
        // Check if object if in Remote Create Mode
        $isLockedForCreation    =    Splash::object("Product")->isLocked();
        
        //====================================================================//
        // Store Global Action Parameters
        $this->Type     = "Product";
        if ($Action        == 'PRODUCT_CREATE') {
            $this->Action       = SPL_A_CREATE;
            $this->Comment      = "Product Created on Dolibarr";
        } elseif ($Action  == 'PRODUCT_MODIFY') {
            $this->Action       = SPL_A_UPDATE;
            $this->Comment      = "Product Updated on Dolibarr";
        } elseif ($Action  == 'PRODUCT_SET_MULTILANGS') {
            $this->Action       = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->Comment      = "Product Description Updated on Dolibarr";
        } elseif ($Action  == 'STOCK_MOVEMENT') {
            $this->Action       = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->Comment      = "Product Stock Updated on Dolibarr";
        } elseif ($Action  == 'PRODUCT_PRICE_MODIFY') {
            $this->Action       = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->Comment  = "Product Price Updated on Dolibarr";
        } elseif ($Action  == 'PRODUCT_DELETE') {
            $this->Action       = SPL_A_DELETE;
            $this->Comment      = "Product Deleted on Dolibarr";
        }
        
        return true;
    }
}
