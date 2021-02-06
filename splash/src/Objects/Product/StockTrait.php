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

namespace   Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Local\Services\ConfigManager;

/**
 * Dolibarr Products Stock Fields
 */
trait StockTrait
{
    /**
     * Cache for Products Stocks Locations Ids
     *
     * @var array
     */
    private static $locationIds;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildStockFields()
    {
        global $langs;

        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//

        //====================================================================//
        // Default Stock Location
        if (Local::dolVersionCmp("8.0.0") >= 0) {
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("fk_default_warehouse")
                ->addChoices($this->getStockLocations())
                ->group($langs->trans("Stock"))
                ->Name($langs->trans("DefaultWarehouse"))
                ->MicroData("http://schema.org/Offer", "inventoryLocation");
        }

        //====================================================================//
        // Stock Reel
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("stock_reel")
            ->Name($langs->trans("RealStock"))
            ->group($langs->trans("Stock"))
            ->MicroData("http://schema.org/Offer", "inventoryLevel")
            ->isReadOnly(ConfigManager::isMultiStocksMode())
            ->isListed();

        //====================================================================//
        // Stock Alerte Level
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("seuil_stock_alerte")
            ->Name($langs->trans("StockLimit"))
            ->group($langs->trans("Stock"))
            ->MicroData("http://schema.org/Offer", "inventoryAlertLevel");

        //====================================================================//
        // Stock Alerte Flag
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("stock_alert_flag")
            ->Name($langs->trans("StockTooLow"))
            ->group($langs->trans("Stock"))
            ->MicroData("http://schema.org/Offer", "inventoryAlertFlag")
            ->isReadOnly();

        //====================================================================//
        // Stock Expected Level
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("desiredstock")
            ->Name($langs->trans("DesiredStock"))
            ->group($langs->trans("Stock"))
            ->MicroData("http://schema.org/Offer", "inventoryTargetLevel");

        //====================================================================//
        // Average Purchase price value
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("pmp")
            ->Name($langs->trans("EstimatedStockValueShort"))
            ->group($langs->trans("Stock"))
            ->MicroData("http://schema.org/Offer", "averagePrice")
            ->isReadOnly();
    }

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMultiStockFields()
    {
        global $langs;

        //====================================================================//
        // MULTI-LOCATIONS STOCKS
        //====================================================================//

        if (!ConfigManager::isMultiStocksMode()) {
            return;
        }

        foreach ($this->getStockLocationsIds() as $locationName => $locationId) {
            //====================================================================//
            // Warehouse Stock
            $this->fieldsFactory()->create(SPL_T_INT)
                ->identifier("stock_level_".$locationId)
                ->name("[".$locationName."] ".$langs->trans("Stock"))
                ->group($langs->trans("Stock"))
                ->microData(
                    "http://schema.org/Offer",
                    "inventoryLevel".ucfirst($locationName)
                )
            ;
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
    protected function getStockFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // STOCK INFORMATIONS
            //====================================================================//

            //====================================================================//
            // Stock Alerte Flag
            case 'stock_alert_flag':
                $this->out[$fieldName] = ($this->object->stock_reel < $this->object->seuil_stock_alerte);

                break;
            //====================================================================//
            // Stock Direct Reading
            case 'stock_reel':
            case 'seuil_stock_alerte':
            case 'desiredstock':
            case 'pmp':
                $this->getSimple($fieldName, "object", 0);

                break;
            //====================================================================//
            // Default Stock Location
            case 'fk_default_warehouse':
                $this->out[$fieldName] = $this->getDefaultLocation();

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
    protected function getMultiStockFields($key, $fieldName)
    {
        //====================================================================//
        // MULTI-LOCATIONS STOCKS
        //====================================================================//

        if (!ConfigManager::isMultiStocksMode()) {
            return;
        }

        foreach ($this->getStockLocationsIds() as $locationId) {
            //====================================================================//
            // Detect is Requested Location
            if ($fieldName != "stock_level_".$locationId) {
                continue;
            }
            //====================================================================//
            // Load Locations Stocks
            if (empty($this->object->stock_warehouse)) {
                $this->object->load_stock();
            }
            //====================================================================//
            // Read Real Stock for Location
            $this->out[$fieldName] = isset($this->object->stock_warehouse[$locationId]->real)
                ? $this->object->stock_warehouse[$locationId]->real
                : 0;
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
    protected function setStockFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writtings
            case 'stock_reel':
                $this->setProductStock($fieldData);

                break;
            //====================================================================//
            // Direct Writtings
            case 'seuil_stock_alerte':
            case 'desiredstock':
            case 'pmp':
                $this->setSimple($fieldName, $fieldData);

                break;
            //====================================================================//
            // Default Stock Location
            case 'fk_default_warehouse':
                $this->setSimple('fk_default_warehouse', $this->detectDefaultLocation($fieldData));

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
    protected function setMultiStockFields($fieldName, $fieldData)
    {
        global $langs, $user;

        //====================================================================//
        // MULTI-LOCATIONS STOCKS
        //====================================================================//

        if (!ConfigManager::isMultiStocksMode()) {
            return;
        }

        foreach ($this->getStockLocationsIds() as $locationId) {
            //====================================================================//
            // Detect is Requested Location
            if ($fieldName != "stock_level_".$locationId) {
                continue;
            }
            unset($this->in[$fieldName]);
            //====================================================================//
            // Load Locations Stocks
            if (empty($this->object->stock_warehouse)) {
                $this->object->load_stock();
            }
            //====================================================================//
            // Read Real Stock for Location
            $currentStock = isset($this->object->stock_warehouse[$locationId]->real)
                ? $this->object->stock_warehouse[$locationId]->real
                : 0;
            //====================================================================//
            // Compare Current Product Stock with new Value
            if ($currentStock == $fieldData) {
                return;
            }
            //====================================================================//
            // Update Product Stock
            $delta = $currentStock - $fieldData;
            //====================================================================//
            // Update Product Stock
            $result = $this->object->correct_stock(
                $user,                                      // Current User Object
                $locationId,                                // Impacted Stock Id
                abs($delta),                                // Quantity to Move
                ($delta > 0)?1:0,                           // Direction 0 = add, 1 = remove
                $langs->trans("Updated by Splash Module"),  // Operation Comment
                $this->getStockPriceForPmp()                // Price Used for Pmp Calculation
            );
            //====================================================================//
            // Check potential Errors
            if ($result < 0) {
                $this->catchDolibarrErrors();
            }
        }
    }

    /**
     * Write Id of Product Default Stock Location
     *
     * @param mixed $fieldData Field Data
     *
     * @return null|int Strock Location Id
     */
    protected function detectDefaultLocation($fieldData)
    {
        $locationId = null;
        //====================================================================//
        // Detect Location Id from Given Ref
        if (!empty($fieldData) && is_scalar($fieldData)) {
            //====================================================================//
            // Load Available Locations
            $locations = $this->getStockLocationsIds();
            if (isset($locations[$fieldData])) {
                $locationId = $locations[$fieldData];
            }
        }
        //====================================================================//
        // Detect Location Id from Default Configuration
        $defaultLocation = ConfigManager::getProductsDefaultWarehouse();
        if (is_null($locationId) && !empty($defaultLocation)) {
            if (in_array($defaultLocation, $this->getStockLocationsIds(), true)) {
                $locationId = (int) $defaultLocation;
            }
        }

        return $locationId;
    }

    /**
     * Create Stock Transaction to Update Products Stocks Level
     *
     * @param int $newStock New Product Stock
     *
     * @return bool
     */
    private function setProductStock($newStock)
    {
        global $langs, $user;

        //====================================================================//
        // Compare Current Product Stock with new Value
        if ($this->object->stock_reel == $newStock) {
            return true;
        }
        //====================================================================//
        // Verify Multi-Stock feature is Disabled
        if (ConfigManager::isMultiStocksMode()) {
            return Splash::log()->war("Multi-Stocks Feature is Enabled... Stock update Skipped.");
        }
        //====================================================================//
        // Update Product Stock
        $delta = $this->object->stock_reel - $newStock;
        //====================================================================//
        // Identify Product Stock to Impact
        $locationId = $this->getStockLocationId();
        if (empty($locationId)) {
            return false;
        }
        //====================================================================//
        // Update Product Stock
        $result = $this->object->correct_stock(
            $user,                                      // Current User Object
            $locationId,                                // Impacted Stock Id
            abs($delta),                                // Quantity to Move
            ($delta > 0)?1:0,                           // Direction 0 = add, 1 = remove
            $langs->trans("Updated by Splash Module"),  // Operation Comment
            $this->getStockPriceForPmp()                // Price Used for Pmp Calculation
        );
        //====================================================================//
        // Check potential Errors
        if ($result < 0) {
            $this->catchDolibarrErrors();

            return false;
        }

        return true;
    }

    /**
     * Read Name of Product Default Stock Location
     *
     * @return string
     */
    private function getDefaultLocation()
    {
        //====================================================================//
        // Check If Field Exists
        if (!isset($this->object->fk_default_warehouse)) {
            return "";
        }
        //====================================================================//
        // Check If Field is Empty
        if (!is_scalar($this->object->fk_default_warehouse)) {
            return "";
        }
        //====================================================================//
        // Read Location Name from Database
        return (string) $this->object->getValueFrom(
            "entrepot",
            (int) $this->object->fk_default_warehouse,
            "ref"
        );
    }

    /**
     * Read Id of Product Stock Location to Impact
     *
     * @return int
     */
    private function getStockLocationId()
    {
        //====================================================================//
        // Check If Location Field Exists (Dolibarr > 7)
        if (isset($this->object->fk_default_warehouse)) {
            //====================================================================//
            // Check If Location Id is Valid
            $dfLocation = $this->object->fk_default_warehouse;
            if (is_scalar($dfLocation) && ($dfLocation > 0)) {
                return (int) $dfLocation;
            }
        }

        return (int) ConfigManager::getSplashWarehouse();
    }

    /**
     * Build List of Stock Location
     *
     * @return array
     */
    private function getStockLocations()
    {
        global $db;

        $locations = array();
        //====================================================================//
        // Prepare SQL Query
        $sql = "SELECT e.rowid, e.ref, e.lieu, e.description";
        $sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e";
        $sql .= " WHERE e.entity IN (".getEntity('stock').")";
        $sql .= " AND e.statut = 1";
        //====================================================================//
        // Execute Query
        dol_syslog(get_class($this).'::splashLoadWarehouses', LOG_DEBUG);
        $resql = $db->query($sql);
        if (!$resql) {
            return $locations;
        }
        //====================================================================//
        // Parse Results
        $index = 0;
        while ($index < $db->num_rows($resql)) {
            $obj = $db->fetch_object($resql);
            $locations[$obj->ref] = $obj->ref.": ".$obj->lieu;
            $index++;
        }

        return $locations;
    }

    /**
     * Build List of Stock Location (RowId Indexed)
     *
     * @return array
     */
    private function getStockLocationsIds()
    {
        global $db;

        //====================================================================//
        // Load to Cache
        if (!isset(static::$locationIds)) {
            static::$locationIds = array();
            //====================================================================//
            // Prepare SQL Query
            $sql = "SELECT e.rowid, e.ref, e.lieu, e.description";
            $sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e";
            $sql .= " WHERE e.entity IN (".getEntity('stock').")";
            $sql .= " AND e.statut = 1";
            //====================================================================//
            // Execute Query
            dol_syslog(get_class($this).'::splashLoadWarehouses', LOG_DEBUG);
            $resql = $db->query($sql);
            if (!$resql) {
                return static::$locationIds;
            }
            //====================================================================//
            // Parse Results
            $index = 0;
            while ($index < $db->num_rows($resql)) {
                $obj = $db->fetch_object($resql);
                static::$locationIds[$obj->ref] = $obj->rowid;
                $index++;
            }
        }

        return static::$locationIds;
    }

    /**
     * Get product Price Used for Pmp Calculation
     *
     * @return float|int
     */
    private function getStockPriceForPmp()
    {
        //====================================================================//
        // USE Product Cost price for Pmp Calculation
        if (isset(Splash::configuration()->DolUseCostPriceForPmp) && Splash::configuration()->DolUseCostPriceForPmp) {
            return (double) $this->object->cost_price;
        }

        return 0;
    }
}
