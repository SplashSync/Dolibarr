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

namespace Splash\Local\Objects\Order;

use Splash\Client\Splash;

/**
 * @abstract    Order Dolibarr Trigger trait
 */
trait TriggersTrait {
    
    /**
     *      @abstract      Prepare Object Commit for Order
     * 
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     * 
     *      @return bool        Commit is required
     */
    function doOrderCommit($Action, $Object)
    {    
        global $db;

        //====================================================================//
        // Check if Commit is Requierd
        if (!$this->isOrderCommitRequired($Action)) {
            return False;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters 
        $this->setOrderObjectId($Object);
        $this->setOrderParameters($Action);
        
        return True;
    }    

    /**
     * @abstract      Check if Commit is Requiered
     * 
     * @param  string      $Action      Code de l'evenement
     * 
     * @return bool
     */
    private function isOrderCommitRequired($Action)
    {    
        //====================================================================//
        // Filter Triggered Actions 
        return in_array($Action,array(
            // Order Actions
            'ORDER_CREATE',
            'ORDER_VALIDATE',
            'ORDER_MODIFY',
            'ORDER_UPDATE',
            'ORDER_DELETE',
            'ORDER_CLOSE',
            'ORDER_REOPEN',
            'ORDER_CLASSIFY_BILLED',
            'ORDER_CANCEL',
            // Order Line Actions
            'LINEORDER_INSERT',
            'LINEORDER_UPDATE',
            'LINEORDER_DELETE',
            // Order Contacts Actions
            'COMMANDE_ADD_CONTACT',
            'COMMANDE_DELETE_CONTACT',
        ));
    }     
    
    /**
     *      @abstract      Identify Order Id from Given Object
     * 
     *      @param  object      $Object      Objet concerne
     * 
     *      @return void
     */
    private function setOrderObjectId($Object)
    {    
        //====================================================================//
        // Identify Order Id         
        if (is_a($Object, "OrderLine")) 
        {
            if ($Object->fk_commande) {
                $this->Id        = $Object->fk_commande;
            } else {
                $this->Id        = $Object->oldline->fk_commande;
            }
        } else {
            $this->Id        = $Object->id;
        } 
    }  
    
    /**
     *      @abstract      Prepare Object Commit for Product
     * 
     *      @param  string      $Action      Code de l'evenement
     * 
     *      @return void
     */
    private function setOrderParameters($Action)
    {    
        //====================================================================//
        // Store Global Action Parameters 
        $this->Type      = "Order";
        
        switch($Action) {
            case 'ORDER_CREATE':
                $this->Action       = SPL_A_CREATE;
                $this->Comment      = "Order Created on Dolibarr";
                break;
            
            case 'ORDER_VALIDATE':
            case 'ORDER_MODIFY':
            case 'ORDER_UPDATE':
            case 'ORDER_CLOSE':
            case 'ORDER_REOPEN':
            case 'ORDER_CLASSIFY_BILLED':
            case 'ORDER_CANCEL':
            case 'LINEORDER_INSERT':
            case 'LINEORDER_UPDATE':
            case 'LINEORDER_DELETE':
            case 'COMMANDE_ADD_CONTACT':
            case 'COMMANDE_DELETE_CONTACT':
                $this->Action       = (Splash::Object("Order")->isLocked() ?   SPL_A_CREATE : SPL_A_UPDATE);
                $this->Comment      = "Order Updated on Dolibarr";
                break;

            case 'ORDER_DELETE':
                $this->Action       = SPL_A_DELETE;
                $this->Comment      = "Order Deleted on Dolibarr";
                break;
            
        }
        
        return True;
    }  
    
}
