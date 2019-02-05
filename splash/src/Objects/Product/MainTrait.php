<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;

/**
 * Dolibarr Products Main Fields
 */
trait MainTrait
{
    /**
     * Build Address Fields using FieldFactory
     */
    protected function buildMainFields()
    {
        global $conf,$langs;
        
        //====================================================================//
        // PRODUCT SPECIFICATIONS
        //====================================================================//

        //====================================================================//
        // Weight
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("weight")
            ->Name($langs->trans("Weight"))
            ->Description($langs->trans("Weight") . "(" . $langs->trans("WeightUnitkg") . ")")
            ->MicroData("http://schema.org/Product", "weight");
        
        //====================================================================//
        // Lenght
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("length")
            ->Name($langs->trans("Length"))
            ->Description($langs->trans("Length") . "(" . $langs->trans("LengthUnitm") . ")")
            ->MicroData("http://schema.org/Product", "depth");
        
        //====================================================================//
        // Surface
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("surface")
            ->Name($langs->trans("Surface"))
            ->Description($langs->trans("Surface") . "(" . $langs->trans("SurfaceUnitm2") . ")")
            ->MicroData("http://schema.org/Product", "surface");
        
        //====================================================================//
        // Volume
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("volume")
            ->Name($langs->trans("Volume"))
            ->Description($langs->trans("Volume") . "(" . $langs->trans("VolumeUnitm3") . ")")
            ->MicroData("http://schema.org/Product", "volume");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Product Selling Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->Identifier("price")
            ->Name($langs->trans("SellingPrice") . " (" . $conf->global->MAIN_MONNAIE . ")")
            ->MicroData("http://schema.org/Product", "price")
            ->isLogged()
            ->isListed();
        
        if (Local::dolVersionCmp("4.0.0") >= 0) {
            //====================================================================//
            // WholeSale Price
            $this->fieldsFactory()->create(SPL_T_PRICE)
                ->Identifier("cost_price")
                ->Name($langs->trans("CostPrice") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->Description($langs->trans("CostPriceDescription"))
                ->isLogged()
                ->MicroData("http://schema.org/Product", "wholesalePrice");
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainSpecFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                $this->out[$fieldName] = (float) $this->convertWeight(
                    $this->object->weight,
                    $this->object->weight_units
                );

                break;
            case 'length':
                $this->out[$fieldName] = (float) $this->convertLength(
                    $this->object->length,
                    $this->object->length_units
                );

                break;
            case 'surface':
                $this->out[$fieldName] = (float) $this->convertSurface(
                    $this->object->surface,
                    $this->object->surface_units
                );

                break;
            case 'volume':
                $this->out[$fieldName] = (float) $this->convertVolume(
                    $this->object->volume,
                    $this->object->volume_units
                );

                break;
            default:
                return;
        }
        
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param null|string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainPriceFields($key, $fieldName)
    {
        global $conf;
        
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'price':
                //====================================================================//
                // If multiprices are enabled
                if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
                    $cfgPriceLevel = isset($conf->global->SPLASH_MULTIPRICE_LEVEL) 
                            ? $conf->global->SPLASH_MULTIPRICE_LEVEL
                            : null;
                    $priceLevel = !empty($cfgPriceLevel) ? $cfgPriceLevel : 1;
                    $priceType  = $this->object->multiprices_base_type[$priceLevel];
                    $priceHT    = (double) $this->object->multiprices[$priceLevel];
                    $priceTTC   = (double) $this->object->multiprices_ttc[$priceLevel];
                    $priceVAT   = (double) $this->object->multiprices_tva_tx[$priceLevel];
                } else {
                    $priceType  = $this->object->price_base_type;
                    $priceHT    = (double) $this->object->price;
                    $priceTTC   = (double) $this->object->price_ttc;
                    $priceVAT   = (double) $this->object->tva_tx;
                }

                if ('TTC' === $priceType) {
                    $this->out[$fieldName] = self::prices()->Encode(
                        null,
                        $priceVAT,
                        $priceTTC,
                        $conf->global->MAIN_MONNAIE
                    );
                } else {
                    $this->out[$fieldName] = self::prices()->Encode(
                        $priceHT,
                        $priceVAT,
                        null,
                        $conf->global->MAIN_MONNAIE
                    );
                }

                break;
            case 'cost_price':
                    $priceHT    = (double) $this->object->cost_price;
                    $this->out[$fieldName] = self::prices()
                        ->Encode($priceHT, (double)$this->object->tva_tx, null, $conf->global->MAIN_MONNAIE);

                break;
            default:
                return;
        }
            
        if($key != null) {
            unset($this->in[$key]);
        }
    }
    
    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setMainSpecFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ((string)$fieldData !== (string) $this->convertWeight(
                    $this->object->weight,
                    $this->object->weight_units
                )) {
                    $nomalized                      =   $this->normalizeWeight($fieldData);
                    $this->object->weight           =   $nomalized->weight;
                    $this->object->weight_units     =   $nomalized->weight_units;
                    $this->needUpdate();
                }

                break;
            case 'length':
                if ((string)$fieldData !== (string) $this->convertLength(
                    $this->object->length,
                    $this->object->length_units
                )) {
                    $nomalized                      =   $this->normalizeLength($fieldData);
                    $this->object->length           =   $nomalized->length;
                    $this->object->length_units     =   $nomalized->length_units;
                    $this->needUpdate();
                }

                break;
            case 'surface':
                if ((string)$fieldData !== (string) $this->convertSurface(
                    $this->object->surface,
                    $this->object->surface_units
                )) {
                    $nomalized                      =   $this->normalizeSurface($fieldData);
                    $this->object->surface          =   $nomalized->surface;
                    $this->object->surface_units    =   $nomalized->surface_units;
                    $this->needUpdate();
                }

                break;
            case 'volume':
                if ((string)$fieldData !== (string) $this->convertVolume(
                    $this->object->volume,
                    $this->object->volume_units
                )) {
                    $nomalized                      =   $this->normalizeVolume($fieldData);
                    $this->object->volume           =   $nomalized->volume;
                    $this->object->volume_units     =   $nomalized->volume_units;
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
    
    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    protected function setMainPriceFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                $this->setProductPrice($fieldData);

                break;
            case 'cost_price':
                $this->setSimpleFloat($fieldName, $fieldData["ht"]);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
    
    /**
     * Write New Price
     *
     * @param array $newPrice
     *
     * @return bool
     */
    private function setProductPrice($newPrice)
    {
        global $user, $conf;
        
        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        $this->getMainPriceFields(null, "price");
        //====================================================================//
        // Compare Prices
        if (self::prices()->Compare($this->out["price"], $newPrice)) {
            return true;
        }
                        
        //====================================================================//
        // Perform Prices Update
        //====================================================================//

        //====================================================================//
        // Update Based on TTC Price
        if ($newPrice["base"]) {
            $price      = $newPrice["ttc"];
            $priceBase  = "TTC";
        //====================================================================//
        // Update Based on HT Price
        } else {
            $price      = $newPrice["ht"];
            $priceBase  = "HT";
        }

        //====================================================================//
        // If multiprices are enabled
        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
            $priceLevel = !empty($conf->global->SPLASH_MULTIPRICE_LEVEL) ? $conf->global->SPLASH_MULTIPRICE_LEVEL : 1;
        } else {
            $priceLevel = 0;
        }
                    
        //====================================================================//
        // Commit Price Update on Product Object
        //====================================================================//
        // For compatibility with previous versions => V3.5.0 or Above
        $result = $this->object->updatePrice($price, $priceBase, $user, $newPrice["vat"], 0.0, $priceLevel);
        
        //====================================================================//
        // Check potential Errors
        if ($result < 0) {
            $this->catchDolibarrErrors();

            return false;
        }
        $this->needUpdate();

        return true;
    }
}
