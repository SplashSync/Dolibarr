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

namespace Splash\Local\Objects\ThirdParty;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Contacts Address CRUD Functions
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
        global $db;        
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__); 
        //====================================================================//
        // Init Object 
        $Object = new \Societe ($db);
        //====================================================================//
        // Fatch Object 
        if ( $Object->fetch($Id) != 1 ) {
            $this->CatchDolibarrErrors($Object);
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load ThirdPaty (" . $Id . ").");
        }        
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
        // Check Customer Name is given
        if ( empty($this->In["name"]) ) {
            return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"name");
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }         
        //====================================================================//
        // Init Object 
        $this->Object = new \Societe($db);        
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setSimple("name", $this->In["name"] );
        //====================================================================//
        // Dolibarr infos
        $this->Object->client             = 1;        // 0=no customer, 1=customer, 2=prospect
        $this->Object->prospect           = 0;        // 0=no prospect, 1=prospect
        $this->Object->fournisseur        = 0;        // 0=no supplier, 1=supplier
        $this->Object->code_client        = -1;       // If not erased, will be created by system
        $this->Object->code_fournisseur   = -1;       // If not erased, will be created by system        
        //====================================================================//
        // Create Object In Database
        if ( $this->Object->create($user) <= 0) {    
            $this->CatchDolibarrErrors();
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new ThirdPaty. ");
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
        // Compute Changes on Customer Name
        $this->updateFullName();        
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
        if ( $this->Object->update($this->Object->id,$user,1,$this->allowmodcodeclient) <= 0) {  
            $this->CatchDolibarrErrors();
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to Update Product (" . $this->Object->id) . ")" ;
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
        $Object = new \Societe($db);
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
        if ( $Object->delete($Id) <= 0 ) {  
            return $this->CatchDolibarrErrors( $Object );
        }
        return True;
    }      
    
}
