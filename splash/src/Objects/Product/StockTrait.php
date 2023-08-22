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
     * @var null|array<string, int>
     */
    private static ?array $locationIds;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildStockFields(): void
    {
        global $langs;

        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//

        //====================================================================//
        // Default Stock Location
        if (Local::dolVersionCmp("8.0.0") >= 0) {
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("fk_default_warehouse")
                ->addChoices($this->getStockLocations())
                ->group($langs->trans("Stock"))
                ->name($langs->trans("DefaultWarehouse"))
                ->microData("http://schema.org/Offer", "inventoryLocation")
            ;
        }
        //====================================================================//
        // Reel Stock
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("stock_reel")
            ->name($langs->trans("RealStock"))
            ->group($langs->trans("Stock"))
            ->microData("http://schema.org/Offer", "inventoryLevel")
            ->isReadOnly(ConfigManager::isMultiStocksMode())
            ->isListed()
        ;
        //====================================================================//
        // Virtual Stock
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("stock_theorique")
            ->name($langs->trans("VirtualStock"))
            ->description($langs->trans("VirtualStockDesc"))
            ->group($langs->trans("Stock"))
            ->MicroData("http://schema.org/Offer", "availableLevel")
            ->isReadOnly()
        ;
        //====================================================================//
        // Stock Alerte Level
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("seuil_stock_alerte")
            ->name($langs->trans("StockLimit"))
            ->group($langs->trans("Stock"))
            ->microData("http://schema.org/Offer", "inventoryAlertLevel")
        ;
        //====================================================================//
        // Stock Alerte Flag
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("stock_alert_flag")
            ->name($langs->trans("StockTooLow"))
            ->group($langs->trans("Stock"))
            ->microData("http://schema.org/Offer", "inventoryAlertFlag")
            ->isReadOnly()
        ;
        //====================================================================//
        // Stock Expected Level
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("desiredstock")
            ->name($langs->trans("DesiredStock"))
            ->group($langs->trans("Stock"))
            ->microData("http://schema.org/Offer", "inventoryTargetLevel")
        ;
        //====================================================================//
        // Average Purchase price value
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("pmp")
            ->name($langs->trans("EstimatedStockValueShort"))
            ->group($langs->trans("Stock"))
            ->microData("http://schema.org/Offer", "averagePrice")
            ->isReadOnly()
        ;
    }

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMultiStockFields(): void
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
    protected function getStockFields(string $key, string $fieldName): void
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
                // Virtual Stock Reading
            case 'stock_theorique':
                $this->object->load_virtual_stock();
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
    protected function getMultiStockFields(string $key, string $fieldName): void
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
            $this->out[$fieldName] = $this->object->stock_warehouse[$locationId]->real ?? 0;
            unset($this->in[$key]);
        }
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setStockFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
            case 'stock_reel':
                $this->setProductStock((int) $fieldData);

                break;
                //====================================================================//
                // Direct Writings
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
    protected function setMultiStockFields(string $fieldName, $fieldData): void
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
            $currentStock = $this->object->stock_warehouse[$locationId]->real ?? 0;
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
     * Write ID of Product Default Stock Location
     *
     * @param mixed $fieldData Field Data
     *
     * @return null|int Stock Location ID
     */
    protected function detectDefaultLocation($fieldData): ?int
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
                $locationId = $defaultLocation;
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
    private function setProductStock(int $newStock): bool
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
            $locationId,                                // Impacted Stock ID
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
    private function getDefaultLocation(): string
    {
        //====================================================================//
        // Check If Field Exists and is NOT Empty
        if (empty($this->object->fk_default_warehouse)) {
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
     * Read ID of Product Stock Location to Impact
     *
     * @return int
     */
    private function getStockLocationId(): int
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
     * @return array<string, string>
     */
    private function getStockLocations(): array
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
        $resSql = $db->query($sql);
        if (!$resSql) {
            return $locations;
        }
        //====================================================================//
        // Parse Results
        $index = 0;
        while ($index < $db->num_rows($resSql)) {
            $obj = $db->fetch_object($resSql);
            $locations[(string) $obj->ref] = $obj->ref.": ".$obj->lieu;
            $index++;
        }

        return $locations;
    }

    /**
     * Build List of Stock Location (RowId Indexed)
     *
     * @return array<string, int>
     */
    private function getStockLocationsIds(): array
    {
        global $db;

        //====================================================================//
        // Load to Cache
        if (!isset(self::$locationIds)) {
            self::$locationIds = array();
            //====================================================================//
            // Prepare SQL Query
            $sql = "SELECT e.rowid, e.ref, e.lieu, e.description";
            $sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e";
            $sql .= " WHERE e.entity IN (".getEntity('stock').")";
            $sql .= " AND e.statut = 1";
            //====================================================================//
            // Execute Query
            $resSql = $db->query($sql);
            if (!$resSql) {
                return self::$locationIds;
            }
            //====================================================================//
            // Parse Results
            $index = 0;
            while ($index < $db->num_rows($resSql)) {
                $obj = $db->fetch_object($resSql);
                self::$locationIds[(string) $obj->ref] = (int) $obj->rowid;
                $index++;
            }
        }

        return self::$locationIds;
    }

    /**
     * Get product Price Used for Pmp Calculation
     *
     * @return float
     */
    private function getStockPriceForPmp(): float
    {
        //====================================================================//
        // USE Product Cost price for Pmp Calculation
        if (isset(Splash::configuration()->DolUseCostPriceForPmp) && Splash::configuration()->DolUseCostPriceForPmp) {
            return (double) $this->object->cost_price;
        }

        return 0.0;
    }
}
