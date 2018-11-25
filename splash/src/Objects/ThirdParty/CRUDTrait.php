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
    public function load($Id)
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Init Object
        $Object = new \Societe($db);
        //====================================================================//
        // Fetch Object
        if ($Object->fetch($Id) != 1) {
            $this->catchDolibarrErrors($Object);
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load ThirdParty (" . $Id . ")."
            );
        }
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        if (!Splash::local()->isMultiCompanyAllowed($Object)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load ThirdParty (" . $Id . ")."
            );
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
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Customer Name is given
        if (empty($this->in["name"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "name");
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::local()->LoadLocalUser();
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Init Object
        $this->object = new \Societe($db);
        //====================================================================//
        // Pre-Setup of Dolibarr infos
        $this->setSimple("name", $this->in["name"]);
        //====================================================================//
        // Dolibarr infos
        $this->object->client             = 1;        // 0=no customer, 1=customer, 2=prospect
        $this->object->prospect           = 0;        // 0=no prospect, 1=prospect
        $this->object->fournisseur        = 0;        // 0=no supplier, 1=supplier
        $this->object->code_client        = -1;       // If not erased, will be created by system
        $this->object->code_fournisseur   = -1;       // If not erased, will be created by system
        //====================================================================//
        // Create Object In Database
        if ($this->object->create($user) <= 0) {
            $this->catchDolibarrErrors();
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create new ThirdParty. ");
        }
        
        return $this->object;
    }
    
    /**
     * @abstract    Update Request Object
     *
     * @param       bool   $Needed         Is This Update Needed
     *
     * @return      string      Object Id
     */
    public function update($Needed)
    {
        global $user;
        //====================================================================//
        // Compute Changes on Customer Name
        $this->updateFullName();
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        if (!$Needed && !$this->isToUpdate()) {
            return (int) $this->object->id;
        }
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::local()->LoadLocalUser();
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Update Object
        if ($this->object->update($this->object->id, $user, 1, 1) <= 0) {
            $this->catchDolibarrErrors();
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Update ThirdParty (" . $this->object->id . ")"
            );
        }
        //====================================================================//
        // Update Object Extra Fields
        if ($this->object->insertExtraFields()  <= 0) {
            $this->catchDolibarrErrors();
        }
        return (int) $this->object->id;
    }
    
    /**
     * @abstract    Delete requested Object
     *
     * @param       int     $Id     Object Id.  If NULL, Object needs to be created.
     *
     * @return      bool
     */
    public function delete($Id = null)
    {
        global $db,$user;
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Object
        $Object = new \Societe($db);
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::local()->LoadLocalUser();
        if (empty($user->login)) {
            return Splash::log()->err("ErrLocalUserMissing", __CLASS__, __FUNCTION__);
        }
        //====================================================================//
        // Set Object Id, fetch not needed
        $Object->id = $Id;
        //====================================================================//
        // Check Object Entity Access (MultiCompany)
        unset($Object->entity);
        if (!Splash::local()->isMultiCompanyAllowed($Object)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to Delete ThirdParty (" . $Id . ")."
            );
        }
        //====================================================================//
        // Delete Object
//        $Arg1 = ( Splash::local()->dolVersionCmp("6.0.0") > 0 ) ? $user : 0;
        if ($Object->delete($Id) <= 0) {
            return $this->catchDolibarrErrors($Object);
        }
        return true;
    }
}
