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
        // Invoice PaymentDueDate Date
        $this->fieldsFactory()->create(SPL_T_DATE)
                ->Identifier("date_lim_reglement")
                ->Name($langs->trans("DateMaxPayment"))
                ->MicroData("http://schema.org/Invoice", "paymentDueDate");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
                ->Identifier("total_ht")
                ->Name($langs->trans("TotalHT") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->MicroData("http://schema.org/Invoice", "totalPaymentDue")
                ->isReadOnly();
        
        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
                ->Identifier("total_ttc")
                ->Name($langs->trans("TotalTTC") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->MicroData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
                ->isReadOnly();
        
        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//

        //====================================================================//
        // Is Draft
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("isDraft")
                ->Name($langs->trans("Invoice") . " : " . $langs->trans("Draft"))
                ->Group(html_entity_decode($langs->trans("Status")))
                ->MicroData("http://schema.org/PaymentStatusType", "InvoiceDraft")
                ->Association("isDraft", "isCanceled", "isValidated")
                ->isReadOnly();

        //====================================================================//
        // Is Canceled
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("isCanceled")
                ->Name($langs->trans("Invoice") . " : " . $langs->trans("Canceled"))
                ->Group(html_entity_decode($langs->trans("Status")))
                ->MicroData("http://schema.org/PaymentStatusType", "PaymentDeclined")
                ->Association("isDraft", "isCanceled", "isValidated")
                ->isReadOnly();
        
        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("isValidated")
                ->Name($langs->trans("Invoice") . " : " . $langs->trans("Validated"))
                ->Group(html_entity_decode($langs->trans("Status")))
                ->MicroData("http://schema.org/PaymentStatusType", "PaymentDue")
                ->Association("isDraft", "isCanceled", "isValidated")
                ->isReadOnly();

        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("isPaid")
                ->Name($langs->trans("Invoice") . " : " . $langs->trans("Paid"))
                ->Group(html_entity_decode($langs->trans("Status")))
                ->MicroData("http://schema.org/PaymentStatusType", "PaymentComplete")
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
            case 'date_lim_reglement':
                $date   =   $this->Object->date_lim_reglement;
                $this->Out[$FieldName] = !empty($date)?dol_print_date($date, '%Y-%m-%d'):null;
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
    protected function getStateFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//

            case 'isDraft':
                $this->Out[$FieldName]  = ( $this->Object->statut == 0 )    ?   true:false;
                break;
            case 'isCanceled':
                $this->Out[$FieldName]  = ( $this->Object->statut == 3 )   ?   true:false;
                break;
            case 'isValidated':
                $this->Out[$FieldName]  = ( $this->Object->statut == 1 )    ?   true:false;
                break;
            case 'isPaid':
                $this->Out[$FieldName]  = ( $this->Object->statut == 2 )    ?   true:false;
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
                break;
                
            //====================================================================//
            // Invoice Payment Due Date
            case 'date_lim_reglement':
                if (dol_print_date($this->Object->$FieldName, 'standard') === $Data) {
                    break;
                }
                $this->setSimple($FieldName, $Data);
                break;
                
            //====================================================================//
            // PAYMENT STATUS
            //====================================================================//
            case 'isPaid':
                $this->setPaidFlag($Data);
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return       bool
     */
    private function setPaidFlag($Data)
    {
        global $user;
        
        //====================================================================//
        // If Status Is Not Validated => Cannot Update This Flag
        if (( $Data == $this->Object->paye ) || ( $this->Object->statut < 1 )) {
            return true;
        }
        
        if ($Data) {
            //====================================================================//
            // Set Paid using Dolibarr Function
            if (($this->Object->statut == 1) && ( $this->Object->set_paid($user) != 1 )) {
                return $this->catchDolibarrErrors();
            }
        } else {
            //====================================================================//
            // Set UnPaid using Dolibarr Function
            if (($this->Object->statut == 2) && ( $this->Object->set_unpaid($user) != 1 )) {
                return $this->catchDolibarrErrors();
            }
        }
       
        //====================================================================//
        // Setup Current Object not to Overite changes with Update
        $this->Object->paye     = ( $Data ? 1 : 0 );
        
        return true;
    }
}
