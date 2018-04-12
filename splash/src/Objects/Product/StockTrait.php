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
 * @abstract    Dolibarr Products Stock Fields
 */
trait StockTrait
{

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    protected function buildStockFields()
    {
        global $langs;
        
        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//
        
        //====================================================================//
        // Stock Reel
        $this->fieldsFactory()->Create(SPL_T_INT)
                ->Identifier("stock_reel")
                ->Name($langs->trans("RealStock"))
                ->MicroData("http://schema.org/Offer", "inventoryLevel")
                ->isListed();

        //====================================================================//
        // Stock Alerte Level
        $this->fieldsFactory()->Create(SPL_T_INT)
                ->Identifier("seuil_stock_alerte")
                ->Name($langs->trans("StockLimit"))
                ->MicroData("http://schema.org/Offer", "inventoryAlertLevel");
                
        //====================================================================//
        // Stock Alerte Flag
        $this->fieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("stock_alert_flag")
                ->Name($langs->trans("StockTooLow"))
                ->MicroData("http://schema.org/Offer", "inventoryAlertFlag")
                ->isReadOnly();
        
        //====================================================================//
        // Stock Expected Level
        $this->fieldsFactory()->Create(SPL_T_INT)
                ->Identifier("desiredstock")
                ->Name($langs->trans("DesiredStock"))
                ->MicroData("http://schema.org/Offer", "inventoryTargetLevel");

        //====================================================================//
        // Average Purchase price value
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("pmp")
                ->Name($langs->trans("EstimatedStockValueShort"))
                ->MicroData("http://schema.org/Offer", "averagePrice")
                ->isReadOnly();
        
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
    protected function getStockFields($Key, $FieldName)
    {

        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // STOCK INFORMATIONS
            //====================================================================//

            //====================================================================//
            // Stock Alerte Flag
            case 'stock_alert_flag':
                $this->Out[$FieldName] = ( $this->Object->stock_reel < $this->Object->seuil_stock_alerte );
                break;
            
            //====================================================================//
            // Stock Direct Reading
            case 'stock_reel':
            case 'seuil_stock_alerte':
            case 'desiredstock':
            case 'pmp':
                $this->getSimple($FieldName, "Object", 0);
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
    protected function setStockFields($FieldName, $Data)
    {

        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Writtings
            case 'stock_reel':
                $this->setProductStock($Data);
                break;
                
            //====================================================================//
            // Direct Writtings
            case 'seuil_stock_alerte':
            case 'desiredstock':
            case 'pmp':
                $this->setSimple($FieldName, $Data);
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     * @abstract    Create Stock Transaction to Update Products Stocks Level
     *
     * @param       int $NewStock New Product Stock
     *
     * @return      bool
     */
    private function setProductStock($NewStock)
    {
        global $conf, $langs, $user;

        //====================================================================//
        // Compare Current Product Stock with new Value
        if ($this->Object->stock_reel == $NewStock) {
            return true;
        }
        //====================================================================//
        // Update Product Stock
        $delta  =   $this->Object->stock_reel - $NewStock;
        //====================================================================//
        // Verify Default Product Stock is defined
        if (empty($conf->global->SPLASH_STOCK)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Product : No Local WareHouse Defined.");
        }
        //====================================================================//
        // Update Product Stock
        $Result = $this->Object->correct_stock(
            $user,                                      // Current User Object
            $conf->global->SPLASH_STOCK,                // Impacted Stock Id
            abs($delta),                                // Quantity to Move
            ($delta > 0)?1:0,                           // Direnction 0 = add, 1 = remove
            $langs->trans("Updated by Splash Module"),  // Operation Comment
            $this->Object->price                        // Product Price for PMP
        );
        //====================================================================//
        // Check potential Errors
        if ($Result < 0) {
            $this->catchDolibarrErrors();
            return false;
        }
        return true;
    }
}
