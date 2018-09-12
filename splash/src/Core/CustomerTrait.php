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

namespace Splash\Local\Core;

use Splash\Core\SplashCore as Splash;

/**
 * @abstract    Dolibarr Customer Fields (Required)
 */
trait CustomerTrait
{
    /**
     * @abstract    Detected SocId
     * @var         int
     */
    private $SocId  =   null;
    
    /**
     *  @abstract     Build Fields using FieldFactory
     */
    protected function buildCustomerFields()
    {
        global $langs;
        
        //====================================================================//
        // Customer Object Link
        $this->fieldsFactory()->create(self::objects()->Encode("ThirdParty", SPL_T_ID))
                ->Identifier("socid")
                ->Name($langs->trans("Company"));
        
        //====================================================================//
        // Metadata are Specific to Object Type (Order/Invoice)
        if (is_a($this, 'Splash\Local\Objects\Order')) {
            $this->fieldsFactory()->MicroData("http://schema.org/Organization", "ID");
        } elseif (is_a($this, 'Splash\Local\Objects\Invoice')) {
            $this->fieldsFactory()->MicroData("http://schema.org/Invoice", "customer");
        }
        
        //====================================================================//
        // Not Allowed Guest Orders/Invoices Mode
        if (!$this->isAllowedGuest()) {
            $this->fieldsFactory()->isRequired();
            return;
        }
        
        //====================================================================//
        // Is Allowed Customer Email Detection
        if ($this->isAllowedEmailDetection()) {
            //====================================================================//
            // Customer Email
            $this->fieldsFactory()->create(SPL_T_EMAIL)
                    ->Identifier("email")
                    ->Name($langs->trans("Email"))
                    ->MicroData("http://schema.org/ContactPoint", "email")
                    ->isWriteOnly()
                    ->isNotTested();
        }
    }

    /**
     *  @abstract     Init Customer SocId Detection
     *
     *  @return         none
     */
    protected function initCustomerDetection()
    {
        //====================================================================//
        // Order/Invoice Create Mode => Init SocId detection
        $this->SocId = null;
    }
    
    /**
     *  @abstract     Init Customer SocId with Guste Mode Management
     *
     *  @param        Array     $Data       Received Data
     *
     *  @return         none
     */
    protected function doCustomerDetection($Data)
    {
        //====================================================================//
        // Order/Invoice Create Mode => Init SocId detection
        $this->initCustomerDetection();
        
        //====================================================================//
        // Standard Mode => A SocId is Given
        if (isset($Data["socid"]) && !empty(self::objects()->Id($Data["socid"]))) {
            $this->setSimple("socid", self::objects()->Id($Data["socid"]));
            return;
        }
        
        //====================================================================//
        // Guest Mode is Disabled => Error
        if (!$this->isAllowedGuest()) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "socid");
        }
        
        //====================================================================//
        // Guest Mode => Detect SocId in Guest Mode
        $this->setSimple("socid", $this->getGuestCustomer($Data));
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getCustomerFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // ThirdParty Id
            case 'socid':
                $this->Out[$FieldName] = self::objects()->Encode("ThirdParty", $this->Object->$FieldName);
                break;
            
            default:
                return;
        }
        
        unset($this->In[$Key]);
        
        if ($FieldName != "socid") {
            return;
        }
        
        //====================================================================//
        // Contact ThirdParty Id
        $this->Out[$FieldName] = self::objects()->Encode("ThirdParty", $this->Object->$FieldName);

        unset($this->In[$Key]);
    }

    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    protected function setCustomerFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Customer Id
            case 'socid':
                //====================================================================//
                // Standard Mode => A SocId is Requiered
                if (!$this->isAllowedGuest()) {
                    $this->setSimple($FieldName, self::objects()->Id($Data));
                    break;
                }
                $this->setSimple($FieldName, $this->getGuestCustomer($this->In));
                break;

            //====================================================================//
            // Customer Email
            case 'email':
                if (!$this->isAllowedGuest() || !$this->isAllowedEmailDetection()) {
                    break;
                }
                $this->setSimple("socid", $this->getGuestCustomer($this->In));
                break;
            
            default:
                return;
        }
        
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Check if Guest Orders Are Allowed
     *
     *  @return         bool
     */
    private function isAllowedGuest()
    {
        global $conf;
        if (!isset($conf->global->SPLASH_GUEST_ORDERS_ALLOW) || empty($conf->global->SPLASH_GUEST_ORDERS_ALLOW)) {
            return false;
        }
        
        if (!isset($conf->global->SPLASH_GUEST_ORDERS_CUSTOMER) || empty($conf->global->SPLASH_GUEST_ORDERS_CUSTOMER)) {
            Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "To use Guest Orders/Invoices mode, you must select a default Customer."
            );
            return false;
        }
        
        return true;
    }
    
    /**
     *  @abstract     Check if Email Detyection is Active
     *
     *  @return         bool
     */
    private function isAllowedEmailDetection()
    {
        global $conf;
        if (!$this->isAllowedGuest() || !isset($conf->global->SPLASH_GUEST_ORDERS_EMAIL)) {
            return false;
        }
        return (bool) $conf->global->SPLASH_GUEST_ORDERS_EMAIL;
    }
    
    /**
     *  @abstract     Detect Guest Customer To Use for This Order/Invoice
     *
     *  @param        Array     $Data       Received Data
     *
     *  @return       int
     */
    protected function getGuestCustomer($Data)
    {
        global $conf;
        
        //====================================================================//
        // Customer detection Already Done
        if (!is_null($this->SocId)) {
            return $this->SocId;
        }
        
        //====================================================================//
        // Standard Mode => A SocId is Given
        if (isset($Data["socid"]) && !empty(self::objects()->Id($Data["socid"]))) {
            Splash::log()->deb("Customer Id Given : Id " .  self::objects()->Id($Data["socid"]));
            $this->SocId = self::objects()->Id($Data["socid"]);
            return $this->SocId;
        }
        //====================================================================//
        // Detect ThirdParty Using Given Email
        if ($this->isAllowedEmailDetection() && isset($Data["email"]) && !empty($Data["email"])) {
            $this->SocId  =   $this->getCustomerByEmail($Data["email"]);
            Splash::log()->deb("Customer Email Identified : Id " .  $this->SocId);
        }
        //====================================================================//
        // Select ThirdParty Using Default Parameters
        if (empty($this->SocId)) {
            $this->SocId  =   $conf->global->SPLASH_GUEST_ORDERS_CUSTOMER;
            Splash::log()->deb("Default Customer Used : Id " .  $this->SocId);
        }

        return $this->SocId;
    }
    
    /**
     *  @abstract     Detect Guest Customer To Use for This Order/Invoice
     *
     *  @param        string     $Email       Customer Email
     *
     *  @return       int   Customer Id
     */
    private function getCustomerByEmail($Email)
    {
        global $db;
        
        //====================================================================//
        // Prepare Sql Query
        $sql = 'SELECT s.rowid';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
        $sql .= ' WHERE s.entity IN ('.getEntity('societe').')';
        $sql .= " AND s.email = '".$db->escape($Email)."'";
        
        //====================================================================//
        // Execute Query
        $resql=$db->query($sql);
        if (!$resql || ($db->num_rows($resql) != 1)) {
            return 0;
        }
        $Customer = $db->fetch_object($resql);
        Splash::log()->deb("Customer Detected by Email : " . $Email . " => Id " .  $Customer->rowid);
        
        //====================================================================//
        // Return Customer Id
        return $Customer->rowid;
    }
}
