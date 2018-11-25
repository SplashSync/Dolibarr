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

namespace   Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Products Main Fields
 */
trait MainTrait
{
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
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
        
        if (Splash::local()->dolVersionCmp("4.0.0") >= 0) {
            //====================================================================//
            // WholeSale Price
            $this->fieldsFactory()->create(SPL_T_PRICE)
                    ->Identifier("cost_price")
                    ->Name($langs->trans("CostPrice") . " (" . $conf->global->MAIN_MONNAIE . ")")
                    ->Description($langs->trans("CostPriceDescription"))
                    ->isLogged()
                    ->MicroData("http://schema.org/Product", "wholesalePrice");
        }
        
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
    protected function getMainSpecFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                $this->out[$FieldName] = (float) $this->convertWeight(
                    $this->object->weight,
                    $this->object->weight_units
                );
                break;
            case 'length':
                $this->out[$FieldName] = (float) $this->convertLength(
                    $this->object->length,
                    $this->object->length_units
                );
                break;
            case 'surface':
                $this->out[$FieldName] = (float) $this->convertSurface(
                    $this->object->surface,
                    $this->object->surface_units
                );
                break;
            case 'volume':
                $this->out[$FieldName] = (float) $this->convertVolume(
                    $this->object->volume,
                    $this->object->volume_units
                );
                break;
            
            default:
                return;
        }
        
        unset($this->in[$Key]);
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getMainPriceFields($Key, $FieldName)
    {
        global $conf;
        
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'price':
                //====================================================================//
                // If multiprices are enabled
                if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
                    $CfgPriceLevel = $conf->global->SPLASH_MULTIPRICE_LEVEL;
                    $PriceLevel = !empty($CfgPriceLevel) ? $CfgPriceLevel : 1;
                    $PriceType  = $this->object->multiprices_base_type[$PriceLevel];
                    $PriceHT    = (double) $this->object->multiprices[$PriceLevel];
                    $PriceTTC   = (double) $this->object->multiprices_ttc[$PriceLevel];
                    $PriceVAT   = (double) $this->object->multiprices_tva_tx[$PriceLevel];
                } else {
                    $PriceType  = $this->object->price_base_type;
                    $PriceHT    = (double) $this->object->price;
                    $PriceTTC   = (double) $this->object->price_ttc;
                    $PriceVAT   = (double) $this->object->tva_tx;
                }

                if ($PriceType === 'TTC') {
                    $this->out[$FieldName] = self::prices()->Encode(
                        null,
                        $PriceVAT,
                        $PriceTTC,
                        $conf->global->MAIN_MONNAIE
                    );
                } else {
                    $this->out[$FieldName] = self::prices()->Encode(
                        $PriceHT,
                        $PriceVAT,
                        null,
                        $conf->global->MAIN_MONNAIE
                    );
                }
                break;

            case 'cost_price':
                    $PriceHT    = (double) $this->object->cost_price;
                    $this->out[$FieldName] = self::prices()
                            ->Encode($PriceHT, (double)$this->object->tva_tx, null, $conf->global->MAIN_MONNAIE);
                break;

            default:
                return;
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
     */
    protected function setMainSpecFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ((string)$Data !== (string) $this->convertWeight(
                        
                    $this->object->weight,
                    $this->object->weight_units
                )) {
                    $nomalized                      =   $this->normalizeWeight($Data);
                    $this->object->weight           =   $nomalized->weight;
                    $this->object->weight_units     =   $nomalized->weight_units;
                    $this->needUpdate();
                }
                break;
            case 'length':
                if ((string)$Data !== (string) $this->convertLength(
                    $this->object->length,
                    $this->object->length_units
                )) {
                    $nomalized                      =   $this->normalizeLength($Data);
                    $this->object->length           =   $nomalized->length;
                    $this->object->length_units     =   $nomalized->length_units;
                    $this->needUpdate();
                }
                break;
            case 'surface':
                if ((string)$Data !== (string) $this->convertSurface(
                    $this->object->surface,
                    $this->object->surface_units
                )) {
                    $nomalized                      =   $this->normalizeSurface($Data);
                    $this->object->surface          =   $nomalized->surface;
                    $this->object->surface_units    =   $nomalized->surface_units;
                    $this->needUpdate();
                }
                break;
            case 'volume':
                if ((string)$Data !== (string) $this->convertVolume(
                    $this->object->volume,
                    $this->object->volume_units
                )) {
                    $nomalized                      =   $this->normalizeVolume($Data);
                    $this->object->volume           =   $nomalized->volume;
                    $this->object->volume_units     =   $nomalized->volume_units;
                    $this->needUpdate();
                }
                break;
                
            default:
                return;
        }
        unset($this->in[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    protected function setMainPriceFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                $this->setProductPrice($Data);
                break;
            case 'cost_price':
                $this->setSimpleFloat($FieldName, $Data["ht"]);
                break;
                
            default:
                return;
        }
        unset($this->in[$FieldName]);
    }
    
    /**
     *  @abstract     Write New Price
     *
     *  @return         bool
     */
    private function setProductPrice($NewPrice)
    {
        global $user, $conf;
        
        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        $this->getMainPriceFields(0, "price");
        //====================================================================//
        // Compare Prices
        if (self::prices()->Compare($this->out["price"], $NewPrice)) {
            return true;
        }
                        
        //====================================================================//
        // Perform Prices Update
        //====================================================================//

        //====================================================================//
        // Update Based on TTC Price
        if ($NewPrice["base"]) {
            $Price      = $NewPrice["ttc"];
            $PriceBase  = "TTC";
        //====================================================================//
        // Update Based on HT Price
        } else {
            $Price      = $NewPrice["ht"];
            $PriceBase  = "HT";
        }

        //====================================================================//
        // If multiprices are enabled
        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
            $PriceLevel = !empty($conf->global->SPLASH_MULTIPRICE_LEVEL) ? $conf->global->SPLASH_MULTIPRICE_LEVEL : 1;
        } else {
            $PriceLevel = 0;
        }
                    
        //====================================================================//
        // Commit Price Update on Product Object
        //====================================================================//
        // For compatibility with previous versions => V3.5.0 or Above
        if (Splash::local()->dolVersionCmp("3.5.0") >= 0) {
            $Result = $this->object->updatePrice($Price, $PriceBase, $user, $NewPrice["vat"], '', $PriceLevel);
        //====================================================================//
        // For compatibility with previous versions => Below V3.5.0
        } else {
            $Result = $this->object->updatePrice(
                $this->object->id,
                $Price,
                $PriceBase,
                $user,
                $NewPrice["vat"],
                '',
                $PriceLevel
            );
        }
        //====================================================================//
        // Check potential Errors
        if ($Result < 0) {
            $this->catchDolibarrErrors();
            return false;
        }
        $this->needUpdate();
        return true;
    }
}
