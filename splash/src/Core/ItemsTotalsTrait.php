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

namespace Splash\Local\Core;

use CommandeFournisseurLigne;
use FactureLigne;
use OrderLine;
use Product;
use PropaleLigne;
use SupplierInvoiceLine;

/**
 * Dolibarr Orders & Invoices Items Totals Fields
 *
 * @phpstan-type Line FactureLigne|OrderLine|CommandeFournisseurLigne|SupplierInvoiceLine|PropaleLigne
 */
trait ItemsTotalsTrait
{
    /**
     * Build Fields using FieldFactory
     */
    private function buildItemsTotalsFields(): void
    {
        global $conf;

        //====================================================================//
        // Order Total Products
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price_products")
            ->name(sprintf("Products (%s)", $conf->global->MAIN_MONNAIE))
            ->microData("http://schema.org/Invoice", "totalProducts")
            ->group("Meta")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Total Services
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price_services")
            ->name(sprintf("Service (%s)", $conf->global->MAIN_MONNAIE))
            ->microData("http://schema.org/Invoice", "totalShipping")
            ->group("Meta")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Total Shipping
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price_discount")
            ->name(sprintf("Discounts (%s)", $conf->global->MAIN_MONNAIE))
            ->microData("http://schema.org/Invoice", "totalDiscount")
            ->group("Meta")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Total Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price_total")
            ->name(sprintf("Grand Total (%s)", $conf->global->MAIN_MONNAIE))
            ->microData("http://schema.org/Invoice", "total")
            ->group("Meta")
            ->isReadOnly()
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
    private function getItemsTotalsFields(string $key, string $fieldName): void
    {
        global $conf;

        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'price_total':
                $this->out[$fieldName] = self::prices()->encode(
                    null,
                    self::toVatPercents($this->object->total_ht, $this->object->total_ttc),
                    $this->object->total_ttc,
                    $this->object->multicurrency_code ?: $conf->global->MAIN_MONNAIE
                );

                break;
            case 'price_products':
                $this->out[$fieldName] = $this->getItemTypeTotal(Product::TYPE_PRODUCT);

                break;
            case 'price_services':
                $this->out[$fieldName] = $this->getItemTypeTotal(Product::TYPE_SERVICE);

                break;
            case 'price_discount':
                $this->out[$fieldName] = self::prices()->encode(
                    0,
                    0,
                    null,
                    $this->object->multicurrency_code ?: $conf->global->MAIN_MONNAIE
                );

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Compute Total Price for Items Type
     */
    private function getItemTypeTotal(int $itemType = null): array
    {
        global $conf;

        $itemType ??= Product::TYPE_PRODUCT;
        $totalHt = $totalTtc = 0;

        foreach ($this->object->lines as $orderLine) {
            //====================================================================//
            // Filter on Line Item Type
            if ($orderLine->product_type != $itemType) {
                continue;
            }
            //====================================================================//
            // Sum Line Totals
            $totalHt += $orderLine->total_ht;
            $totalTtc += $orderLine->total_ttc;
        }

        return self::prices()->encode(
            null,
            self::toVatPercents($totalHt, $totalTtc),
            $totalHt,
            $this->object->multicurrency_code ?: $conf->global->MAIN_MONNAIE
        );
    }

    /**
     * Compute Vat Percentile from Both Price Values
     *
     * @param float $priceTaxExcl
     * @param float $priceTaxIncl
     *
     * @return float
     */
    private static function toVatPercents(float $priceTaxExcl, float $priceTaxIncl): float
    {
        return (($priceTaxExcl > 0) && ($priceTaxIncl > 0) && ($priceTaxExcl <= $priceTaxIncl))
            ? 100 * ($priceTaxIncl - $priceTaxExcl) / $priceTaxExcl
            : 0.0
        ;
    }
}
