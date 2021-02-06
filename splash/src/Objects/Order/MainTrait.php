<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Order;

/**
 * Dolibarr Customer Orders Fields
 */
trait MainTrait
{
    /**
     * @var null|bool
     */
    private $updateBilled;

    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMainFields()
    {
        global $langs,$conf;

        //====================================================================//
        // Delivry Estimated Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->Identifier("date_livraison")
            ->Name($langs->trans("DeliveryDate"))
            ->MicroData("http://schema.org/ParcelDelivery", "expectedArrivalUntil");

        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("total_ht")
            ->Name($langs->trans("TotalHT")." (".$conf->global->MAIN_MONNAIE.")")
            ->MicroData("http://schema.org/Invoice", "totalPaymentDue")
            ->isReadOnly();

        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("total_ttc")
            ->Name($langs->trans("TotalTTC")." (".$conf->global->MAIN_MONNAIE.")")
            ->MicroData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
            ->isReadOnly();

        //====================================================================//
        // ORDER STATUS FLAGS
        //====================================================================//

        //====================================================================//
        // Is Draft
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isdraft")
            ->Group(html_entity_decode($langs->trans("Status")))
            ->Name($langs->trans("Order")." : ".$langs->trans("Draft"))
            ->MicroData("http://schema.org/OrderStatus", "OrderDraft")
            ->Association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly();

        //====================================================================//
        // Is Canceled
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("iscanceled")
            ->Group(html_entity_decode($langs->trans("Status")))
            ->Name($langs->trans("Order")." : ".$langs->trans("Canceled"))
            ->MicroData("http://schema.org/OrderStatus", "OrderCancelled")
            ->Association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly();

        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isvalidated")
            ->Group(html_entity_decode($langs->trans("Status")))
            ->Name($langs->trans("Order")." : ".$langs->trans("Validated"))
            ->MicroData("http://schema.org/OrderStatus", "OrderProcessing")
            ->Association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly();

        //====================================================================//
        // Is Closed
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isclosed")
            ->Name($langs->trans("Order")." : ".$langs->trans("Closed"))
            ->Group(html_entity_decode($langs->trans("Status")))
            ->MicroData("http://schema.org/OrderStatus", "OrderDelivered")
            ->Association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly();

        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("billed")
            ->Group(html_entity_decode($langs->trans("Status")))
            ->Name($langs->trans("Order")." : ".$langs->trans("Paid"))
            ->MicroData("http://schema.org/OrderStatus", "OrderPaid")
            ->isNotTested();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Order Delivery Date
            case 'date_livraison':
                $dateLivraison = $this->object->date_livraison;
                $this->out[$fieldName] = !empty($dateLivraison)?dol_print_date($dateLivraison, '%Y-%m-%d'):null;

                break;
            //====================================================================//
            // ORDER INVOICED FLAG
            //====================================================================//
            case 'billed':
                $this->getSimple($fieldName);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getTotalsFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_ht':
            case 'total_ttc':
            case 'total_vat':
                $this->getSimple($fieldName);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getStatesFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//

            case 'isdraft':
                $this->out[$fieldName] = (0 == $this->object->statut)    ?   true:false;

                break;
            case 'iscanceled':
                $this->out[$fieldName] = (-1 == $this->object->statut)   ?   true:false;

                break;
            case 'isvalidated':
                $this->out[$fieldName] = (1 == $this->object->statut)    ?   true:false;

                break;
            case 'isclosed':
                $this->out[$fieldName] = (3 == $this->object->statut)    ?   true:false;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setMainFields($fieldName, $fieldData)
    {
        global $user;

        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Order Official Date
            case 'date_livraison':
                if (dol_print_date($this->object->{$fieldName}, 'standard') === $fieldData) {
                    break;
                }
                $this->object->set_date_livraison($user, $fieldData);
                $this->needUpdate();

                break;
            //====================================================================//
            // ORDER INVOICED FLAG
            //====================================================================//
            case 'billed':
                if ($this->object->billed == $fieldData) {
                    break;
                }
                $this->updateBilled = $fieldData;
                $this->updateBilledFlag();

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);
    }

    /**
     * Update Order Billed Flag if Required & Possibe
     *
     * @return void
     */
    protected function updateBilledFlag()
    {
        global $user;

        // Not Required
        if (is_null($this->updateBilled)) {
            return;
        }
        // Not Possible
        if ($this->object->statut <= \Commande::STATUS_DRAFT) {
            return;
        }

        // Update
        if ($this->updateBilled) {
            $this->object->classifyBilled($user);
        } else {
            $this->object->classifyUnBilled();
        }
        $this->updateBilled = null;
        $this->catchDolibarrErrors();
    }
}
