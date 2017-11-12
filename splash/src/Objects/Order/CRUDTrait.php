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

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Customer Orders CRUD Functions
 */
trait CRUDTrait
{
    
    /**
     * @abstract    Load Request Object 
     * @param       string  $Id               Object id
     * @return      mixed
     */
    public function Load( $Id )
    {
        global $db, $user;        
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__); 
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }             
        //====================================================================//
        // Init Object 
        $Object = new \Commande ($db);
        //====================================================================//
        // Fatch Object 
        if ( $Object->fetch($Id) != 1 ) {
            $this->CatchDolibarrErrors($Object);
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer Order (" . $Id . ").");
        }   
        $Object->fetch_lines();        
        return $Object;
    }    

    /**
     * @abstract    Create Request Object 
     * 
     * @param       array   $List         Given Object Data
     * 
     * @return      object     New Object
     */
    public function Create()
    {
        global $db, $user;          
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);         
        //====================================================================//
        // Check Order Date is given
        if ( empty($this->In["date"]) ) {
            return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"date");
        }
        //====================================================================//
        // Check Customer Id is given
        if ( empty($this->In["socid"]) || empty(self::Objects()->Id($this->In["socid"])) ) {
            return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"socid");
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }         
        //====================================================================//
        // Init Object 
        $this->Object = new \Commande($db);    
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setSimple("date", $this->In["date"] );
        $this->setSimple("socid", self::Objects()->Id($this->In["socid"]) );  
        $this->setSimple("statut", \Commande::STATUS_DRAFT );  
        
        //====================================================================//
        // Create Object In Database
        if ( $this->Object->create($user) <= 0) {    
            $this->CatchDolibarrErrors();
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new Customer Order. ");
        }        
        
        return $this->Object;
    }
    
    /**
     * @abstract    Update Request Object 
     * 
     * @param       array   $Needed         Is This Update Needed
     * 
     * @return      string      Object Id
     */
    public function Update( $Needed )
    {
        global $user;
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        if ( !$Needed) {
            return (int) $this->Object->id;
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }        
        //====================================================================//
        // Update Product Object 
        if ( $this->Object->update($user)  <= 0 ) {  
            $this->CatchDolibarrErrors();
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to Update Customer Order (" . $this->Object->id . ")") ;
        }        
        return (int) $this->Object->id;
    }  
    
    /**
     * @abstract    Delete requested Object
     * 
     * @param       int     $Id     Object Id.  If NULL, Object needs to be created.
     * 
     * @return      bool                          
     */    
    public function Delete($Id = NULL)
    {
        global $db,$user;
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Load Object 
        $Object = new \Commande($db);
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }
        //====================================================================//
        // Set Object Id, fetch not needed
        $Object->id = $Id;
        //====================================================================//
        // Delete Object 
//        $Arg1 = ( Splash::Local()->DolVersionCmp("6.0.0") > 0 ) ? $user : 0;
        if ( $Object->delete($user) <= 0 ) {  
            return $this->CatchDolibarrErrors( $Object );
        }
        return True;
    }      
    
}
