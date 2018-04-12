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
 * @abstract    Dolibarr Customer Orders Fields
 */
trait MainTrait
{

    /**
     *  @abstract     Build Address Fields using FieldFactory
     */
    protected function buildMainFields()
    {
        global $langs,$conf;
        
        //====================================================================//
        // Delivry Estimated Date
        $this->fieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date_livraison")
                ->Name($langs->trans("DeliveryDate"))
                ->MicroData("http://schema.org/ParcelDelivery", "expectedArrivalUntil");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_ht")
                ->Name($langs->trans("TotalHT") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->MicroData("http://schema.org/Invoice", "totalPaymentDue")
                ->isReadOnly();
        
        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_ttc")
                ->Name($langs->trans("TotalTTC") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->MicroData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
                ->isReadOnly();
        
        //====================================================================//
        // ORDER STATUS FLAGS
        //====================================================================//

        //====================================================================//
        // Is Draft
        $this->fieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isdraft")
                ->Group(html_entity_decode($langs->trans("Status")))
                ->Name($langs->trans("Order") . " : " . $langs->trans("Draft"))
                ->MicroData("http://schema.org/OrderStatus", "OrderDraft")
                ->Association("isdraft", "iscanceled", "isvalidated", "isclosed")
                ->isReadOnly();

        //====================================================================//
        // Is Canceled
        $this->fieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("iscanceled")
                ->Group(html_entity_decode($langs->trans("Status")))
                ->Name($langs->trans("Order") . " : " . $langs->trans("Canceled"))
                ->MicroData("http://schema.org/OrderStatus", "OrderCancelled")
                ->Association("isdraft", "iscanceled", "isvalidated", "isclosed")
                ->isReadOnly();
        
        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isvalidated")
                ->Group(html_entity_decode($langs->trans("Status")))
                ->Name($langs->trans("Order") . " : " . $langs->trans("Validated"))
                ->MicroData("http://schema.org/OrderStatus", "OrderProcessing")
                ->Association("isdraft", "iscanceled", "isvalidated", "isclosed")
                ->isReadOnly();
        
        //====================================================================//
        // Is Closed
        $this->fieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isclosed")
                ->Name($langs->trans("Order") . " : " . $langs->trans("Closed"))
                ->Group(html_entity_decode($langs->trans("Status")))
                ->MicroData("http://schema.org/OrderStatus", "OrderDelivered")
                ->Association("isdraft", "iscanceled", "isvalidated", "isclosed")
                ->isReadOnly();

        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("facturee")
                ->Group(html_entity_decode($langs->trans("Status")))
                ->Name($langs->trans("Order") . " : " . $langs->trans("Paid"))
                ->MicroData("http://schema.org/OrderStatus", "OrderPaid")
                ->isNotTested();
        
        return;
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getMainFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Order Delivery Date
            case 'date_livraison':
                $date_livraison   =  $this->Object->date_livraison;
                $this->Out[$FieldName] = !empty($date_livraison)?dol_print_date($date_livraison, '%Y-%m-%d'):null;
                break;
            
            //====================================================================//
            // ORDER INVOICED FLAG
            //====================================================================//
            case 'facturee':
                $this->getSimple($FieldName);
                break;

            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getTotalsFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_ht':
            case 'total_ttc':
            case 'total_vat':
                $this->getSimple($FieldName);
                break;

            default:
                return;
        }
        
        unset($this->In[$Key]);
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getStatesFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//

            case 'isdraft':
                $this->Out[$FieldName]  = ( $this->Object->statut == 0 )    ?   true:false;
                break;
            case 'iscanceled':
                $this->Out[$FieldName]  = ( $this->Object->statut == -1 )   ?   true:false;
                break;
            case 'isvalidated':
                $this->Out[$FieldName]  = ( $this->Object->statut == 1 )    ?   true:false;
                break;
            case 'isclosed':
                $this->Out[$FieldName]  = ( $this->Object->statut == 3 )    ?   true:false;
                break;

            default:
                return;
        }
        
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
    protected function setMainFields($FieldName, $Data)
    {
        global $user;
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Order Official Date
            case 'date_livraison':
                if (dol_print_date($this->Object->$FieldName, 'standard') === $Data) {
                    break;
                }
                $this->Object->set_date_livraison($user, $Data);
                $this->needUpdate();
                break;
               
            //====================================================================//
            // ORDER INVOICED FLAG
            //====================================================================//
            case 'facturee':
                if ($this->Object->facturee == $Data) {
                    break;
                }
                $this->updateBilled = $Data;
                $this->updateBilledFlag();
                break;
                
            default:
                return;
        }
        
        
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Update Order Billed Flag if Required & Possibe
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    protected function updateBilledFlag()
    {
        global $user;
        
        // Not Required
        if (!isset($this->updateBilled)) {
            return;
        }
        // Not Possible
        if ($this->Object->statut <= \Commande::STATUS_DRAFT) {
            return;
        }
        
        // Update
        if ($this->updateBilled) {
            $this->Object->classifyBilled($user);
        } else {
            $this->Object->classifyUnBilled();
        }
        unset($this->updateBilled);
        $this->catchDolibarrErrors();
    }
}
