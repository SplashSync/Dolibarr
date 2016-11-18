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
//                     SPLASH FOR PRESTASHOP                          //
// *******************************************************************//
//                CUSTOMERS ORDERS DATA MANAGEMENT                    //
// *******************************************************************//
//====================================================================//

/**
 *	\class      Order
 *	\brief      Customers Orders Management Class
 */
class SplashOrder extends SplashObject
{
    
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment this line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static    $NAME            =  "Customer Order";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Dolibarr Customers Order Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-shopping-cart ";
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//

    private     $orderlineupdate = False;
    
    //====================================================================//
    // Class Constructor
    //====================================================================//
        
    /**
     *      @abstract       Class Constructor (Used only if localy necessary)
     *      @return         int                     0 if KO, >0 if OK
     */
    function __construct()
    {
        global $user;
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
        //====================================================================//
        // Load Dolibarr Default Language
        Splash::Local()->LoadDefaultLanguage();
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }        
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
        $langs->load("admin");
        $langs->load("companies");
        $langs->load("orders");
        $langs->load("other");
        //====================================================================//
        //  Load Local Translation File
        Splash::Translator()->Load("objects@local");         
        //====================================================================//
        // CORE INFORMATIONS
        //====================================================================//
        $this->buildCoreFields();
        //====================================================================//
        // MAIN INFORMATIONS
        //====================================================================//
        $this->buildMainFields();
        //====================================================================//
        // MAIN ORDER LINE INFORMATIONS
        //====================================================================//
        $this->buildOrderLineFields();
        //====================================================================//
        // META INFORMATIONS
        //====================================================================//
        $this->buildMetaFields();
        //====================================================================//
        // POST UPDATED INFORMATIONS (UPDATED AFTER OBJECT CREATED)
        //====================================================================//
        $this->buildPostCreateFields();
        //====================================================================//
        // Publish Fields
        return $this->FieldsFactory()->Publish();
    }
    
    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        string   $filter               Filter string for Orders List. 
    *   @param        array   $params              Search parameters for result List. 
    *                         $params["max"]       Maximum Number of results 
    *                         $params["offset"]    List Start Offset 
    *                         $params["sortfield"] Field name for sort list (Available fields listed below)    
    *                         $params["sortorder"] List Order Constraign (Default = ASC)    
    *   @return       array   $data             List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter=NULL,$params=NULL)
    {
        global $db,$langs;
        Splash::Log()->Deb("MsgLocalFuncTrace",__CLASS__,__FUNCTION__);             
        $data = array();
        //====================================================================//
        // Load Default Language
        Splash::Local()->LoadDefaultLanguage();
        //====================================================================//
        // Load Required Translation Files
        $langs->load("orders");
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql    = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql .= " o.rowid as id,";                  // Object Id         
        $sql .= " o.ref as ref,";                   // Dolibarr Reference  
        $sql .= " o.ref_ext as ref_ext,";           // External Reference  
        $sql .= " o.ref_int as ref_int,";           // Internal Reference 
        $sql .= " o.ref_client as ref_client,";     // Customer Reference
        $sql .= " o.total_ht as total_ht,";         // Total net of tax
        $sql .= " o.total_ttc as total_ttc,";       // Total with tax
        $sql .= " o.date_commande as date";         // Order date
        //====================================================================//
        // Select Database tables
        $sql   .= " FROM " . MAIN_DB_PREFIX . "commande as o ";
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in Order Ref.
            $sql   .= " WHERE LOWER( o.ref ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Order Internal Ref 
            $sql   .= " OR LOWER( o.ref_int ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Order External Ref
            $sql   .= " OR LOWER( o.ref_ext ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Order Customer Ref
            $sql   .= " OR LOWER( o.ref_client ) LIKE LOWER( '%" . $filter ."%') ";
        }   
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"o.rowid":$params["sortfield"];
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
            $i++;
        }
        $db->free($resql);
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__, " " . $i . " Orders Found.");
        return $data;
    }
    
    /**
    *   @abstract     Return requested Customer Data
    *   @param        array   $id               Customers Id.  
    *   @param        array   $list             List of requested fields    
    */
    public function Get($id=NULL,$list=0)
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Init Reading
        $this->In = $list;
        //====================================================================//
        // Init Object 
        $this->Object = new Commande($db);
        if ( $this->Object->fetch($id) != 1 )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Order (" . $id . ").");
        }
        $this->Object->fetch_lines();
        //====================================================================//
        // Init Response Array 
        $this->Out  =   array( "id" => $id );
        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        foreach ($this->In as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields            
            $this->getCoreFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getOrderLineFields($Key,$FieldName);
            $this->getMetaFields($Key, $FieldName);
            $this->getPostCreateFields($Key, $FieldName);
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
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
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
        //====================================================================//
        // Init Object
        if ( !$this->setInitObject($id) ) {
            return False;
        }        

        //====================================================================//
        // Run Throw All Requested Fields
        //====================================================================//
        foreach ($this->In as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
            $this->setMainFields($FieldName,$Data);
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
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
        //====================================================================//
        // Create Object
        $this->Object = new Commande($db);
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }
        //====================================================================//
        // Set Object Id, fetch not needed
        $this->Object->id = $id;
        //====================================================================//
        // Delete Object
        if ( $this->Object->delete($user) <= 0) {  
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
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
        // Customer Object
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "ThirdParty" , SPL_T_ID))
                ->Identifier("socid")
                ->Name($langs->trans("Company"))
                ->MicroData("http://schema.org/Organization","ID")
                ->isRequired();  
        
        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref")
                ->Name($langs->trans("RefOrder"))
                ->MicroData("http://schema.org/Order","name")       
                ->ReadOnly()
                ->IsListed();

        //====================================================================//
        // Order Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date")
                ->Name($langs->trans("OrderDate"))
                ->MicroData("http://schema.org/Order","orderDate")
                ->isRequired()
                ->IsListed();
        
    }    

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        global $langs,$conf;
        
        //====================================================================//
        // Order Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date_livraison")
                ->Name($langs->trans("DeliveryDate"))
                ->MicroData("http://schema.org/ParcelDelivery","expectedArrivalUntil");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Order Total Price HT
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_ht")
                ->Name($langs->trans("TotalHT") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDue")
                ->ReadOnly();
        
        //====================================================================//
        // Order Total Price TTC
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_ttc")
                ->Name($langs->trans("TotalTTC") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDueTaxIncluded")
                ->ReadOnly();        
        
        //====================================================================//
        // ORDER STATUS FLAGS
        //====================================================================//        

        //====================================================================//
        // Is Draft
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isdraft")
                ->Name($langs->trans("Order") . " : " . $langs->trans("Draft"))
                ->MicroData("http://schema.org/OrderStatus","OrderDraft")
                ->Association( "isdraft","iscanceled","isvalidated","isclosed")
                ->ReadOnly();     

        //====================================================================//
        // Is Canceled
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("iscanceled")
                ->Name($langs->trans("Order") . " : " . $langs->trans("Canceled"))
                ->MicroData("http://schema.org/OrderStatus","OrderCancelled")
                ->Association( "isdraft","iscanceled","isvalidated","isclosed")
                ->ReadOnly();     
        
        //====================================================================//
        // Is Validated
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isvalidated")
                ->Name($langs->trans("Order") . " : " . $langs->trans("Validated"))
                ->MicroData("http://schema.org/OrderStatus","OrderProcessing")
                ->Association( "isdraft","iscanceled","isvalidated","isclosed")
                ->ReadOnly();
        
        //====================================================================//
        // Is Closed
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isclosed")
                ->Name($langs->trans("Order") . " : " . $langs->trans("Closed"))
                ->MicroData("http://schema.org/OrderStatus","OrderDelivered")
                ->Association( "isdraft","iscanceled","isvalidated","isclosed")
                ->ReadOnly();

        //====================================================================//
        // Is Paid
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("facturee")
                ->Name($langs->trans("Order") . " : " . $langs->trans("Paid"))
                ->MicroData("http://schema.org/OrderStatus","OrderPaid")
                ->NotTested();
        
        return;
    }
        
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildOrderLineFields() {
        global $langs;
        
        $ListName = $langs->trans("OrderLine") . " => " ;
        
        //====================================================================//
        // Order Line Label
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
//                ->Identifier("label")
//                ->InList("lines")
//                ->Name( $ListName . $langs->trans("Label"))
//                ->MicroData("http://schema.org/partOfInvoice","name")
//                ->Association("description@lines","qty@lines","price@lines");        
        
        //====================================================================//
        // Order Line Description
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("desc")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Description"))
                ->MicroData("http://schema.org/partOfInvoice","description")        
                ->Association("desc@lines","qty@lines","price@lines");        

        //====================================================================//
        // Order Line Product Identifier
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Product" , SPL_T_ID))        
                ->Identifier("fk_product")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Product"))
                ->MicroData("http://schema.org/Product","productID")
                ->Association("desc@lines","qty@lines","price@lines");        
//                ->NotTested();        

        //====================================================================//
        // Order Line Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)        
                ->Identifier("qty")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Quantity"))
                ->MicroData("http://schema.org/QuantitativeValue","value")        
                ->Association("desc@lines","qty@lines","price@lines");        

        //====================================================================//
        // Order Line Discount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("remise_percent")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Discount"))
                ->MicroData("http://schema.org/Order","discount")
                ->Association("desc@lines","qty@lines","price@lines");        

        //====================================================================//
        // Order Line Unit Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)        
                ->Identifier("price")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Price"))
                ->MicroData("http://schema.org/PriceSpecification","price")        
                ->Association("desc@lines","qty@lines","price@lines");        

    }

    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaFields() {
        global $langs;
        
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

//        //====================================================================//
//        // Active
//        $this->FieldsFactory()->Create(SPL_T_BOOL)
//                ->Identifier("status")
//                ->Name($langs->trans("Active"))
//                ->MicroData("http://schema.org/Organization","active")
//                ->IsListed();        
//        
//        if ( Splash::Local()->DolVersionCmp("3.6.0") >= 0 ) {
//            //====================================================================//
//            // isProspect
//            $this->FieldsFactory()->Create(SPL_T_BOOL)
//                    ->Identifier("prospect")
//                    ->Name($langs->trans("Prospect"))
//                    ->MicroData("http://schema.org/Organization","prospect");        
//        }

        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//        
        
        //====================================================================//
        // TMS - Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_modification")
                ->Name($langs->trans("DateLastModification"))
                ->MicroData("http://schema.org/DataFeedItem","dateModified")
                ->ReadOnly();
        
        //====================================================================//
        // datec - Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_creation")
                ->Name($langs->trans("DateCreation"))
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->ReadOnly();        
        
    }   

    /**
    *   @abstract     Build PostCreation Update Fields using FieldFactory
    */
    private function buildPostCreateFields()   {
        global $langs;
        
        //====================================================================//
        // Customer Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref_client")
                ->Name($langs->trans("RefCustomerOrder"))
                ->IsListed()
                ->MicroData("http://schema.org/Order","orderNumber");
        
        //====================================================================//
        // Internal Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref_int")
                ->Name($langs->trans("InternalRef"))
                ->MicroData("http://schema.org/Order","description");
                
        //====================================================================//
        // External Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref_ext")
                ->Name($langs->trans("RefExt"))
                ->IsListed()
                ->MicroData("http://schema.org/Order","alternateName");
        
        //====================================================================//
        // Order Current Status
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("status")
                ->Name($langs->trans("Status"))
                ->MicroData("http://schema.org/Order","orderStatus")
                ->AddChoice("OrderCanceled",    $langs->trans("StatusOrderCanceled"))
                ->AddChoice("OrderDraft",       $langs->trans("StatusOrderDraftShort"))
                ->AddChoice("OrderInTransit",   $langs->trans("StatusOrderSent"))
                ->AddChoice("OrderProcessing",  $langs->trans("StatusOrderSentShort"))
                ->AddChoice("OrderDelivered",   $langs->trans("StatusOrderProcessed"))
                ->NotTested();

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
            case 'ref_client':
            case 'ref_int':
            case 'ref_ext':
                $this->getSingleField($FieldName);
                break;
            
            //====================================================================//
            // Contact ThirdParty Id 
            case 'socid':
                $this->Out[$FieldName] = self::ObjectId_Encode( "ThirdParty" , $this->Object->$FieldName);
                break;

            //====================================================================//
            // Order Official Date
            case 'date':
                $this->Out[$FieldName] = !empty($this->Object->date)?dol_print_date($this->Object->date, '%Y-%m-%d'):Null;
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
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Order Delivery Date
            case 'date_livraison':
                $this->Out[$FieldName] = !empty($this->Object->date_livraison)?dol_print_date($this->Object->date_livraison, '%Y-%m-%d'):Null;
                break;            
            
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_ht':
            case 'total_ttc':
            case 'total_vat':
                $this->getSingleField($FieldName);
                break;
            
        //====================================================================//
        // ORDER STATUS
        //====================================================================//        
            
        case 'isdraft':
            $this->Out[$FieldName]  = ( $this->Object->statut == 0 )?True:False;
            break;
        case 'iscanceled':
            $this->Out[$FieldName]  = ( $this->Object->statut == -1 )?True:False;
            break;
        case 'isvalidated':
            $this->Out[$FieldName]  = ( $this->Object->statut == 1 )?True:False;
            break;
        case 'isclosed':
            $this->Out[$FieldName]  = ( $this->Object->statut == 3 )?True:False;
            break;            

        //====================================================================//
        // ORDER INVOCE
        //====================================================================//        
        case 'facturee':
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
    private function getOrderLineFields($Key,$FieldName)
    {
        global $conf;
        //====================================================================//
        // Create List Array If Needed
        if (!array_key_exists("lines",$this->Out)) {
            $this->Out["lines"] = array();
        }
        //====================================================================//
        // Verify List is Not Empty
        if ( !is_array($this->Object->lines) ) {
            return True;
        }        
        
        //====================================================================//
        // Fill List with Data
        foreach ($this->Object->lines as $key => $OrderLine) {
            
            //====================================================================//
            // READ Fields
            switch ($FieldName)
            {
                //====================================================================//
                // Order Line Description
                case 'desc@lines':
                    $Value = $OrderLine->desc;
//                    $Value = ($OrderLine->fk_product)?$OrderLine->product_label:$OrderLine->desc;
                    break;
                //====================================================================//
                // Order Line Product Id
                case 'fk_product@lines':
                    $Value = ($OrderLine->fk_product)?self::ObjectId_Encode( "Product" , $OrderLine->fk_product):Null;
                    break;
                //====================================================================//
                // Order Line Quantity
                case 'qty@lines':
                    $Value = (int) $OrderLine->qty;
                    break;
                //====================================================================//
                // Order Line Discount Percentile
                case "remise_percent@lines":
                    $Value = (double) $OrderLine->remise_percent;
                    break;                
                //====================================================================//
                // Order Line Quantity
                case 'price@lines':
                    $Value = self::Price_Encode(
                                    (double) $OrderLine->subprice,
                                    (double) $OrderLine->tva_tx,
                                    Null,
                                    $conf->global->MAIN_MONNAIE);
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Create Address Array If Needed
            if (!array_key_exists($key,$this->Out["lines"])) {
                $this->Out["lines"][$key] = array();
            }            
            //====================================================================//
            // Store Date in Array
            $FieldIndex = explode("@",$FieldName);
            $this->Out["lines"][$key][$FieldIndex[0]] = $Value;
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
            // STRUCTURAL INFORMATIONS
            //====================================================================//

//            case 'status':
//            case 'tva_assuj':
//            case 'fournisseur':
//                $this->getSingleBoolField($FieldName);
//                break;                
//
//            case 'client':
//                $this->Out[$FieldName] = (bool) $this->Bitwise_Read($this->Object->client, 0);
//                break;                
//
//            case 'prospect':
//                $this->Out[$FieldName] = (bool) $this->Bitwise_Read($this->Object->client, 1);
//                break;                



            //====================================================================//
            // TRACEABILITY INFORMATIONS
            //====================================================================//

            //====================================================================//
            // Last Modifictaion Date
            case 'date_creation':
            case 'date_modification':
                if (!$this->infoloaded)  {
                    $this->Object->info($this->Object->id);
                    $this->infoloaded = True;
                }
                $this->Out[$FieldName] = dol_print_date($this->Object->$FieldName,'standard');
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
    private function getPostCreateFields($Key,$FieldName)
    {
        global $conf;
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'ref_client':
            case 'ref_int':
            case 'ref_ext':
                $this->getSingleField($FieldName);
                break;
            
            //====================================================================//
            // ORDER STATUS
            //====================================================================//   
            case 'status':
                if ($this->Object->statut == -1) {
                    $this->Out[$FieldName]  = "OrderCanceled";
                } elseif ($this->Object->statut == 0) {
                    $this->Out[$FieldName]  = "OrderDraft";
                } elseif ($this->Object->statut == 1) {
                    $this->Out[$FieldName]  = "OrderProcessing";
                } elseif ($this->Object->statut == 2) {
                    $this->Out[$FieldName]  = "OrderInTransit";
                } elseif ($this->Object->statut == 3) {
                    $this->Out[$FieldName]  = "OrderDelivered";
                } else {
                    $this->Out[$FieldName]  = "Unknown";
                }
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
     *  @abstract     Init Object vefore Writting Fields
     * 
     *  @param        array   $id               Object Id. If NULL, Object needs t be created.
     * 
     */
    private function setInitObject($id) 
    {
        global $db,$user;
        
        //====================================================================//
        // Init Object 
        $this->Object = new Commande($db);
        
        //====================================================================//
        // If $id Given => Load Customer Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            if ( $this->Object->fetch($id) != 1 ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Order (" . $id . ").");
            }
            $this->Object->fetch_lines();
        }
        //====================================================================//
        // If NO $id Given  => Verify Input Data includes minimal valid values
        //                  => Setup Standard Parameters For New Customers
        //====================================================================//
        else
        {
            //====================================================================//
            // Check Order Date is given
            if ( empty($this->In["date"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"date");
            }
            //====================================================================//
            // Check Customer Id is given
            if ( empty($this->In["socid"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"socid");
            }
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
        global $user;
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'ref':
                $this->setSingleField($FieldName,$Data);
                break;
            
            //====================================================================//
            // Order Official Date
            case 'date':
                if (dol_print_date($this->Object->$FieldName, 'standard') === $Data) {
                    break;
                }
                //====================================================================//
                // Order Update Mode
                if ( $this->Object->id > 0) {
                    $this->Object->set_date($user, $Data);
                //====================================================================//
                // Order Create Mode
                } else {
                    $this->setSingleField($FieldName,$Data);
                }
                $this->update = True;
                break;     
                    
            //====================================================================//
            // Order Company Id 
            case 'socid':
                $SocId = self::ObjectId_DecodeId( $Data );
                $this->setSingleField($FieldName,$SocId);
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
        global $conf,$langs,$user; 
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Order Official Date
            case 'date_livraison':
                if (dol_print_date($this->Object->$FieldName, 'standard') === $Data) {
                    break;
                }
                //====================================================================//
                // Order Update Mode
                if ( $this->Object->id > 0) {
                    $this->Object->set_date_livraison($user, $Data);
                //====================================================================//
                // Order Create Mode
                } else {
                    $this->setSingleField($FieldName,$Data);
                }
                $this->update = True;
                break;   
               
        //====================================================================//
        // ORDER INVOCE
        //====================================================================//        
        case 'facturee':
            if ($Data) {
                $this->Object->classifyBilled();
            }
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
    private function setOrderLineFields($FieldName,$Data) 
    {
        global $db,$langs;
//Splash::Log()->www("setOrderLine", $Data);            
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "lines" ) {
            return True;
        }
        
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $LineData) {
            $this->orderlineupdate = False;
            //====================================================================//
            // Read Next Order Product Line
            $this->OrderLine = array_shift($this->Object->lines);
            //====================================================================//
            // Create New Line
            if ( !$this->OrderLine ) {
                $this->OrderLine = new OrderLine($db);
                $this->OrderLine->fk_commande = $this->Object->id;
//                $this->OrderLine->fk_commande = 5;
            }
//            //====================================================================//
//            // If Product Line doesn't Exists
//            if ( is_null($OrderLine) ) {
//                //====================================================================//
//                // Force Order Status To Draft
//                $this->Object->statut     = 0;
//                $this->Object->bouillon   = 1;
//                $this->Object->addline(
//                        $LineData["desc"], 
//                        $LineData["price"]["ht"], 
//                        $LineData["qty"], 
//                        $LineData["price"]["vat"],
//                                    0,0,
//                        $ProductId,
//                        array_key_exists("remise_percent", $LineData)?$LineData["remise_percent"]:0);
//                continue;
//            }
            
            //====================================================================//
            // Update Line Description
            $this->setOrderLineData($LineData,"desc");
            //====================================================================//
            // Update Line Label
            $this->setOrderLineData($LineData,"label");
            //====================================================================//
            // Update Quantity
            $this->setOrderLineData($LineData,"qty");
            //====================================================================//
            // Update Discount
            $this->setOrderLineData($LineData,"remise_percent");
            //====================================================================//
            // Update Sub-Price
            if (array_key_exists("price", $LineData) ) {
                if (!$this->Float_Compare($this->OrderLine->subprice,$LineData["price"]["ht"])) {
                    $this->OrderLine->subprice  = $LineData["price"]["ht"];
                    $this->OrderLine->price     = $LineData["price"]["ht"];
                    $this->orderlineupdate      = TRUE;
                }
                if (!$this->Float_Compare($this->OrderLine->tva_tx,$LineData["price"]["vat"])) {
                    $this->OrderLine->tva_tx    = $LineData["price"]["vat"];
                    $this->orderlineupdate      = TRUE;
                }
            }            

            //====================================================================//
            // Update Line Totals
            if ($this->orderlineupdate) {

                include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

                // Calcul du total TTC et de la TVA pour la ligne a partir de
                // qty, pu, remise_percent et txtva
                // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
                // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
                $localtaxes_type=getLocalTaxesFromRate($this->OrderLine->tva_tx,0,$this->OrderLine->socid);

                $tabprice=calcul_price_total(
                        $this->OrderLine->qty, $this->OrderLine->subprice, 
                        $this->OrderLine->remise_percent, $this->OrderLine->tva_tx, 
                        -1,-1,
//                        $this->OrderLine->localtax1_tx, $this->OrderLine->localtax2_tx, 
                        0, "HT", 
                        $this->OrderLine->info_bits, $this->OrderLine->type, 
                        '', $localtaxes_type);

                $this->OrderLine->total_ht  = $tabprice[0];
                $this->OrderLine->total_tva = $tabprice[1];
                $this->OrderLine->total_ttc = $tabprice[2];
                $this->OrderLine->total_localtax1 = $tabprice[9];
                $this->OrderLine->total_localtax2 = $tabprice[10];                
            }
            
            //====================================================================//
            // Commit Line Update
            if ( $this->orderlineupdate && $this->OrderLine->id ) {
                if ( $this->OrderLine->update() <= 0) {  
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Order Line. ");
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->OrderLine->error));
                    continue;
                }
                
            } elseif ( $this->orderlineupdate && !$this->OrderLine->id ) {
                if ( $this->OrderLine->insert() <= 0) {  
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create Order Line. ");
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->OrderLine->error));
                    continue;
                }
            }
            //====================================================================//
            // Update Product Link
            if (array_key_exists("fk_product", $LineData) && !empty($LineData["fk_product"]) ) {
                $ProductId = $this->ObjectId_DecodeId($LineData["fk_product"]);
                if ( $this->OrderLine->fk_product != $ProductId )  {
                    $this->OrderLine->setValueFrom("fk_product",$ProductId);
                    $this->orderlineupdate = TRUE;
                }   
            } elseif (array_key_exists("fk_product", $LineData) && empty($LineData["fk_product"]) ) {
                if ( $this->OrderLine->fk_product != 0 )  {
                    $this->OrderLine->setValueFrom("fk_product",0);
                    $this->orderlineupdate = TRUE;
                }   
            }       
            
            $this->OrderLine->update_total();
            
        } 
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Object->lines as $OrderLine) {
            //====================================================================//
            // Force Order Status To Draft
            $Object->statut         = 0;
            $Object->brouillon      = 1;
            //====================================================================//
            // Perform Line Delete
            $this->Object->deleteline($OrderLine->id);
        }        
        //====================================================================//
        // Update Order Total Prices
        $this->Object->update_price();
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function setOrderLineData($OrderLineData,$FieldName) 
    {
        if ( !array_key_exists($FieldName, $OrderLineData) ) {
            return;
        }
        if ($this->OrderLine->$FieldName !== $OrderLineData[$FieldName]) {
            $this->OrderLine->$FieldName = $OrderLineData[$FieldName];
            $this->orderlineupdate = TRUE;
        }   
        return;
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
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setPostCreateFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'ref_client':
            case 'ref_int':
            case 'ref_ext':
                //====================================================================//
                //  Compare Field Data
                if ( $this->Object->$FieldName != $Data ) {
                    //====================================================================//
                    //  Update Field Data
                    $this->Object->setValueFrom($FieldName,$Data);
                    $this->update = True;
                }  
                break;
            
            //====================================================================//
            // ORDER STATUS
            //====================================================================//        
            case 'status':
                $this->setOrderStatus($Data); 
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
        global $db,$user,$langs,$user,$conf;
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//
        
        if ( empty($this->Object->id) ) {
            //====================================================================//
            // Create Object In Database
            if ( $this->Object->create($user) <= 0) {    
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new Order. ");
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
            }
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Order Created");
            $this->update = False;
            //====================================================================//
            // LOCK PRODUCT to prevent triggered actions on PostCreate Update
            $this->Lock($this->Object->id);
        }
        
        //====================================================================//
        // Apply Post Create Parameter Changes 
        foreach ($this->In as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setPostCreateFields($FieldName,$Data);
            $this->setOrderLineFields($FieldName,$Data);
        }

        //====================================================================//
        // Verify Update Is requiered
        if ( !$this->update ) {
            Splash::Log()->War("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return $this->Object->id;
        }

        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        
        if (!empty($this->Object->id) && $this->update ) {
            //====================================================================//
            // Appel des triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($db);
            if ( $interface->run_triggers('ORDER_UPDATE',$this->Object,$user,$langs,$conf) <= 0) {  
                foreach ($interface->errors as $Error) {
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($Error));
                }
            }
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Order Updated");
            $this->update = False;
            return $this->Object->id;
        }
        
        return $this->Object->id; 
    }    
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//
   
    /**
     *   @abstract   Update Order Status
     * 
     *   @param      string     $Status         Schema.org Order Status String
     * 
     *   @return     bool 
     */
    private function setOrderStatus($Status) {
        global $conf,$langs,$user;
        $langs->load("stocks");
        //====================================================================//
        // Safety Check
        if ( empty($this->Object->id) ) {
            return False;
        }
        //====================================================================//
        // Verify Stock Is Defined if Required
        // If stock is incremented on validate order, we must increment it          
        if ( !empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1) {
            if ( empty($conf->global->SPLASH_STOCK ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, $langs->trans("WarehouseSourceNotDefined"));
            }
        }    
        //====================================================================//
        // Statut Canceled
        //====================================================================//
        // Statut Canceled
        if ( ($Status == "OrderCanceled") && ($this->Object->statut != -1) )    {
            //====================================================================//
            // If Previously Closed => Set Draft
            if ( ( $this->Object->statut == 3 ) && ( $this->Object->set_draft($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Draft", $langs->trans($this->Object->error) );
            }         
            //====================================================================//
            // If Previously Draft => Valid
            if ( ( $this->Object->statut == 0 ) && ( $this->Object->valid($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Validated", $langs->trans($this->Object->error) );
            }               
            //====================================================================//
            // Set Canceled
            if ( $this->Object->cancel($conf->global->SPLASH_STOCK) != 1 ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__,"Set Canceled", $langs->trans($this->Object->error) );
            }                
            return True;
        }
        //====================================================================//
        // If Previously Canceled => Re-Validate
        if ( ( $this->Object->statut == -1 ) && ( $this->Object->valid($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Validated Again", $langs->trans($this->Object->error) );
        }         
        //====================================================================//
        // Statut Draft
        if ( $Status == "OrderDraft" )    {
            //====================================================================//
            // If Not Draft (Validated or Closed)            
            if ( ($this->Object->statut != 0) && $this->Object->set_draft($user,$conf->global->SPLASH_STOCK) != 1 ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, $langs->trans($this->Object->error) );
            }                
            return True;
        }        
        //====================================================================//
        // Statut Validated || Closed => Go Valid if Draft
        if ( ( $this->Object->statut == 0 ) && ( $this->Object->valid($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Validated", $langs->trans($this->Object->error) );
        }         
        //====================================================================//
        // Statut Not Closed but Validated Only => ReOpen 
        if ($Status != "OrderDelivered")    {
            //====================================================================//
            // If Previously Closed => Re-Open
            if ( ( $this->Object->statut == 3 ) && ( $this->Object->set_reopen($user) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Re-Open", $langs->trans($this->Object->error) );
            }      
        }            
        //====================================================================//
        // Statut Closed => Go Closed
        if ( ($Status == "OrderDelivered") && ($this->Object->statut != 3) )    {
            //====================================================================//
            // If Previously Validated => Close
            if ( ( $this->Object->statut == 1 ) && ( $this->Object->cloture($user) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Closed", $langs->trans($this->Object->error) );
            }         
        }
        return True;
    }    
    
    
}



?>
