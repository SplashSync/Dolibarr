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
trait MainTrait {
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    protected function buildMainFields() {
        global $conf,$langs;
        
        //====================================================================//
        // PRODUCT SPECIFICATIONS
        //====================================================================//

        //====================================================================//
        // Weight
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("weight")
                ->Name($langs->trans("Weight"))
                ->Description($langs->trans("Weight") . "(" . $langs->trans("WeightUnitkg") . ")")
                ->MicroData("http://schema.org/Product","weight");
        
        //====================================================================//
        // Lenght
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("length")
                ->Name($langs->trans("Length"))
                ->Description($langs->trans("Length") . "(" . $langs->trans("LengthUnitm") . ")")
                ->MicroData("http://schema.org/Product","depth");
        
        //====================================================================//
        // Surface
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("surface")
                ->Name($langs->trans("Surface"))
                ->Description($langs->trans("Surface") . "(" . $langs->trans("SurfaceUnitm2") . ")")
                ->MicroData("http://schema.org/Product","surface");
        
        //====================================================================//
        // Volume
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("volume")
                ->Name($langs->trans("Volume"))
                ->Description($langs->trans("Volume") . "(" . $langs->trans("VolumeUnitm3") . ")")
                ->MicroData("http://schema.org/Product","volume");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Product Selling Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("price")
                ->Name($langs->trans("SellingPrice") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->MicroData("http://schema.org/Product","price")
                ->isLogged()
                ->isListed();
        
        if ( Splash::Local()->DolVersionCmp("4.0.0") >= 0) {
            //====================================================================//
            // WholeSale Price
            $this->FieldsFactory()->Create(SPL_T_PRICE)
                    ->Identifier("cost_price")
                    ->Name($langs->trans("CostPrice") . " (" . $conf->global->MAIN_MONNAIE . ")")
                    ->Description($langs->trans("CostPriceDescription"))
                    ->isLogged()
                    ->MicroData("http://schema.org/Product","wholesalePrice");
            
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
    protected function getMainFields($Key,$FieldName)
    {
        global $conf;
     
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                $this->Out[$FieldName] = (float) $this->C_Weight($this->Object->weight,$this->Object->weight_units);             
                break;
            case 'length':
                $this->Out[$FieldName] = (float) $this->C_Length($this->Object->length,$this->Object->length_units);             
                break;
            case 'surface':
                $this->Out[$FieldName] = (float) $this->C_Surface($this->Object->surface,$this->Object->surface_units);             
                break;
            case 'volume':
                $this->Out[$FieldName] = (float) $this->C_Volume($this->Object->volume,$this->Object->volume_units);             
                break;
            
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'price':
                //====================================================================//
                // If multiprices are enabled
                if (!empty($conf->global->PRODUIT_MULTIPRICES) )
                {
                    $PriceLevel = !empty($conf->global->SPLASH_MULTIPRICE_LEVEL) ? $conf->global->SPLASH_MULTIPRICE_LEVEL : 1;
                    $PriceType  = $this->Object->multiprices_base_type[$PriceLevel];
                    $PriceHT    = (double) $this->Object->multiprices[$PriceLevel];
                    $PriceTTC   = (double) $this->Object->multiprices_ttc[$PriceLevel];
                    $PriceVAT   = (double) $this->Object->multiprices_tva_tx[$PriceLevel];
                } else {
                    $PriceType  = $this->Object->price_base_type;
                    $PriceHT    = (double) $this->Object->price;
                    $PriceTTC   = (double) $this->Object->price_ttc;
                    $PriceVAT   = (double) $this->Object->tva_tx;
                }

                if ( $PriceType === 'TTC' ) {
                    $this->Out[$FieldName] = self::Prices()->Encode(Null, $PriceVAT, $PriceTTC, $conf->global->MAIN_MONNAIE);
                } else {
                    $this->Out[$FieldName] = self::Prices()->Encode($PriceHT, $PriceVAT, Null, $conf->global->MAIN_MONNAIE);
                }
                break;

            case 'cost_price':
                    $PriceHT    = (double) $this->Object->cost_price;
                    $this->Out[$FieldName] = self::Prices()
                            ->Encode( $PriceHT, (double)$this->Object->tva_tx, Null, $conf->global->MAIN_MONNAIE );
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
    protected function setMainFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ( (string)$Data !== (string) $this->C_Weight($this->Object->weight,$this->Object->weight_units)) {   
                    $nomalized                      =   $this->N_Weight($Data);
                    $this->Object->weight           =   $nomalized->weight;
                    $this->Object->weight_units     =   $nomalized->weight_units;
                    $this->needUpdate();
                }
                break;
            case 'length':
                if ( (string)$Data !== (string) $this->C_Length($this->Object->length,$this->Object->length_units)) {             
                    $nomalized                      =   $this->N_Length($Data);
                    $this->Object->length           =   $nomalized->length;
                    $this->Object->length_units     =   $nomalized->length_units;
                    $this->needUpdate();
                }
                break;
            case 'surface':
                if ( (string)$Data !== (string) $this->C_Surface($this->Object->surface,$this->Object->surface_units)) {             
                    $nomalized                      =   $this->N_Surface($Data);
                    $this->Object->surface          =   $nomalized->surface;
                    $this->Object->surface_units    =   $nomalized->surface_units;
                    $this->needUpdate();
                }
                break;
            case 'volume':
               if ( (string)$Data !== (string) $this->C_Volume($this->Object->volume,$this->Object->volume_units)) {             
                    $nomalized                      =   $this->N_Volume($Data);
                    $this->Object->volume           =   $nomalized->volume;
                    $this->Object->volume_units     =   $nomalized->volume_units;
                    $this->needUpdate();
                }
                break;             
            
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                $this->setProductPrice( $Data );
                break;  
            case 'cost_price':
                $this->setSimpleFloat($FieldName,$Data["ht"]);
                break;                
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    
    /**
     *  @abstract     Write New Price
     * 
     *  @return         bool
     */
    private function setProductPrice( $NewPrice )
    {
        global $user, $conf;
        
        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        $this->getMainFields(0,"price");
        //====================================================================//
        // Compare Prices
        if ( self::Prices()->Compare($this->Out["price"], $NewPrice) ) {
            return True;
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
        if (!empty($conf->global->PRODUIT_MULTIPRICES) )
        {
            $PriceLevel = !empty($conf->global->SPLASH_MULTIPRICE_LEVEL) ? $conf->global->SPLASH_MULTIPRICE_LEVEL : 1;
        } else {
            $PriceLevel = 0;
        }
                    
        //====================================================================//
        // Commit Price Update on Product Object
        //====================================================================//
        // For compatibility with previous versions => V3.5.0 or Above
        if (Splash::Local()->DolVersionCmp("3.5.0") >= 0) {
            $Result = $this->Object->updatePrice($Price,$PriceBase, $user, $NewPrice["vat"], '', $PriceLevel);
        //====================================================================//
        // For compatibility with previous versions => Below V3.5.0
        } else {    
            $Result = $this->Object->updatePrice($this->Object->id, $Price,$PriceBase, $user, $NewPrice["vat"], '', $PriceLevel);
        }
        //====================================================================//
        // Check potential Errors
        if ( $Result < 0 ) {
            $this->CatchDolibarrErrors();
            return False;
        }
        $this->needUpdate();
        return True;
    }    
    
    
}
