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

namespace Splash\Local\Objects\Address;

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
    public function load( $Id )
    {
        global $db;        
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__,__FUNCTION__); 
        //====================================================================//
        // Init Object 
        $Object = new \Contact ($db);
        //====================================================================//
        // Fatch Object 
        if ( $Object->fetch($Id) != 1 ) {
            $this->CatchDolibarrErrors($Object);
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Contact Address (" . $Id . ").");
        }  
        //====================================================================//
        // Check Object Entity Access (MultiCompany) 
        if ( !Splash::Local()->isMultiCompanyAllowed($Object) ) {
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Contact Address (" . $Id . ").");
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
    public function create()
    {
        global $db, $user;          
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__,__FUNCTION__);         
        //====================================================================//
        // Check Customer Name is given
        if ( empty($this->In["firstname"]) ) {
            return Splash::log()->err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"firstname");
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::log()->err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }         
        //====================================================================//
        // Init Object 
        $this->Object = new \Contact($db);        
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setSimple("firstname", $this->In["firstname"] );
        //====================================================================//
        // Create Object In Database
        if ( $this->Object->create($user) <= 0) {    
            $this->CatchDolibarrErrors();
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new Contact Address. ");
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
    public function update( $Needed )
    {
        global $user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__,__FUNCTION__);  
        if ( !$Needed) {
            return (int) $this->Object->id;
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::log()->err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }        
        //====================================================================//
        // Update Object 
        if ( $this->Object->update($this->Object->id,$user) <= 0) {  
            $this->CatchDolibarrErrors();
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to Update Product (" . $this->Object->id . ")") ;
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
    public function delete($Id = NULL)
    {
        global $db,$user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Load Object 
        $Object = new \Contact($db);
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::log()->err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }
        //====================================================================//
        // Set Object Id, fetch not needed
        $Object->id = $Id;
        //====================================================================//
        // Check Object Entity Access (MultiCompany) 
        unset($Object->entity);
        if ( !Splash::Local()->isMultiCompanyAllowed($Object) ) {
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to delete Product (" . $Id . ").");
        }        
        //====================================================================//
        // Delete Object 
//        $Arg1 = ( Splash::Local()->DolVersionCmp("6.0.0") > 0 ) ? $user : 0;
        if ( $Object->delete() <= 0 ) {  
            return $this->CatchDolibarrErrors( $Object );
        }
        return True;
    }      
    
}
