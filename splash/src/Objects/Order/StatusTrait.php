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
 * @abstract    Dolibarr Customer Order Status Field
 */
trait StatusTrait
{

    /**
     *  @abstract     Build Customer Order Status Fields using FieldFactory
     */
    protected function buildStatusFields()
    {
        
        global $langs;
        
        //====================================================================//
        // Order Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("status")
                ->Name($langs->trans("Status"))
                ->Group(html_entity_decode($langs->trans("Status")))
                ->MicroData("http://schema.org/Order", "orderStatus")
                ->AddChoice("OrderCanceled", $langs->trans("StatusOrderCanceled"))
                ->AddChoice("OrderDraft", $langs->trans("StatusOrderDraftShort"))
                ->AddChoice("OrderInTransit", $langs->trans("StatusOrderSent"))
                ->AddChoice("OrderProcessing", $langs->trans("StatusOrderSentShort"))
                ->AddChoice("OrderDelivered", $langs->trans("StatusOrderProcessed"))
                ->isNotTested()
                ;
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getStatusFields($Key, $FieldName)
    {
        if ($FieldName != 'status') {
            return;
        }
        
        if ($this->object->statut == -1) {
            $this->out[$FieldName]  = "OrderCanceled";
        } elseif ($this->object->statut == 0) {
            $this->out[$FieldName]  = "OrderDraft";
        } elseif ($this->object->statut == 1) {
            $this->out[$FieldName]  = "OrderProcessing";
        } elseif ($this->object->statut == 2) {
            $this->out[$FieldName]  = "OrderInTransit";
        } elseif ($this->object->statut == 3) {
            $this->out[$FieldName]  = "OrderDelivered";
        } else {
            $this->out[$FieldName]  = "Unknown";
        }
        
        unset($this->in[$Key]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     *
     *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *  @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function setStatusFields($FieldName, $Data)
    {
        global $conf,$langs,$user;
        
        if ($FieldName != 'status') {
            return;
        }
        unset($this->in[$FieldName]);

        //====================================================================//
        // Safety Check
        if (empty($this->object->id)) {
            return false;
        }
        //====================================================================//
        // Verify Stock Is Defined if Required
        // If stock is incremented on validate order, we must increment it
        if (!empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1) {
            if (empty($conf->global->SPLASH_STOCK)) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    $langs->trans("WarehouseSourceNotDefined")
                );
            }
        }
        //====================================================================//
        // Statut Canceled
        //====================================================================//
        // Statut Canceled
        if (($Data == "OrderCanceled") && ($this->object->statut != -1)) {
            //====================================================================//
            // If Previously Closed => Set Draft
            if (( $this->object->statut == 3 )
                    && ( $this->object->set_draft($user, $conf->global->SPLASH_STOCK) != 1 )) {
                return $this->catchDolibarrErrors();
            }
            //====================================================================//
            // If Previously Draft => Valid
            if (( $this->object->statut == 0 ) && ( $this->object->valid($user, $conf->global->SPLASH_STOCK) != 1 )) {
                return $this->catchDolibarrErrors();
            }
            //====================================================================//
            // Set Canceled
            if ($this->object->cancel($conf->global->SPLASH_STOCK) != 1) {
                    return $this->catchDolibarrErrors();
            }
            $this->object->statut = \Commande::STATUS_CANCELED;
            return true;
        }
        //====================================================================//
        // If Previously Canceled => Re-Validate
        if (( $this->object->statut == -1 ) && ( $this->object->valid($user, $conf->global->SPLASH_STOCK) != 1 )) {
            return $this->catchDolibarrErrors();
        }
        //====================================================================//
        // Statut Draft
        if ($Data == "OrderDraft") {
            //====================================================================//
            // If Not Draft (Validated or Closed)
            if (($this->object->statut != 0) && $this->object->set_draft($user, $conf->global->SPLASH_STOCK) != 1) {
                return $this->catchDolibarrErrors();
            }
            $this->object->statut = \Commande::STATUS_DRAFT;
            return true;
        }
        //====================================================================//
        // Statut Validated || Closed => Go Valid if Draft
        if (( $this->object->statut == 0 ) && ( $this->object->valid($user, $conf->global->SPLASH_STOCK) != 1 )) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, "Set Validated", $langs->trans($this->object->error));
        }
        //====================================================================//
        // Statut Not Closed but Validated Only => ReOpen
        if ($Data != "OrderDelivered") {
            //====================================================================//
            // If Previously Closed => Re-Open
            if (( $this->object->statut == 3 ) && ( $this->object->set_reopen($user) != 1 )) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, "Re-Open", $langs->trans($this->object->error));
            }
            $this->object->statut = \Commande::STATUS_VALIDATED;
        }
        //====================================================================//
        // Statut Closed => Go Closed
        if (($Data == "OrderDelivered") && ($this->object->statut != 3)) {
            //====================================================================//
            // If Previously Validated => Close
            if (( $this->object->statut == 1 ) && ( $this->object->cloture($user) != 1 )) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, "Set Closed", $langs->trans($this->object->error));
            }
            $this->object->statut = \Commande::STATUS_CLOSED;
        }
        //====================================================================//
        // Redo Billed flag Update if Impacted by Status Change
        $this->updateBilledFlag();
    }
}
