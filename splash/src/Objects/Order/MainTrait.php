<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Order;

use Exception;

/**
 * Dolibarr Customer Orders Fields
 */
trait MainTrait
{
    /**
     * @var null|bool
     */
    private ?bool $updateBilled = false;

    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMainFields()
    {
        global $langs, $conf;

        //====================================================================//
        // Estimated Delivery Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier("date_livraison")
            ->name($langs->trans("DeliveryDate"))
            ->microData("http://schema.org/ParcelDelivery", "expectedArrivalUntil")
        ;

        //====================================================================//
        // PRICES INFORMATION
        //====================================================================//

        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("total_ht")
            ->name($langs->trans("TotalHT")." (".$conf->global->MAIN_MONNAIE.")")
            ->microData("http://schema.org/Invoice", "totalPaymentDue")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("total_ttc")
            ->name($langs->trans("TotalTTC")." (".$conf->global->MAIN_MONNAIE.")")
            ->microData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
            ->isReadOnly()
        ;

        //====================================================================//
        // ORDER STATUS FLAGS
        //====================================================================//

        $groupName = $langs->trans("Status");
        //====================================================================//
        // Is Draft
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isdraft")
            ->group($groupName)
            ->name($langs->trans("Order")." : ".$langs->trans("Draft"))
            ->microData("http://schema.org/OrderStatus", "OrderDraft")
            ->association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Canceled
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("iscanceled")
            ->group($groupName)
            ->name($langs->trans("Order")." : ".$langs->trans("Canceled"))
            ->microData("http://schema.org/OrderStatus", "OrderCancelled")
            ->association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isvalidated")
            ->group($groupName)
            ->name($langs->trans("Order")." : ".$langs->trans("Validated"))
            ->microData("http://schema.org/OrderStatus", "OrderProcessing")
            ->association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Closed
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("isclosed")
            ->name($langs->trans("Order")." : ".$langs->trans("Closed"))
            ->group($groupName)
            ->microData("http://schema.org/OrderStatus", "OrderDelivered")
            ->association("isdraft", "iscanceled", "isvalidated", "isclosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("billed")
            ->group($groupName)
            ->name($langs->trans("Order")." : ".$langs->trans("Paid"))
            ->microData("http://schema.org/OrderStatus", "OrderPaid")
            ->isNotTested()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainFields(string $key, string $fieldName): void
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
    protected function getTotalsFields(string $key, string $fieldName): void
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
    protected function getStatesFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//

            case 'isdraft':
                $this->out[$fieldName] = (0 == $this->object->statut);

                break;
            case 'iscanceled':
                $this->out[$fieldName] = (-1 == $this->object->statut);

                break;
            case 'isvalidated':
                $this->out[$fieldName] = (1 == $this->object->statut);

                break;
            case 'isclosed':
                $this->out[$fieldName] = (3 == $this->object->statut);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|scalar $fieldData Field Data
     *
     * @throws Exception
     *
     * @return void
     */
    protected function setMainFields(string $fieldName, $fieldData): void
    {
        global $user;

        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Order Official Date
            case 'date_livraison':
                if (empty($fieldData) || dol_print_date($this->object->{$fieldName}, 'standard') === $fieldData) {
                    break;
                }
                $dateTime = new \DateTime((string) $fieldData);
                if ($this->object->set_date_livraison($user, $dateTime->getTimestamp()) < 0) {
                    $this->catchDolibarrErrors();
                }
                //====================================================================//
                // FIX for V12 & 13 - Move to delivery_date in next releases.
                $this->setSimple('delivery_date', $this->object->date_livraison);
                $this->needUpdate();

                break;
                //====================================================================//
                // ORDER INVOICED FLAG
                //====================================================================//
            case 'billed':
                if ($this->object->billed == $fieldData) {
                    break;
                }
                $this->updateBilled = !empty($fieldData);
                $this->updateBilledFlag();

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);
    }

    /**
     * Update Order Billed Flag if Required & Possible
     *
     * @return void
     */
    protected function updateBilledFlag(): void
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
            $this->object->classifyUnBilled($user);
        }
        $this->updateBilled = null;
        $this->catchDolibarrErrors();
    }
}
