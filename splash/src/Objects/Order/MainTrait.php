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
    private ?bool $updateBilled = null;

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
                $dateLivraison = $this->object->delivery_date;
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
                $deliveryDate = (int) $this->object->delivery_date;
                if (empty($fieldData) || dol_print_date($deliveryDate, 'standard') === $fieldData) {
                    break;
                }
                $dateTime = new \DateTime((string) $fieldData);
                if ($this->object->setDeliveryDate($user, $dateTime->getTimestamp()) < 0) {
                    $this->catchDolibarrErrors();
                }
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
        if ($this->getRawStatus() <= \Commande::STATUS_DRAFT) {
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
