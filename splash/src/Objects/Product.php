<?php
/*
 * Copyright (C) 2011-2014  Bernard Paquier       <bernard.paquier@gmail.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * 
 *  \Id 	$Id: osws-local-Customers.class.php 92 2014-09-16 22:18:01Z Nanard33 $
 *  \version    $Revision: 92 $
 *  \date       $LastChangedDate: 2014-09-17 00:18:01 +0200 (mer. 17 sept. 2014) $ 
 *  \ingroup    OSWS - Open Synchronisation WebService
 *  \brief      Local Function Definition for Management of Customers Data
 *  \class      OsWs_Local_Customers
 *  \remarks	Designed for Splash Module - Dolibar ERP Version
*/
                    
//====================================================================//
// *******************************************************************//
//                     SPLASH FOR DOLIBARR                            //
// *******************************************************************//
//               PRODUCT / SERVICE DATA MANAGEMENT                    //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Objects;

use Splash\Models\ObjectBase;
use Splash\Core\SplashCore      as Splash;

use Splash\Models\Objects\PricesTrait;

/**
 *	\class      Product
 *	\brief      Product - Dolibarr Products Management Class
 */
class Product extends ObjectBase
{
    
    use PricesTrait;
    
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment thius line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static    $NAME            =  "Product";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Dolibarr Product Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-product-hunt";
    
    //====================================================================//
    // Class Constructor
    //====================================================================//
        
    /**
     *      @abstract       Class Constructor (Used only if localy necessary)
     *      @return         int                     0 if KO, >0 if OK
     */
    function __construct()
    {
        //====================================================================//
        // Place Here Any SPECIFIC Initialisation Code
        //====================================================================//
        
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
        
        //====================================================================//
        // Load Dolibarr Default Language
        Splash::Local()->LoadDefaultLanguage();
        
        return True;
    }    
    
    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    /**
    *   @abstract     Return List Of available data for Customer
    *   @return       array   $data             List of all customers available data
    *                                           All data must match with OSWS Data Types
    *                                           Use OsWs_Data::Define to create data instances
    */
    public function Fields()
    {
        global $langs;
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             

        //====================================================================//
        // Load Required Dolibarr Translation Files
        $langs->load("main");
        $langs->load("other");
        $langs->load("products");
        $langs->load("stocks");
        
        //====================================================================//
        //  Load Local Translation File
        Splash::Translator()->Load("objects@local");         

        //====================================================================//
        // CORE INFORMATIONS
        //====================================================================//
        $this->buildCoreFields();

        //====================================================================//
        // PRODUCT DESCRIPTIONS
        //====================================================================//
        $this->buildDescFields();
        
        //====================================================================//
        // MAIN INFORMATIONS
        //====================================================================//
        $this->buildMainFields();

        //====================================================================//
        // STOCK INFORMATIONS
        //====================================================================//
        $this->buildStockFields();
        
        //====================================================================//
        // META INFORMATIONS
        //====================================================================//
        $this->buildMetaFields();
          
        //====================================================================//
        // Publish Fields
        return $this->FieldsFactory()->Publish();
    }
    
    /**
    *   @abstract     Return List Of Customer with required filters
     * 
    *   @param        string  $filter                   Filters/Search String for Contact List. 
    *   @param        array   $params                   Search parameters for result List. 
    *                         $params["max"]            Maximum Number of results 
    *                         $params["offset"]         List Start Offset 
    *                         $params["sortfield"]      Field name for sort list (Available fields listed below)    
    *                         $params["sortorder"]      List Order Constraign (Default = ASC)    
     * 
    *   @return       array   $data                     List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter=NULL,$params=NULL)
    {
        global $db,$conf,$langs;
        Splash::Log()->Deb("MsgLocalFuncTrace",__CLASS__,__FUNCTION__);             
        $data = array();
        //====================================================================//
        // Load Default Language
        Splash::Local()->LoadDefaultLanguage();
        //====================================================================//
        // Load Required Translation Files
        $langs->load("products");
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql    = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql   .= " p.rowid as id,";                    // Object Id         
        $sql   .= " p.ref as ref,";                     // Reference
        $sql   .= " p.label as label,";                 // Product Name 
        $sql   .= " p.description as description,";     // Short Description 
        $sql   .= " p.stock as stock_reel,";            // Stock Level
        $sql   .= " p.price as price,";                 // Price
        $sql   .= " p.tobuy as status_buy,";            // Product may be Ordered / Bought
        $sql   .= " p.tosell as status,";               // Product may be Sold
        $sql   .= " p.tms as modified";                 // last modified date
        //====================================================================//
        // Select Database tables
        $sql   .= " FROM " . MAIN_DB_PREFIX . "product as p ";
        
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in Product Ref.
            $sql   .= " WHERE LOWER( p.ref ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Product Label
            $sql   .= " OR LOWER( p.label ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Product Description
            $sql   .= " OR LOWER( p.description ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Product Stock
            $sql   .= " OR LOWER( p.stock ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Product Price
            $sql   .= " OR LOWER( p.price ) LIKE LOWER( '%" . $filter ."%') ";
        }  
        
        //====================================================================//
        // Setup sortorder
        //====================================================================//
        $sortfield = empty($params["sortfield"])?"p.rowid":$params["sortfield"];
        $sortorder = empty($params["sortorder"])?"DESC":$params["sortorder"];
        $sql   .= " ORDER BY " . $sortfield . " " . $sortorder;   
        
        //====================================================================//
        // Execute request to get total number of row
        $resqlcount = $db->query($sql);
        if ($resqlcount)    {
            $data["meta"]["total"]   =   $db->num_rows($resqlcount);  // Store Total Number of results
        }
        //====================================================================//
        // Setup limmits
        if ( !empty($params["max"])  ) {
            $sql   .= " LIMIT " . $params["max"];
        }
        if ( !empty($params["offset"])  ) {
            $sql   .= " OFFSET " . $params["offset"];
        }
        //====================================================================//
        // Execute final request
        $resql = $db->query($sql);
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," SQL : " . $sql);
        if (empty($resql))  {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__, $db->lasterror());
        }
        //====================================================================//
        // Read Data and prepare Response Array
        $num = $db->num_rows($resql);           // Read number of results
        $data["meta"]["current"]   =   $num;    // Store Current Number of results
        $i = 0;
        //====================================================================//
        // For each result, read information and add to $data
        while ($i < $num)
        {
            $data[$i] = (array) $db->fetch_object($resql);
            $data[$i]["price"] = round($data[$i]["price"],3) . " " . $conf->global->MAIN_MONNAIE;
            $i++;
        }
        $db->free($resql);
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__, " " . $i . " Products Found.");
        return $data;
    }
    
    /**
    *   @abstract     Return requested Customer Data
    *   @param        array   $id               Customers Id.  
    *   @param        array   $list             List of requested fields    
    */
    public function Get($id=NULL,$list=0)
    {
        global $db,$langs;
        
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Load Default Language
        Splash::Local()->LoadDefaultLanguage();
        //====================================================================//
        // Init Reading
        $this->In = $list;
        //====================================================================//
        // Init Object 
        $this->Object = new \Product($db);
        if ( $this->Object->fetch($id) != 1 )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Product (" . $id . ").");
        }
        //====================================================================//
        // Init Response Array 
        $this->Out  =   array( "id" => $id );
        
        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields            
            $this->getCoreFields($Key,$FieldName);
            $this->getDescFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getStockFields($Key, $FieldName);
            $this->getMetaFields($Key, $FieldName);
        }        
        
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach ($this->In as $FieldName) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        
        //====================================================================//
        // Return Data
        //====================================================================//
//        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
        return $this->Out; 
    }
        
    /**
    *   @abstract     Write or Create requested Customer Data
    *   @param        array   $id               Customers Id.  If NULL, Customer needs t be created.
    *   @param        array   $list             List of requested fields    
    *   @return       string  $id               Customers Id.  If NULL, Customer wasn't created.    
    */
    public function Set($id=NULL,$list=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);
        
        //====================================================================//
        // Init Reading
        $this->In           =   $list;
        $this->update       =   False;
        
        //====================================================================//
        // Init Object
        if ( !$this->setInitObject($id) ) {
            return False;
        }        

        //====================================================================//
        // Run Throw All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
            $this->setDescFields($FieldName,$Data);
            $this->setMainFields($FieldName,$Data);
            $this->setStockFields($FieldName,$Data);
            $this->setMetaFields($FieldName,$Data);
        }
        //====================================================================//
        // Create/Update Object if Requiered
        if ( $this->setSaveObject() == False ) {
            return False;
        }            
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach ($this->In as $FieldName => $Data) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        //====================================================================//
        // Return Object Id        
        return (int) $this->Object->id;        
    }       

    /**
    *   @abstract   Delete requested Object
    *   @param      int         $id             Object Id.  If NULL, Object needs to be created.
    *   @return     int                         0 if KO, >0 if OK 
    */    
    public function Delete($id=NULL)
    {
        global $db,$user,$langs;
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        
        //====================================================================//
        // Create Object 
        $Object = new \Product($db);
        
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }
        
        //====================================================================//
        // Set Object Id, fetch not needed
        $Object->id = $id;
        
        //====================================================================//
        // Delete Object
        $Arg1 = ( Splash::Local()->DolVersionCmp("6.0.0") > 0 ) ? $user : 0;
        if ( $Object->delete($Arg1)) {  
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($Object->error));
        }
        
                
        return True;
    }       

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()   {
        global $langs;

        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref")
                ->Name($langs->trans("ProductRef"))
                ->IsListed()
                ->MicroData("http://schema.org/Product","model")
                ->isLogged()
                ->isRequired();
        
    }    

    /**
    *   @abstract     Build Description Fields using FieldFactory
    */
    private function buildDescFields()   {
        global $conf,$langs;
        
        $GroupName  =   $langs->trans("Description");
        
        //====================================================================//
        // PRODUCT DESCRIPTIONS
        //====================================================================//

        //====================================================================//
        // Native Multilangs Descriptions
        if ($conf->global->MAIN_MULTILANGS) {
            //====================================================================//
            // Name
            $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                    ->Identifier("label")
                    ->Name($langs->trans("ProductLabel"))
                    ->IsListed()
                    ->isLogged()
                    ->Group($GroupName)
                    ->MicroData("http://schema.org/Product","name")
                    ->isRequired();
            //====================================================================//
            // Description
            $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                    ->Identifier("description")
                    ->Name($langs->trans("Description"))
                    ->IsListed()
                    ->isLogged()
                    ->Group($GroupName)
                    ->MicroData("http://schema.org/Product","description");
            
            //====================================================================//
            // Note
//            if ( Splash::Local()->DolVersionCmp("5.0.0") > 0 ) {
                $this->FieldsFactory()->Create(SPL_T_TEXT)
                    ->Identifier("note")
                    ->Name($langs->trans("Note"))
                    ->Group($GroupName)
                    ->MicroData("http://schema.org/Product","privatenote");
//            }
        //====================================================================//
        // No Multilangs Descriptions
        } else {
            //====================================================================//
            // Name
            $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                    ->Identifier("label")
                    ->Name($langs->trans("ProductLabel"))
                    ->IsListed()
                    ->isLogged()
                    ->Group($GroupName)
                    ->MicroData("http://schema.org/Product","name")
                    ->isRequired();
            //====================================================================//
            // Description
            $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                    ->Identifier("description")
                    ->Name($langs->trans("Description"))
                    ->IsListed()
                    ->isLogged()
                    ->Group($GroupName)
                    ->MicroData("http://schema.org/Product","description");
            //====================================================================//
            // Note
            $this->FieldsFactory()->Create(SPL_T_TEXT)
                    ->Identifier("note")
                    ->Name($langs->trans("Note"))
                    ->Group($GroupName)
                    ->MicroData("http://schema.org/Product","privatenote");
        }        
        
    }    

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
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
        
        if ( Splash::Local()->DolVersionCmp("3.9.0") >= 0) {
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
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildStockFields() {
        global $langs;
        
        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//
        
        //====================================================================//
        // Stock Reel
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("stock_reel")
                ->Name($langs->trans("RealStock"))
                ->MicroData("http://schema.org/Offer","inventoryLevel")
                ->isListed();

        //====================================================================//
        // Stock Alerte Level
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("seuil_stock_alerte")
                ->Name($langs->trans("StockLimit"))
                ->MicroData("http://schema.org/Offer","inventoryAlertLevel");
                
        //====================================================================//
        // Stock Alerte Flag
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("stock_alert_flag")
                ->Name($langs->trans("StockTooLow"))
                ->MicroData("http://schema.org/Offer","inventoryAlertFlag")
                ->ReadOnly();
        
        //====================================================================//
        // Stock Expected Level
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("desiredstock")
                ->Name($langs->trans("DesiredStock"))
                ->MicroData("http://schema.org/Offer","inventoryTargetLevel");

        //====================================================================//
        // Average Purchase price value
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("pmp")
                ->Name($langs->trans("EstimatedStockValueShort"))
                ->MicroData("http://schema.org/Offer","averagePrice")
                ->ReadOnly();
        
        return;
    }
    
    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaFields() {
        global $langs;
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // On Sell
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("status_buy")
                ->Name($langs->trans("Status").' ('.$langs->trans("Buy").')')
                ->MicroData("http://schema.org/Product","ordered")
                ->Group("Meta")
               ->isListed();        
        
        //====================================================================//
        // On Buy
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("status")
                ->Name($langs->trans("Status").' ('.$langs->trans("Sell").')')
                ->MicroData("http://schema.org/Product","offered")
                ->Group("Meta")
                ->isListed();        
        
        
        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//        
        
        //====================================================================//
        // TMS - Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date_modification")
                ->Name($langs->trans("DateLastModification"))
                ->MicroData("http://schema.org/DataFeedItem","dateModified")
                ->Group("Meta")
                ->ReadOnly();
        
        //====================================================================//
        // datec - Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date_creation")
                ->Name($langs->trans("DateCreation"))
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->Group("Meta")
                ->ReadOnly();             
        
    }   

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getCoreFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'ref':
                $this->getSingleField($FieldName);
                break;
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getDescFields($Key,$FieldName)
    {
        global $conf;
        
        //====================================================================//
        // PRODUCT MULTILANGUAGES CONTENTS
        //====================================================================//

        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            case 'label':
            case 'description':
                
                //====================================================================//        
                // Update Product Description
                if ($conf->global->MAIN_MULTILANGS) {        
                    $this->Out[$FieldName] = Splash::Local()->getMultilang($this->Object,$FieldName);
                } else {
                    $this->getSingleField($FieldName);
                }                
                break;            
            
            case 'note':
                
                $this->getSingleField($FieldName);
                
                break;            

            default:
                return;
        }
        
        unset($this->In[$Key]);
    }    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMainFields($Key,$FieldName)
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
                $this->Out[$FieldName] = (float) Splash::Local()->C_Weight($this->Object->weight,$this->Object->weight_units);             
                break;
            case 'length':
                $this->Out[$FieldName] = (float) Splash::Local()->C_Length($this->Object->length,$this->Object->length_units);             
                break;
            case 'surface':
                $this->Out[$FieldName] = (float) Splash::Local()->C_Surface($this->Object->surface,$this->Object->surface_units);             
                break;
            case 'volume':
                $this->Out[$FieldName] = (float) Splash::Local()->C_Volume($this->Object->volume,$this->Object->volume_units);             
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
                    $PriceHT    = (double) $this->Object->getValueFrom($this->Object->table_element, $this->Object->id, "cost_price");
                    $this->Out[$FieldName] = self::Prices()
                            ->Encode( $PriceHT, (double)$this->Object->tva_tx, Null, $conf->global->MAIN_MONNAIE );
                break;

            default:
                return;
        }
        
        unset($this->In[$Key]);
    }

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getStockFields($Key,$FieldName) {

        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // STOCK INFORMATIONS
            //====================================================================//

            //====================================================================//
            // Stock Alerte Flag
            case 'stock_alert_flag':
                if ( $this->Object->stock_reel < $this->Object->seuil_stock_alerte )    {
                    $this->Object->$FieldName = True;             
                } else {
                    $this->Object->$FieldName = False;             
                }
            
            //====================================================================//
            // Stock Direct Reading
            case 'stock_reel':
//                $this->Out[$FieldName] = (int) $this->Object->$FieldName;
//                break;                
            case 'seuil_stock_alerte':
            case 'desiredstock':
            case 'pmp':
                $this->getSingleField($FieldName, "Object", 0);
                break;                
            
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }    

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMetaFields($Key,$FieldName) {

        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // OTHERS INFORMATIONS
            //====================================================================//
            case 'status':
            case 'status_buy':
                $this->Out[$FieldName] = (bool) $this->Object->$FieldName;
                break;                
            
            //====================================================================//
            // TRACEABILITY INFORMATIONS
            //====================================================================//
            case 'date_creation':
            case 'date_modification':
                $this->Out[$FieldName] = dol_print_date($this->Object->$FieldName,'dayrfc');
                break;                

            default:
                return;
        }
        
        unset($this->In[$Key]);
    }    
    
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//
    
    /**
     *  @abstract     Init Object before Writting Fields
     * 
     *  @param        array   $id               Object Id. If NULL, Object needs t be created.
     * 
     */
    private function setInitObject($id) 
    {
        global $db;
        
        //====================================================================//
        // Init Object 
        $this->Object = new \Product($db);
        
        //====================================================================//
        // If $id Given => Load Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            if ( $this->Object->fetch($id) != 1 ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Product (" . $id . ").");
            }
        }
        //====================================================================//
        // If NO $id Given  => Verify Input Data includes minimal valid values
        //                  => Setup Standard Parameters For New Customers
        //====================================================================//
        else
        {
            //====================================================================//
            // Check Product Ref is given
            if ( empty($this->In["ref"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"ref");
            }
            //====================================================================//
            // Check Product Label is given
            if ( empty($this->In["label"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"label");
            }
            //====================================================================//
            // Pre-Setup of Dolibarr infos
            
            //====================================================================//
            // Required For Dolibarr Below 3.6
            //! Type 0 for regular product, 1 for service (Advanced feature: 2 for assembly kit, 3 for stock kit)
            $this->Object->type        = 0;				   
            
        }        
        
        return True;
    }
        
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setCoreFields($FieldName,$Data) 
    {

        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
            case 'ref':
                $this->setSingleField($FieldName, $Data);
                break;       
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setDescFields($FieldName,$Data) 
    {
        global $conf;
        
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            case 'label':
            case 'description':
                //====================================================================//        
                // Update Product Description
                if ($conf->global->MAIN_MULTILANGS) {        
                    $this->update |= Splash::Local()->setMultilang($this->Object, $FieldName, $Data);
                } else {
                    $this->setSingleField($FieldName, $Data);
                }
                
                //====================================================================//        
                // Duplicate Lable to Deprecated libelle variable
                if ( $FieldName === "label") {
                    $this->Object->libelle = $this->Object->label;
                }
                break;                    
                
            case 'note':
                $this->setSingleField($FieldName, $Data);
                break;                    
            
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMainFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ( (string)$Data !== (string) Splash::Local()->C_Weight($this->Object->weight,$this->Object->weight_units)) {   
                    $nomalized                      =   Splash::Local()->N_Weight($Data);
                    $this->Object->weight           =   $nomalized->weight;
                    $this->Object->weight_units     =   $nomalized->weight_units;
                    $this->update = True;
                }
                break;
            case 'length':
                if ( (string)$Data !== (string) Splash::Local()->C_Length($this->Object->length,$this->Object->length_units)) {             
                    $nomalized                      =   Splash::Local()->N_Length($Data);
                    $this->Object->length           =   $nomalized->length;
                    $this->Object->length_units     =   $nomalized->length_units;
                    $this->update = True;
                }
                break;
            case 'surface':
                if ( (string)$Data !== (string) Splash::Local()->C_Surface($this->Object->surface,$this->Object->surface_units)) {             
                    $nomalized                      =   Splash::Local()->N_Surface($Data);
                    $this->Object->surface          =   $nomalized->surface;
                    $this->Object->surface_units    =   $nomalized->surface_units;
                    $this->update = True;
                }
                break;
            case 'volume':
               if ( (string)$Data !== (string) Splash::Local()->C_Volume($this->Object->volume,$this->Object->volume_units)) {             
                    $nomalized                      =   Splash::Local()->N_Volume($Data);
                    $this->Object->volume           =   $nomalized->volume;
                    $this->Object->volume_units     =   $nomalized->volume_units;
                    $this->update = True;
                }
                break;             
            
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getMainFields(0,"price");
                //====================================================================//
                // Compare Prices
                if ( !$this->Price_Compare($this->Out["price"],$Data) ) {
                    $this->NewPrice = $Data;
                    $this->update   = True;
                }
                break;  
            case 'cost_price':
                    //====================================================================//
                    // Read Current Product Cost Price
                    $CostPrice  =  $this->Object->getValueFrom($this->Object->table_element, $this->Object->id, "cost_price");
                    //====================================================================//
                    // Compare Prices
                    if ( !$this->Float_Compare($CostPrice,$Data["ht"]) ) {
                        $this->NewCostPrice = $Data["ht"];
                    }  
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
    private function setSavePrice()
    {
        global $user, $conf;
        
        //====================================================================//
        // Verify Price Need to be Updated
        if ( empty($this->NewPrice) ) {
            return True;
        }
        
        //====================================================================//
        // Perform Prices Update
        //====================================================================//

        //====================================================================//
        // Update Based on TTC Price
        if ($this->NewPrice["base"]) {
            $Price      = $this->NewPrice["ttc"];
            $PriceBase  = "TTC";
        //====================================================================//
        // Update Based on HT Price
        } else {
            $Price      = $this->NewPrice["ht"];
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
            return (bool) $this->Object
                    ->updatePrice($Price,$PriceBase, $user, $this->NewPrice["vat"], '', $PriceLevel);
        //====================================================================//
        // For compatibility with previous versions => Below V3.5.0
        } else {    
            return (bool) $this->Object
                    ->updatePrice($this->Object->id, $Price,$PriceBase, $user, $this->NewPrice["vat"], '', $PriceLevel);
        }
        return False;
    }
    
    /**
     *  @abstract     Write New Price
     * 
     *  @return         bool
     */
    private function setSaveCostPrice()
    {    
        //====================================================================//
        // Update Cost Prices
        if ( !isset($this->NewCostPrice) ) {
            return True;
        }
                    
        //====================================================================//
        // Update Cost Prices
        if ( $this->Object->setValueFrom("cost_price",$this->NewCostPrice) < 0 ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$this->Object->error);              
        }
        return True;
    }
            
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setStockFields($FieldName,$Data) 
    {

        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//

            //====================================================================//
            // Direct Writtings
            case 'stock_reel':
                //====================================================================//
                // Product Stock Update is done After Product Object Update
                //====================================================================//
                if ( !$this->Object->id ) {
                    $this->NewStock = $Data;
                    $this->update = True;
                    break;       
                }
                //====================================================================//
                // Load Current Product Stock Details
                $this->Object->load_stock();                
                //====================================================================//
                // Compare Current Product Stock with new Value
                if ( $this->Object->$FieldName != $Data ) {
                    $this->NewStock = $Data;
                    $this->update = True;
                }  
                break;       
                
            //====================================================================//
            // Direct Writtings
            case 'seuil_stock_alerte':
            case 'desiredstock':
            case 'pmp':                
                if ( $this->Object->$FieldName != $Data ) {
                    $this->Object->$FieldName = $Data;
                    $this->update = True;
                }  
                break;       
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract   Create Stock Transaction to Update Products Stocks Level
     * 
     *  @return     bool
     */    
    private function setSaveStock()   
    {
        global $conf,$langs,$user;

        //====================================================================//
        // ReLoad Current Product Stock Details
        $this->Object->load_stock();                
        
        //====================================================================//
        // Update Product Stock
        $delta  =   $this->Object->stock_reel - $this->NewStock;

        //====================================================================//
        // Verify Product Stock need to be Updated
        if ( $delta == 0 ) {
            return True;
        }
        //====================================================================//
        // Verify Default Product Stock is defined
        if ( !empty ($conf->global->SPLASH_STOCK) ) {
            //====================================================================//
            // Update Product Stock
            return $this->Object->correct_stock(
                    $user,                                      // Current User Object
                    $conf->global->SPLASH_STOCK,                // Impacted Stock Id
                    abs($delta),                                // Quantity to Move
                    ($delta > 0)?1:0,                           // Direnction 0 = add, 1 = remove    
                    $langs->trans("Updated by Splash Module"),  // Operation Comment 
                    $this->Object->price                        // Product Price for PMP
                );                            
        }
        
        //====================================================================//
        // Return No Default Stock Error
        return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Product : No Local WareHouse Defined.");
    }     

    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMetaFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
            case 'status':
            case 'status_buy':
                if ( $this->Object->$FieldName != $Data ) {
                     $this->Object->$FieldName = $Data;
                     $this->update = True;
                 }  
                break; 
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Save Object after Writting Fields
     */
    private function setSaveObject() 
    {
        global $user,$langs,$user,$conf;

        //====================================================================//
        // Verify Update Is requiered
        if ( $this->update == False ) {
            Splash::Log()->War("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return $this->Object->id;
        }
        
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }
        
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        
        if (!empty($this->Object->id)) {
            if ( $this->Object->update($this->Object->id,$user) <= 0) {  
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Product. ");
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
            }
            
            //====================================================================//
            // Update Product Price In Database
            if ( !$this->setSavePrice() ) {  
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Product Price. ");
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
            }
            //====================================================================//
            // Update Product Stock In Database
            if ( !$this->setSaveStock() ) {  
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Product Stock. ");
            }
            //====================================================================//
            // Update Product Cost Price In Database            
            $this->setSaveCostPrice();
            
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Product Updated");
            $this->update = False;
            return $this->Object->id;
        }
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//

        //====================================================================//
        // Create Object In Database
        if ( $this->Object->create($user) <= 0) {    
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new Product. ");
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
        }
        
        //====================================================================//
        // Lock New Object 
        $this->Lock($this->Object->id);
        
        //====================================================================//
        // Store Product Price In Database
        if ( !$this->setSavePrice() ) {  
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to set Product Price. ");
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
        }
        
        //====================================================================//
        // Set Product Stock In Database
        if ( !$this->setSaveStock() ) {  
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to set Product Stock. ");
        }
        
        //====================================================================//
        // Update Product Cost Price In Database            
        $this->setSaveCostPrice();

        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Product Created");
        $this->update = False;
        return $this->Object->id; 
    }    
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

	/**
	 *  @abstract Fetch List of Invoices Payments Amounts
	 *
         *  @param int  $PaiementId     Paiment Object Id
         * 
	 *  @return   array             List Of Paiment Object Amounts
	 */
	function getPaiementAmounts($PaiementId)
	{
            global $db;
            //====================================================================//
            // Init Result Array 
            $Amounts = array();
            //====================================================================//
            // SELECT SQL Request 
            $sql = 'SELECT fk_facture, amount';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture';
            $sql.= ' WHERE fk_paiement = '.$PaiementId;
            $resql = $db->query($sql);
            //====================================================================//
            // SQL Error 
            if (!$resql)
            {
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$db->error());
                return $Amounts;
            }
            //====================================================================//
            // Populate Object
            for ($i=0; $i < $db->num_rows($resql); $i++)
            {
                $obj = $db->fetch_object($resql);
                $Amounts[$obj->fk_facture]    =   $obj->amount;
            }
            $db->free($resql);                    
            return $Amounts;
	}      
    
}



?>
