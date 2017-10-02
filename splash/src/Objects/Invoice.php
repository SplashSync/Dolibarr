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
//                CUSTOMERS INVOICE DATA MANAGEMENT                    //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Objects;

use Splash\Models\ObjectBase;
use Splash\Core\SplashCore      as Splash;

/**
 *	\class      Order
 *	\brief      Customers Invoices Management Class
 */
class Invoice extends ObjectBase
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
    protected static    $NAME            =  "Customer Invoice";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Dolibarr Customers Invoice Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-money";
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//

    private     $invoicelineupdate  = False;
    private     $forceDueDate       = 0;
    private     $Payments           = array();
    
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
        require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
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
        $langs->load("compta");
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
        // MAIN INVOICE LINE INFORMATIONS
        //====================================================================//
        $this->buildInvoiceLineFields();
        //====================================================================//
        //INVOICE PAYMENT INFORMATIONS
        //====================================================================//
        $this->buildPaymentLineFields();        
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
        $langs->load("compta");
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql    = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql .= " f.rowid as id,";                  // Object Id         
        $sql .= " f.facnumber as ref,";             // Dolibarr Reference  
        $sql .= " f.ref_ext as ref_ext,";           // External Reference  
        $sql .= " f.ref_int as ref_int,";           // Internal Reference 
        $sql .= " f.ref_client as ref_client,";     // Customer Reference
        $sql .= " f.total as total_ht,";            // Total net of tax
        $sql .= " f.total_ttc as total_ttc,";       // Total with tax
        $sql .= " f.datef as date";                 // Invoice date
        //====================================================================//
        // Select Database tables
        $sql   .= " FROM " . MAIN_DB_PREFIX . "facture as f ";
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in Invoice Ref.
            $sql   .= " WHERE LOWER( f.facnumber ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Invoice Internal Ref 
            $sql   .= " OR LOWER( f.ref_int ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Invoice External Ref
            $sql   .= " OR LOWER( f.ref_ext ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Invoice Customer Ref
            $sql   .= " OR LOWER( f.ref_client ) LIKE LOWER( '%" . $filter ."%') ";
        }   
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"f.rowid":$params["sortfield"];
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
        $this->Object = new \Facture($db);
        if ( $this->Object->fetch($id) != 1 )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Invoice (" . $id . ").");
        }
        $this->Object->fetch_lines();
        $this->getPaymentsList();
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
            $this->getMainFields($Key,$FieldName);
            $this->getInvoiceLineFields($Key,$FieldName);
            $this->getPaymentLineFields($Key,$FieldName);
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
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $FieldName => $Data) {
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
                Splash::Log()->War("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
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
        global $db,$user,$conf;
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Create Object
        $Object = new \Facture($db);
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
        // Debug Mode => Force Allow Delete
        if ( defined("SPLASH_DEBUG") && SPLASH_DEBUG ) {
            $conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED = 1;
        }
        //====================================================================//
        // Delete Object
        $Arg1 = ( Splash::Local()->DolVersionCmp("5.0.0") > 0 ) ? $user : 0;
        if ( $Object->delete($Arg1) <= 0 ) {         
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__, $Object->errorsToString());
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
                ->MicroData("http://schema.org/Invoice","customer")
                ->isRequired();  
        
        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref")
                ->Name($langs->trans("InvoiceRef"))
                ->MicroData("http://schema.org/Invoice","name")       
                ->ReadOnly()
                ->IsListed();

//        //====================================================================//
//        // Internal Reference
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
//                ->Identifier("ref_int")
//                ->Name($langs->trans("Ref"))
//                ->MicroData("http://schema.org/Invoice","confirmationNumber")       
//                ->ReadOnly()
//                ->IsListed();        

//        //====================================================================//
//        // External Reference
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
//                ->Identifier("ref_ext")
//                ->Name($langs->trans("Ref"))
//                ->MicroData("http://schema.org/Invoice","confirmationNumber")       
//                ->ReadOnly()
//                ->IsListed();        

        
        //====================================================================//
        // Invoice Date 
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
        global $conf,$langs;
        
        //====================================================================//
        // Customer Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref_client")
                ->Name($langs->trans("RefCustomerInvoice"))
                ->MicroData("http://schema.org/Invoice","confirmationNumber");
        
        //====================================================================//
        // Invoice PaymentDueDate Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date_lim_reglement")
                ->Name($langs->trans("DateMaxPayment"))
                ->MicroData("http://schema.org/Invoice","paymentDueDate");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Invoice Total Price HT
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_ht")
                ->Name($langs->trans("TotalHT") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDue")
                ->ReadOnly();
        
        //====================================================================//
        // Invoice Total Price TTC
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_ttc")
                ->Name($langs->trans("TotalTTC") . " (" . $conf->global->MAIN_MONNAIE . ")")
                ->MicroData("http://schema.org/Invoice","totalPaymentDueTaxIncluded")
                ->ReadOnly();        
        
        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//        

        //====================================================================//
        // Is Draft
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isDraft")
                ->Name($langs->trans("Invoice") . " : " . $langs->trans("Draft"))
                ->MicroData("http://schema.org/PaymentStatusType","InvoiceDraft")
                ->Association( "isDraft","isCanceled","isValidated")
                ->ReadOnly();     

        //====================================================================//
        // Is Canceled
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isCanceled")
                ->Name($langs->trans("Invoice") . " : " . $langs->trans("Canceled"))
                ->MicroData("http://schema.org/PaymentStatusType","PaymentDeclined")
                ->Association( "isDraft","isCanceled","isValidated")
                ->ReadOnly();     
        
        //====================================================================//
        // Is Validated
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isValidated")
                ->Name($langs->trans("Invoice") . " : " . $langs->trans("Validated"))
                ->MicroData("http://schema.org/PaymentStatusType","PaymentDue")
                ->Association( "isDraft","isCanceled","isValidated")
                ->ReadOnly();

        //====================================================================//
        // Is Paid
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isPaid")
                ->Name($langs->trans("Invoice") . " : " . $langs->trans("Paid"))
                ->MicroData("http://schema.org/PaymentStatusType","PaymentComplete");
//                ->ReadOnly();
        
        return;
    }
        
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildInvoiceLineFields() {
        global $langs;
        
        $ListName = $langs->trans("InvoiceLine") . " => " ;
        
        //====================================================================//
        // Invoice Line Description
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("desc")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Description"))
                ->MicroData("http://schema.org/partOfInvoice","description")        
                ->Association("desc@lines","qty@lines","price@lines");        

        //====================================================================//
        // Invoice Line Product Identifier
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Product" , SPL_T_ID))        
                ->Identifier("fk_product")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Product"))
                ->MicroData("http://schema.org/Product","productID")
                ->Association("desc@lines","qty@lines","price@lines");        
//                ->NotTested();        

        //====================================================================//
        // Invoice Line Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)        
                ->Identifier("qty")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Quantity"))
                ->MicroData("http://schema.org/QuantitativeValue","value")        
                ->Association("desc@lines","qty@lines","price@lines");        

        //====================================================================//
        // Invoice Line Discount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("remise_percent")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Discount"))
                ->MicroData("http://schema.org/Order","discount")
                ->Association("desc@lines","qty@lines","price@lines");        

        //====================================================================//
        // Invoice Line Unit Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)        
                ->Identifier("price")
                ->InList("lines")
                ->Name( $ListName . $langs->trans("Price"))
                ->MicroData("http://schema.org/PriceSpecification","price")        
                ->Association("desc@lines","qty@lines","price@lines");        

    }

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildPaymentLineFields() {
        global $langs;
        
//        $ListName = $langs->trans("Payment") . " => " ;
        $ListName = "" ;
        
        //====================================================================//
        // Payment Line Payment Method 
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("mode")
                ->InList("payments")
                ->Name( $ListName . $langs->trans("PaymentMode"))
                ->MicroData("http://schema.org/Invoice","PaymentMethod")
                ->AddChoice("ByBankTransferInAdvance"   , "By bank transfer in advance")
                ->AddChoice("CheckInAdvance"            , "Check in advance")
                ->AddChoice("COD"                       , "Cash On Delivery")
                ->AddChoice("Cash"                      , "Cash")
                ->AddChoice("PayPal"                    , "Online Payments (PayPal, more..)")
                ->AddChoice("DirectDebit"               , "Credit Card")
                ->NotTested();        

        //====================================================================//
        // Payment Line Date
        $this->FieldsFactory()->Create(SPL_T_DATE)        
                ->Identifier("date")
                ->InList("payments")
                ->Name( $ListName . $langs->trans("Date"))
                ->MicroData("http://schema.org/PaymentChargeSpecification","validFrom")
//                ->Association("date@payments","mode@payments","amount@payments");        
                ->NotTested();        

        //====================================================================//
        // Payment Line Payment Identifier
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)        
                ->Identifier("number")
                ->InList("payments")
                ->Name( $ListName . $langs->trans('Numero'))
                ->MicroData("http://schema.org/Invoice","paymentMethodId")        
//                ->Association("date@payments","mode@payments","amount@payments");        
                ->NotTested();        

        //====================================================================//
        // Payment Line Amount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("amount")
                ->InList("payments")
                ->Name( $ListName . $langs->trans("PaymentAmount"))
                ->MicroData("http://schema.org/PaymentChargeSpecification","price")
                ->NotTested();        

//        //====================================================================//
//        // Invoice Line Product Identifier
//        $this->FieldsFactory()->Create(self::ObjectId_Encode( "BankAccount" , SPL_T_ID))        
//                ->Identifier("accountid")
//                ->InList("payments")
//                ->Name( $ListName . $langs->trans("AccountToDebit"))
//                ->MicroData("http://schema.org/Invoice","accountId");
//                ->Association("desc@lines","qty@lines","price@lines");        
//                ->NotTested();             
        
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
                ->Identifier("datem")
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
        // Internal Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref_int")
                ->Name($langs->trans("InternalRef"))
                ->MicroData("http://schema.org/Invoice","description");
                
        //====================================================================//
        // External Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref_ext")
                ->Name($langs->trans("RefExt"))
                ->IsListed()
                ->MicroData("http://schema.org/Invoice","alternateName");
        
        //====================================================================//
        // Order Current Status
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("status")
                ->Name($langs->trans("Status"))
                ->MicroData("http://schema.org/Invoice","paymentStatus")
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
            // Invoice Official Date
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
            // Direct Readings
            case 'ref_client':
                $this->getSingleField($FieldName);
                break;
            
            //====================================================================//
            // Order Delivery Date
            case 'date_lim_reglement':
                $this->Out[$FieldName] = !empty($this->Object->date_lim_reglement)?dol_print_date($this->Object->date_lim_reglement, '%Y-%m-%d'):Null;
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

            case 'isDraft':
                $this->Out[$FieldName]  = ( $this->Object->statut == 0 )?True:False;
                break;
            case 'isCanceled':
                $this->Out[$FieldName]  = ( $this->Object->statut == 3 )?True:False;
                break;
            case 'isValidated':
                $this->Out[$FieldName]  = ( $this->Object->statut == 1 )?True:False;
                break;
            case 'isPaid':
                $this->Out[$FieldName]  = ( $this->Object->statut == 2 )?True:False;
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
    private function getInvoiceLineFields($Key,$FieldName)
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
        foreach ($this->Object->lines as $key => $InvoiceLine) {
            
            //====================================================================//
            // READ Fields
            switch ($FieldName)
            {
                //====================================================================//
                // Order Line Description
                case 'desc@lines':
                    $Value = $InvoiceLine->desc;
//                    $Value = ($InvoiceLine->fk_product)?$InvoiceLine->product_label:$InvoiceLine->desc;
                    break;
                //====================================================================//
                // Order Line Product Id
                case 'fk_product@lines':
                    $Value = ($InvoiceLine->fk_product)?self::ObjectId_Encode( "Product" , $InvoiceLine->fk_product):Null;
                    break;
                //====================================================================//
                // Order Line Quantity
                case 'qty@lines':
                    $Value = (int) $InvoiceLine->qty;
                    break;
                //====================================================================//
                // Order Line Discount Percentile
                case "remise_percent@lines":
                    $Value = (double) $InvoiceLine->remise_percent;
                    break;                
                //====================================================================//
                // Order Line Quantity
                case 'price@lines':
                    $Value = self::Price_Encode(
                                    (double) $InvoiceLine->subprice,
                                    (double) $InvoiceLine->tva_tx,
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
    private function getPaymentsList()
    {
        global $db, $conf;
        
        //====================================================================//
        // Prepare SQL Request
	// Payments already done (from payment on this invoice)
	$sql = 'SELECT p.datep as date, p.num_paiement as number, p.rowid as id, p.fk_bank,';
	$sql .= ' c.code as code, c.libelle as payment_label,';
	$sql .= ' pf.amount as amount,';
	$sql .= ' ba.rowid as baid, ba.ref, ba.label';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_paiement as c, ' . MAIN_DB_PREFIX . 'paiement_facture as pf, ' . MAIN_DB_PREFIX . 'paiement as p';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank as b ON p.fk_bank = b.rowid';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON b.fk_account = ba.rowid';
	$sql .= ' WHERE pf.fk_facture = ' . $this->Object->id . ' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
	$sql .= ' ORDER BY p.rowid';

        //====================================================================//
        // Execute SQL Request
	$Result = $db->query($sql);
	if (!$Result) {
            dol_print_error($db);
            return False;
        }
        //====================================================================//
        // Count Results
        $Count = $db->num_rows($Result);
        if ($Count == 0) {
            return True;
        }
	//====================================================================//
        // Fetch Results
        $i = 0;
        while ($i < $Count) {
            $this->Payments[$i] = $db->fetch_object($Result);
            //====================================================================//
            // Detect Payment Method Type from Default Payment "known" methods
            switch ($this->Payments[$i]->code){
                case "PRE":
                case "PRO":
                case "TIP":
                case "VIR":
                    $this->Payments[$i]->method = "ByBankTransferInAdvance";
                    break;
                case "CHQ":
                    $this->Payments[$i]->method = "CheckInAdvance";
                    break;
                case "FAC":
                    $this->Payments[$i]->method = "COD";
                    break;
                case "LIQ":
                    $this->Payments[$i]->method = "Cash";
                    break;
                case "CB":
                    $this->Payments[$i]->method = "DirectDebit";
                    break;
                case "VAD":
                    $this->Payments[$i]->method = "PayPal";
                    break;
                default:
                    $this->Payments[$i]->method = "Unknown";
            }
            $i ++;
        }
	$db->free($Result);
        return True;
    }    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getPaymentLineFields($Key,$FieldName)
    {
        global $conf;
        //====================================================================//
        // Create List Array If Needed
        if (!array_key_exists("payments",$this->Out)) {
            $this->Out["payments"] = array();
        }
        //====================================================================//
        // Verify List is Not Empty
        if ( !is_array($this->Payments) ) {
            return True;
        }       
        //====================================================================//
        // Fill List with Data
        foreach ($this->Payments as $key => $PaymentLine) {
            //====================================================================//
            // READ Fields
            switch ($FieldName)
            {
                //====================================================================//
                // Payment Line - Payment Mode
                case 'mode@payments':
                    $Value = $PaymentLine->method;
                    break;
                //====================================================================//
                // Payment Line - Payment Date
                case 'date@payments':
                    $Value = !empty($PaymentLine->date)?dol_print_date($PaymentLine->date, '%Y-%m-%d'):Null;
                    break;
                //====================================================================//
                // Payment Line - Payment Identification Number
                case 'number@payments':
                    $Value = $PaymentLine->number;
                    break;
                //====================================================================//
                // Payment Line - Payment Amount
                case 'amount@payments':
                    $Value = $PaymentLine->amount;
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Create Address Array If Needed
            if (!array_key_exists($key,$this->Out["payments"])) {
                $this->Out["payments"][$key] = array();
            }            
            //====================================================================//
            // Store Data in Array
            $FieldIndex = explode("@",$FieldName);
            $this->Out["payments"][$key][$FieldIndex[0]] = $Value;
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
            case 'datem':
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
            case 'ref_int':
            case 'ref_ext':
                $this->getSingleField($FieldName);
                break;
            
            //====================================================================//
            // INVOICE STATUS
            //====================================================================//   
            case 'status':
                if ($this->Object->statut == 0) {
                    $this->Out[$FieldName]  = "PaymentDraft";
                } elseif ($this->Object->statut == 1) {
                    $this->Out[$FieldName]  = "PaymentDue";
                } elseif ($this->Object->statut == 2) {
                    $this->Out[$FieldName]  = "PaymentComplete";
                } elseif ($this->Object->statut == 3) {
                    $this->Out[$FieldName]  = "PaymentCanceled";
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
        global $db;
        
        //====================================================================//
        // Init Object 
        $this->Object = new \Facture($db);
        
        //====================================================================//
        // If $id Given => Load Customer Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            if ( $this->Object->fetch($id) != 1 ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Invoice (" . $id . ").");
            }
            $this->Object->fetch_lines();
            $this->getPaymentsList();            
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
            // Invoice Official Date
            case 'date':
                if (dol_print_date($this->Object->$FieldName, 'standard') !== $Data) {
                    $this->setSingleField($FieldName,$Data);
                }
                //====================================================================//
                // If New Invoice & No Max Payment Date => Force Due date
                if ($this->Object->id == 0) {
                    $this->forceDueDate = $Data;
                }

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
            // Direct Writting
            case 'ref_client':
                $this->setSingleField($FieldName,$Data);
                break;            
            
            //====================================================================//
            // Invoice Payment Due Date
            case 'date_lim_reglement':
                if (dol_print_date($this->Object->$FieldName, 'standard') === $Data) {
                    break;
                }
                //====================================================================//
                // Invoice Update Mode
                if ( $this->Object->id == 0) {
                    $this->forceDueDate = $Data;
                //====================================================================//
                // Invoice Create Mode
                } else {
                    $this->setSingleField($FieldName,$Data);
                }
                $this->update = True;
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
    private function setInvoiceLineFields($FieldName,$Data) 
    {
        global $db,$langs, $mysoc;         
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "lines" ) {
            return True;
        }
        
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $LineData) {
            $this->invoicelineupdate = False;
            //====================================================================//
            // Read Next Order Product Line
            $this->InvoiceLine = array_shift($this->Object->lines);
            //====================================================================//
            // Create New Line
            if ( !$this->InvoiceLine ) {
                $this->InvoiceLine = new \FactureLigne($db);
                $this->InvoiceLine->fk_facture = $this->Object->id;
            }
            //====================================================================//
            // Update Line Description
            $this->setInvoiceLineData($LineData,"desc");
            //====================================================================//
            // Update Line Label
            $this->setInvoiceLineData($LineData,"label");
            //====================================================================//
            // Update Quantity
            $this->setInvoiceLineData($LineData,"qty");
            //====================================================================//
            // Update Discount
            $this->setInvoiceLineData($LineData,"remise_percent");
            //====================================================================//
            // Update Sub-Price
            if (array_key_exists("price", $LineData) ) {
                if (!$this->Float_Compare($this->InvoiceLine->subprice,$LineData["price"]["ht"])) {
                    $this->InvoiceLine->subprice  = $LineData["price"]["ht"];
                    $this->InvoiceLine->price     = $LineData["price"]["ht"];
                    $this->invoicelineupdate      = TRUE;
                }
                if (!$this->Float_Compare($this->InvoiceLine->tva_tx,$LineData["price"]["vat"])) {
                    $this->InvoiceLine->tva_tx    = $LineData["price"]["vat"];
                    $this->invoicelineupdate      = TRUE;
                }
            }            
            //====================================================================//
            // Update Line Totals
            if ($this->invoicelineupdate) {
                //====================================================================//
                // Calcul du total TTC et de la TVA pour la ligne a partir de
                include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
                $localtaxes_type=getLocalTaxesFromRate($this->InvoiceLine->tva_tx,0,$this->InvoiceLine->socid, $mysoc);
                $tabprice=calcul_price_total(
                        $this->InvoiceLine->qty, $this->InvoiceLine->subprice, 
                        $this->InvoiceLine->remise_percent, $this->InvoiceLine->tva_tx, 
                        -1,-1,
                        0, "HT", 
                        $this->InvoiceLine->info_bits, $this->InvoiceLine->type, 
                        '', $localtaxes_type);
                $this->InvoiceLine->total_ht  = $tabprice[0];
                $this->InvoiceLine->total_tva = $tabprice[1];
                $this->InvoiceLine->total_ttc = $tabprice[2];
                $this->InvoiceLine->total_localtax1 = $tabprice[9];
                $this->InvoiceLine->total_localtax2 = $tabprice[10];                
            }
            //====================================================================//
            // Commit Line Update
            if ( $this->invoicelineupdate && $this->InvoiceLine->rowid ) {
                if ( $this->InvoiceLine->update() <= 0) {  
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Invoice Line. ");
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->InvoiceLine->error));
                    continue;
                }
                
            } elseif ( $this->invoicelineupdate && !$this->InvoiceLine->rowid ) {
                if ( $this->InvoiceLine->insert() <= 0) {  
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create Invoice Line. ");
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->InvoiceLine->error));
                    continue;
                }
            }
            //====================================================================//
            // Update Product Link
            if (array_key_exists("fk_product", $LineData) && !empty($LineData["fk_product"]) ) {
                $ProductId = $this->ObjectId_DecodeId($LineData["fk_product"]);
                if ( $this->InvoiceLine->fk_product != $ProductId )  {
                    $this->InvoiceLine->setValueFrom("fk_product",$ProductId);
                    $this->invoicelineupdate = TRUE;
                }   
            } elseif (array_key_exists("fk_product", $LineData) && empty($LineData["fk_product"]) ) {
                if ( $this->InvoiceLine->fk_product != 0 )  {
                    $this->InvoiceLine->setValueFrom("fk_product",0);
                    $this->invoicelineupdate = TRUE;
                }   
            }       
            $this->InvoiceLine->update_total();
        } 
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Object->lines as $InvoiceLine) {
            //====================================================================//
            // Force Order Status To Draft
            $Object->statut         = 0;
            $Object->brouillon      = 1;
            //====================================================================//
            // Perform Line Delete
            $this->Object->deleteline($InvoiceLine->rowid);
        }        
        //====================================================================//
        // Update Invoice Total Prices
        $this->Object->update_price();
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
    private function setPaymentLineFields($FieldName,$Data) 
    {
        global $db;         
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "payments" ) {
            return True;
        }
        
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';        
        require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
        
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $LineData) {
            $this->setPaymentLineData($LineData);
        } 
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Payments as $PaymentData) {
            
            //====================================================================//
            // Fetch Payment Line Entity
            $this->PaymentLine = new \Paiement($db);
            $this->PaymentLine->fetch($PaymentData->id);            
            //====================================================================//
            // Check If Payment impact another Bill
            if ( count($this->PaymentLine->getBillsArray()) > 1) {
                continue;
            }
            //====================================================================//
            // Try to delete Payment Line
            $this->PaymentLine->delete(); 
        }        
        
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        array     $InvoiceLineData          OrderLine Data Array
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function setInvoiceLineData($InvoiceLineData,$FieldName) 
    {
        if ( !array_key_exists($FieldName, $InvoiceLineData) ) {
            return;
        }
        if ($this->InvoiceLine->$FieldName !== $InvoiceLineData[$FieldName]) {
            $this->InvoiceLine->$FieldName = $InvoiceLineData[$FieldName];
            $this->invoicelineupdate = TRUE;
        }   
        return;
    }   

    /**
     *  @abstract     Update a Payment line Data
     * 
     *  @param        array     $LineData          Line Data Array
     * 
     *  @return         none
     */
    private function setPaymentLineData($LineData)
    {
        global $db,$langs,$conf,$user;         
        
        //====================================================================//
        // Read Next Payment Line
        $this->PaymentData = array_shift($this->Payments);

        //====================================================================//
        // Existing Line
        // 
        // => Update Date & Payment reference (Number)
        // => If Amount is Different, delete Payment & Re-Create
        // => If Payment method is different => Do nothing!!
        //====================================================================//
        if ( $this->PaymentData ) {

            $this->PaymentLine = new \Paiement($db);
            $this->PaymentLine->fetch($this->PaymentData->id);
            //====================================================================//
            // Update Payment Date
            if ( array_key_exists("date", $LineData) 
                && (dol_print_date($this->PaymentLine->datepaye, 'standard') !== $LineData["date"]) ) 
            {
                    $this->PaymentLine->update_date($LineData["date"]);
            }
            
            //====================================================================//
            // Update Payment Number
            if ( array_key_exists("number", $LineData) 
                && ($this->PaymentLine->num_paiement !== $LineData["number"]) ) 
            {
                    $this->PaymentLine->update_num($LineData["number"]);
            }
            
            //====================================================================//
            // Update Payment Method
            if ( array_key_exists("mode", $LineData) ) {
                //====================================================================//
                // Detect Payment Method Id
                $NewMethodId        = $this->IdentifyPaymentMethod($LineData["mode"]);
                $CurrentMethodId    = $this->IdentifyPaymentType($this->PaymentLine->type_code);
                if ($NewMethodId && ($CurrentMethodId !== $NewMethodId) ) {
                    $this->PaymentLine->setValueFrom("fk_paiement",$NewMethodId);
                }
                
            }
              
            //====================================================================//
            // Check If Payment impact another Bill => Too Late to Delete & recreate this payment
            if ( count($this->PaymentLine->getBillsArray()) > 1) {
                return;
            }
            
            //====================================================================//
            // Check If Payment Amount are Different
            if ( !array_key_exists("amount", $LineData) 
                || ( $this->PaymentLine->amount ==  $LineData["amount"]) )
            {
                return;
            }  
            
            //====================================================================//
            // Try to delete Payment
            if ( $this->PaymentLine->delete() <= 0) {
                return;
            }
        }

        //====================================================================//
        // Create New Line
        //====================================================================//

        //====================================================================//
        // Verify Minimal Fields Ar available
        if ( !array_key_exists("mode", $LineData) 
                || !array_key_exists("date" , $LineData)
                || !array_key_exists("amount" , $LineData)
                || empty($LineData["amount"]) ) {
            return;
        }
        $this->PaymentLine = new \Paiement($db);
        //====================================================================//
        // Setup Payment Invoice Id
        $this->PaymentLine->facid       =   $this->Object->id;
        //====================================================================//
        // Setup Payment Date
        $this->PaymentLine->datepaye    =   $LineData["date"];
        //====================================================================//
        // Setup Payment Method
        $this->PaymentLine->paiementid =   $this->IdentifyPaymentMethod($LineData["mode"]); 
        //====================================================================//
        // Setup Payment Refrence
        $this->PaymentLine->num_paiement=   $LineData["number"]; 
        //====================================================================//
        // Setup Payment Amount
        $this->PaymentLine->amounts[$this->PaymentLine->facid]    = $LineData["amount"];
        //====================================================================//
        // Create Payment Line
        if ( $this->PaymentLine->create($user) <= 0) {  
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create Invoice Payment. ");
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->PaymentLine->error));
        }    

        //====================================================================//
        // Setup Payment Account Id
        $Result = $this->PaymentLine->addPaymentToBank($user,'payment','(Payment)',$conf->global->SPLASH_BANK,"","");
        if ( $Result < 0) {  
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to add Invoice Payment to Bank Account. ");
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->PaymentLine->error));
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
        global $user,$langs;
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writting
            case 'ref_int':
            case 'ref_ext':
                //====================================================================//
                //  Compare Field Data
                if ( $this->Object->$FieldName == $Data ) {
                    break;                   
                }  
                //====================================================================//
                // Invoice Create Mode
                if ( $this->Object->id == 0) {
                    $this->setSingleField($FieldName,$Data);
                //====================================================================//
                // Invoice Update Mode
                } else {
                    $this->Object->setValueFrom($FieldName,$Data);
                    $this->update = True;
                }
                break;                   

            //====================================================================//
            // PAYMENT STATUS
            //====================================================================//        
            case 'isPaid':
                if ( $Data == $this->Object->paye ) {
                    break;                   
                }                
                //====================================================================//
                // Set Paid
                if ( $Data && ( $this->Object->set_paid($user) != 1 ) ) {
                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Paid", $langs->trans($this->Object->error) );
                }     
                //====================================================================//
                // Set Paid
                if ( !$Data && ( $this->Object->set_unpaid($user) != 1 ) ) {
                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set UnPaid", $langs->trans($this->Object->error) );
                }                     
                $this->update = True;
                break; 
                
            //====================================================================//
            // INVOICE STATUS
            //====================================================================//        
            case 'status':
                $this->setInvoiceStatus($Data); 
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
            if ( $this->Object->create($user,1,$this->forceDueDate) <= 0) {    
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new Invoice. ");
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
            }
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Invoice Created");
            $this->update = False;
            //====================================================================//
            // LOCK PRODUCT to prevent triggered actions on PostCreate Update
            $this->Lock($this->Object->id);

        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        } else {
            if ( $this->update && $this->Object->update($user) <= 0) {    
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Invoice. ");
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
            }
            $this->update = False;            
        }
        
        //====================================================================//
        // Apply Post Create Parameter Changes 
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;        
        foreach ($Fields as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setPostCreateFields($FieldName,$Data);
            $this->setInvoiceLineFields($FieldName,$Data);
            $this->setPaymentLineFields($FieldName,$Data);
        }

        if (!$this->update ) {
            return $this->Object->id; 
        }
        
        //====================================================================//
        // Appel des triggers
        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
        $interface=new \Interfaces($db);
        if ( $interface->run_triggers('BILL_UPDATE',$this->Object,$user,$langs,$conf) <= 0) {  
            foreach ($interface->errors as $Error) {
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($Error));
            }
        }
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Invoice Updated");
        $this->update = False;
        
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
    private function setInvoiceStatus($Status) {
        global $conf,$langs,$user;
        $langs->load("stocks");
        //====================================================================//
        // Safety Check
        if ( empty($this->Object->id) ) {
            return False;
        }
        //====================================================================//
        // Verify Stock Is Defined if Required
        // If stock is incremented on validate invoice, we must provide warhouse id          
        if ( !empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_BILL == 1) {
            if ( empty($conf->global->SPLASH_STOCK ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, $langs->trans("WarehouseSourceNotDefined"));
            }
        }    
        switch ($Status)
        {
            //====================================================================//
            // Status Draft
            //====================================================================//
            case "Unknown":
            case "PaymentDraft":
                //====================================================================//
                // Whatever => Set Draft
                if ( ( $this->Object->statut != 0 ) && ( $this->Object->set_draft($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Draft", $langs->trans($this->Object->error) );
                }     
                break;
            //====================================================================//
            // Status Validated
            //====================================================================//
            case "PaymentDue":
            case "PaymentDeclined":
            case "PaymentPastDue":
                //====================================================================//
                // If Already Paid => Set Draft
                if ( ( $this->Object->statut == 2 ) && ( $this->Object->set_draft($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Draft", $langs->trans($this->Object->error) );
                }     
                //====================================================================//
                // If Already Canceled => Set Draft
                if ( ( $this->Object->statut == 3 ) && ( $this->Object->set_draft($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Draft", $langs->trans($this->Object->error) );
                }  
                //====================================================================//
                // If Not Valdidated => Set Validated
                if ( ( $this->Object->statut != 1 ) && ( $this->Object->validate($user,"",$conf->global->SPLASH_STOCK) != 1 ) ) {
                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Validated", $langs->trans($this->Object->error) );
                }                  
                break;
            //====================================================================//
            // Status Paid
            //====================================================================//
            case "PaymentComplete":
                //====================================================================//
                // If Draft => Set Validated
                if ( ( $this->Object->statut == 0 ) && ( $this->Object->validate($user,"",$conf->global->SPLASH_STOCK) != 1 ) ) {
                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Validated", $langs->trans($this->Object->error) );
                }                  
                //====================================================================//
                // If Validated => Set Paid
                if ( ( $this->Object->statut == 1 ) && ( $this->Object->set_paid($user) != 1 ) ) {
                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Paid", $langs->trans($this->Object->error) );
                }     
//                //====================================================================//
//                // If Closed => Set Paid
//                if ( ( $this->Object->statut == 2 ) && ( $this->Object->set_paid($user) != 1 ) ) {
//                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Paid", $langs->trans($this->Object->error) );
//                }     
//                //====================================================================//
//                // If Canceleded => Reopen & Set Paid
//                if ( ( $this->Object->statut == 3 ) && ( $this->Object->set_unpaid($user) != 1 ) ) {
//                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set UnPaid", $langs->trans($this->Object->error) );
//                }     
                break; 
            //====================================================================//
            // Status Canceled
            //====================================================================//
            case "PaymentCanceled":
                //====================================================================//
                // Whatever => Set Canceled
                if ( ( $this->Object->statut != 3 ) && ( $this->Object->set_canceled($user) != 1 ) ) {
                    return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Canceled", $langs->trans($this->Object->error) );
                }                  
                break;                  
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
    private function IdentifyPaymentMethod($MethodType) 
    {
        global $conf;         
        
        //====================================================================//
        // Detect Payment Method Type from Default Payment "known/standard" methods
        switch ($MethodType){
            case "ByBankTransferInAdvance":
                return $this->IdentifyPaymentType("VIR");
            case "CheckInAdvance":
                return $this->IdentifyPaymentType("CHQ");
            case "COD":
                return $this->IdentifyPaymentType("FAC");
            case "Cash":
                return $this->IdentifyPaymentType("LIQ");
            case "PayPal":
                return $this->IdentifyPaymentType("VAD");
            case "CreditCard":
            case "DirectDebit":
                return $this->IdentifyPaymentType("CB");
        }        
        
        //====================================================================//
        // Return Default Payment Method or 0 (Default) 
        if ( isset($conf->global->SPLASH_DEFAULT_PAYMENT) && !empty($conf->global->SPLASH_DEFAULT_PAYMENT) ) {
            return $this->IdentifyPaymentType($conf->global->SPLASH_DEFAULT_PAYMENT);
        }
        return $this->IdentifyPaymentType("VAD");
    }
    
    /**
     *  @abstract     Identify Payment Method Id using Payment Method Code
     * 
     *  @param        string    $PaymentTypeCode        Payment Method Code
     * 
     *  @return       int
     */
    private function IdentifyPaymentType($PaymentTypeCode) 
    {
        global $db;         
        
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';      
        $Form = new \Form($db);
        $Form->load_cache_types_paiements();
        //====================================================================//
        // Safety Check
        if ( empty($Form->cache_types_paiements) ) {
            return 0;
        }    
        //====================================================================//
        // Detect Payment Method Id From Method Code
        foreach ($Form->cache_types_paiements as $Key => $PaymentMethod) {
            if ( $PaymentMethod["code"] === $PaymentTypeCode ) {
                return $Key;
            }   
        }
        //====================================================================//
        // Default Payment Method Id 
        return 0;
    }
    
}



?>
