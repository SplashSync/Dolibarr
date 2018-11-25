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
 * @abstract    Dolibarr Customer Invoice Status Field
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
        // Invoice Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("status")
                ->Name($langs->trans("Status"))
                ->Group(html_entity_decode($langs->trans("Status")))
                ->MicroData("http://schema.org/Invoice", "paymentStatus")
                ->AddChoice("PaymentDraft", $langs->trans("BillStatusDraft"))
                ->AddChoice("PaymentDue", $langs->trans("BillStatusNotPaid"))
                ->AddChoice("PaymentComplete", $langs->trans("BillStatusConverted"))
                ->AddChoice("PaymentCanceled", $langs->trans("BillStatusCanceled"))
                ->isNotTested();
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
        
        if ($this->object->statut == 0) {
            $this->out[$FieldName]  = "PaymentDraft";
        } elseif ($this->object->statut == 1) {
            $this->out[$FieldName]  = "PaymentDue";
        } elseif ($this->object->statut == 2) {
            $this->out[$FieldName]  = "PaymentComplete";
        } elseif ($this->object->statut == 3) {
            $this->out[$FieldName]  = "PaymentCanceled";
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
     *  @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setStatusFields($FieldName, $Data)
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
        // If stock is incremented on validate invoice, we must provide warhouse id
        if (!empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_BILL == 1) {
            if (empty($conf->global->SPLASH_STOCK)) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    $langs->trans("WarehouseSourceNotDefined")
                );
            }
        }
        $InitialStatut  =   $this->object->statut;
        switch ($Data) {
            //====================================================================//
            // Status Draft
            //====================================================================//
            case "Unknown":
            case "PaymentDraft":
                //====================================================================//
                // Whatever => Set Draft
                if (( $this->object->statut != 0 )
                        && ( $this->object->set_draft($user, $conf->global->SPLASH_STOCK) != 1 )) {
                    return Splash::log()->err(
                        "ErrLocalTpl",
                        __CLASS__,
                        "Set Draft",
                        $langs->trans($this->object->error)
                    );
                }
                $this->object->statut = \Facture::STATUS_DRAFT;
                break;
            //====================================================================//
            // Status Validated
            //====================================================================//
            case "PaymentDue":
            case "PaymentDeclined":
            case "PaymentPastDue":
                //====================================================================//
                // If Already Paid => Set Draft
                if (( $this->object->statut == 2 )
                        && ( $this->object->set_draft($user, $conf->global->SPLASH_STOCK) != 1 )) {
                    return $this->catchDolibarrErrors();
                }
                //====================================================================//
                // If Already Canceled => Set Draft
                if (( $this->object->statut == 3 )
                        && ( $this->object->set_draft($user, $conf->global->SPLASH_STOCK) != 1 )) {
                    return $this->catchDolibarrErrors();
                }
                //====================================================================//
                // If Not Valdidated => Set Validated
                if (( $this->object->statut != 1 )
                        && ( $this->object->validate($user, "", $conf->global->SPLASH_STOCK) != 1 )) {
                    return $this->catchDolibarrErrors();
                }
                $this->object->paye = 0;
                $this->object->statut = \Facture::STATUS_VALIDATED;
                break;
            //====================================================================//
            // Status Paid
            //====================================================================//
            case "PaymentComplete":
                //====================================================================//
                // If Draft => Set Validated
                if (( $this->object->statut == 0 )
                        && ( $this->object->validate($user, "", $conf->global->SPLASH_STOCK) != 1 )) {
                    return $this->catchDolibarrErrors();
                }
                //====================================================================//
                // If Validated => Set Paid
                if (( $this->object->statut == 1 ) && ( $this->object->set_paid($user) != 1 )) {
                    return $this->catchDolibarrErrors();
                }
                $this->object->paye = 1;
                $this->object->statut = \Facture::STATUS_CLOSED;
                break;
            //====================================================================//
            // Status Canceled
            //====================================================================//
            case "PaymentCanceled":
                //====================================================================//
                // Whatever => Set Canceled
                if (( $this->object->statut != 3 ) && ( $this->object->set_canceled($user) != 1 )) {
                    return $this->catchDolibarrErrors();
                }
                $this->object->paye = 0;
                $this->object->statut = \Facture::STATUS_ABANDONED;
                break;
        }
        if ($InitialStatut != $this->object->statut) {
            $this->needUpdate();
        }
    }
}
