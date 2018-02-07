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

namespace Splash\Local\Objects\Invoice;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Customer Invoice CRUD Functions
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
        global $conf;        
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
        $Object = new \Facture ($db);
        //====================================================================//
        // Fatch Object 
        if ( $Object->fetch($Id) != 1 ) {
            $this->CatchDolibarrErrors($Object);
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Current Entity is : " . $conf->entity);
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer Invoice (" . $Id . ").");
        }   
        //====================================================================//
        // Check Object Entity Access (MultiCompany) 
        if ( !Splash::Local()->isMultiCompanyAllowed($Object) ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer Invoice (" . $Id . ").");
        }           
        $Object->fetch_lines(); 
        $this->loadPayments($Id);
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
        $this->Object = new \Facture($db);    
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setSimple("date", $this->In["date"] );
        $this->setSimple("socid", self::Objects()->Id($this->In["socid"]) );  
        $this->setSimple("statut", \Facture::STATUS_DRAFT );  
        $this->Object->statut = \Facture::STATUS_DRAFT;
        $this->Object->paye = 0;
        
        //====================================================================//
        // Create Object In Database
        if ( $this->Object->create($user) <= 0) {    
            $this->CatchDolibarrErrors();
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new Customer Invoice. ");
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
        // Update Object 
        if ( $this->Object->update($user)  <= 0 ) {  
            $this->CatchDolibarrErrors();
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to Update Customer Invoice (" . $this->Object->id . ")") ;
        }        
        //====================================================================//
        // Update Object Extra Fields 
        if ( $this->Object->insertExtraFields()  <= 0 ) {  
            $this->CatchDolibarrErrors();
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
        global $db,$user,$conf;
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Load Object 
        $Object = new \Facture($db);
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }
        //====================================================================//
        // Debug Mode => Force Allow Delete
        if ( defined("SPLASH_DEBUG") && SPLASH_DEBUG ) {
            $conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED = 1;
        }        
        //====================================================================//
        // Debug Mode => Force Delete All Invooices Payments
        if ( defined("SPLASH_DEBUG") && SPLASH_DEBUG ) {
            $this->clearPayments($Id);
        }        
        //====================================================================//
        // Set Object Id, fetch not needed
        $Object->id = $Id;
        //====================================================================//
        // Check Object Entity Access (MultiCompany) 
        unset($Object->entity);
        if ( !Splash::Local()->isMultiCompanyAllowed($Object) ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to Delete Customer Invoice (" . $Id . ").");
        }        
        //====================================================================//
        // Delete Object 
        $Arg1 = ( Splash::Local()->DolVersionCmp("5.0.0") > 0 ) ? $user : 0;
        if ( $Object->delete($Arg1) <= 0 ) {  
            $this->CatchDolibarrErrors( $Object );
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to Delete Customer Invoice (" . $Id . ")") ;
        }
        return True;
    }      
    
}
