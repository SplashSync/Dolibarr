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
//                  THIRDPARTY DATA MANAGEMENT                         //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Objects;

use Splash\Models\ObjectBase;
use Splash\Core\SplashCore      as Splash;
use Societe;

/**
 *	\class      ThirdParty
 *	\brief      ThirdParty - Customers / Supplier Management Class
 */
class ThirdParty extends ObjectBase
{
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
    protected static    $NAME            =  "Company";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Dolibarr Company Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-user";
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//

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
        // MAIN ADDRESS INFORMATIONS
        //====================================================================//
        $this->buildAddressFields();
        
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
    *   @param        array   $filter               Filters for Customers List. 
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
        $langs->load("companies");
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        $sql    = "SELECT ";
        //====================================================================//
        // Select Database fields
        $sql   .= " s.rowid as id,";                   // Object Id         
        $sql   .= " s.nom as name,";                   // Company Name 
        $sql   .= " s.code_client as code_client,";    // Reference
        $sql   .= " s.phone as phone,";                // Phone
        $sql   .= " s.email as email,";                // Email
        $sql   .= " s.zip as zip,";                    // ZipCode
        $sql   .= " s.town as town,";                  // City
        if (Splash::Local()->DolVersionCmp("3.7.0") >= 0) {
            $sql   .= " p.label as country,";          // Country Name
        } else {
            $sql   .= " p.libelle as country,";        // Country Name
        }          
        $sql   .= " s.status as status,";              // Active
        $sql   .= " s.tms as modified";                // last modified date
        //====================================================================//
        // Select Database tables
        $sql   .= " FROM " . MAIN_DB_PREFIX . "societe as s ";
        
        if (Splash::Local()->DolVersionCmp("3.7.0") >= 0) {
            $sql   .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as p on s.fk_pays = p.rowid"; 
        } else {
            $sql   .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_pays as p on s.fk_pays = p.rowid"; 
        }        
        
        //====================================================================//
        // Setup filters
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in Customer Code
            $sql   .= " WHERE LOWER( s.code_client ) LIKE LOWER( '%" . $filter ."%') ";        
            //====================================================================//
            // Search in Customer Name
            $sql   .= " OR LOWER( s.nom ) LIKE LOWER( '%" . $filter ."%') ";        
            //====================================================================//
            // Search in Customer Phone
            $sql   .= " OR LOWER( s.phone ) LIKE LOWER( '%" . $filter ."%') ";        
            //====================================================================//
            // Search in Customer Email
            $sql   .= " OR LOWER( s.email ) LIKE LOWER( '%" . $filter ."%') ";        
            //====================================================================//
            // Search in Customer Zip
            $sql   .= " OR LOWER( s.zip ) LIKE LOWER( '%" . $filter ."%') ";        
            //====================================================================//
            // Search in Customer Town
            $sql   .= " OR LOWER( s.town ) LIKE LOWER( '%" . $filter ."%') ";        
        }  
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"s.nom":$params["sortfield"];
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
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__, " " . $i . " Customers Found.");
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
        // Load Default Language
        Splash::Local()->LoadDefaultLanguage();
        //====================================================================//
        // Init Reading
        $this->In = $list;
        //====================================================================//
        // Init Object 
        $this->Object = new Societe($db);
        if ( $this->Object->fetch($id) != 1 )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer (" . $id . ").");
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
            $this->getMainFields($Key,$FieldName);
            $this->getAddressFields($Key,$FieldName);
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
            $this->setMainFields($FieldName,$Data);
            $this->setAddressFields($FieldName,$Data);
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
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        
        //====================================================================//
        // Create Object
        $Object = new Societe($db);
        
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
        if ( $Object->delete($id) <= 0) {  
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
        global $langs,$conf;
        
        //====================================================================//
        // Company
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->Name($langs->trans("CompanyName"))
                ->isLogged()
                ->Description($langs->trans("CompanyName"))
                ->MicroData("http://schema.org/Organization","legalName")       // 8f0ca290d33f34b64658814bd2642d60
                ->isRequired()
                ->IsListed();

        //====================================================================//
        // Firstname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name($langs->trans("Firstname"))
                ->isLogged()
                ->MicroData("http://schema.org/Person","familyName")
                ->Association("firstname","lastname");        
        
        //====================================================================//
        // Lastname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name($langs->trans("Lastname"))
                ->isLogged()
                ->MicroData("http://schema.org/Person","givenName")
                ->Association("firstname","lastname");        
                
        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("code_client")
                ->Name($langs->trans("CustomerCode"))
                ->Description($langs->trans("CustomerCodeDesc"))
                ->IsListed()
                ->MicroData("http://schema.org/Organization","alternateName");
        //====================================================================//
        // Set as Read Only when Auto-Generated by Dolibarr        
        if ($conf->global->SOCIETE_CODECLIENT_ADDON != "mod_codeproduct_leopard") {
             $this->FieldsFactory()->ReadOnly();
        }  
        
    }    

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        global $langs;
        
        //====================================================================//
        // Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone")
                ->Name($langs->trans("Phone"))
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","telephone")
                ->isListed();

        //====================================================================//
        // Email
        $this->FieldsFactory()->Create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name($langs->trans("Email"))
                ->MicroData("http://schema.org/ContactPoint","email")
                ->isLogged()
                ->isListed();  
        
        //====================================================================//
        // WebSite
        $this->FieldsFactory()->Create(SPL_T_URL)
                ->Identifier("url")
                ->Name($langs->trans("PublicUrl"))
                ->MicroData("http://schema.org/Organization","url");

        //====================================================================//
        // Id Professionnal 1 SIREN
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("idprof1")
                ->Group("ID")
                ->Name($langs->trans("ProfId1Short"))
                ->MicroData("http://schema.org/Organization","duns");
        
        //====================================================================//
        // Id Professionnal 2 SIRET
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("idprof2")
                ->Group("ID")
                ->Name($langs->trans("ProfId2Short"))
                ->MicroData("http://schema.org/Organization","taxID");

        //====================================================================//
        // Id Professionnal 3 APE
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("idprof3")
                ->Group("ID")
                ->Name($langs->trans("ProfId3Short"))
                ->MicroData("http://schema.org/Organization","naics");
        
        //====================================================================//
        // Id Professionnal 4 RCS
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("idprof4")
                ->Group("ID")
                ->Name($langs->trans("ProfId4Short"))
                ->MicroData("http://schema.org/Organization","isicV4");
        
        //====================================================================//
        // Id Professionnal 5
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("idprof5")
                ->Group("ID")
                ->Name($langs->trans("ProfId5Short"));
        
        
        //====================================================================//
        // Id Professionnal 6
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("idprof6")
                ->Name($langs->trans("ProfId6Short"))
                ->Group("ID")
                ->MicroData("http://splashync.com/schemas","ObjectId");

        //====================================================================//
        // VAT Number
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("tva_intra")
                ->Name($langs->trans("VATIntra"))
                ->Group("ID")
                ->MicroData("http://schema.org/Organization","vatID");
        
        return;
    }
        
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildAddressFields() {
        global $langs;
        
        $GroupName = $langs->trans("CompanyAddress");
        //====================================================================//
        // Addess
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("address")
                ->Name($langs->trans("CompanyAddress"))
                ->Group($GroupName)
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","streetAddress");

        //====================================================================//
        // Zip Code
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("zip")
                ->Name( $langs->trans("CompanyZip"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/PostalAddress","postalCode")
                ->isLogged()
                ->isListed();
        
        //====================================================================//
        // City Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("town")
                ->Name($langs->trans("CompanyTown"))
                ->Group($GroupName)
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","addressLocality");
        
        //====================================================================//
        // State Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Group($GroupName)
                ->Name($langs->trans("State"))
                ->ReadOnly();
        
        //====================================================================//
        // State code
        $this->FieldsFactory()->Create(SPL_T_STATE)
                ->Identifier("state_code")
                ->Name($langs->trans("State Code"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/PostalAddress","addressRegion")
                ->ReadOnly();
        
        //====================================================================//
        // Country Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("country")
                ->Name($langs->trans("CompanyCountry"))
                ->Group($GroupName)
                ->ReadOnly()
                ->isListed();
        
        //====================================================================//
        // Country ISO Code
        $this->FieldsFactory()->Create(SPL_T_COUNTRY)
                ->Identifier("country_code")
                ->Name($langs->trans("CountryCode"))
                ->Group($GroupName)
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","addressCountry");
        
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
        // Active
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("status")
                ->Name($langs->trans("Active"))
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization","active")
                ->IsListed();        
        
        if ( Splash::Local()->DolVersionCmp("3.6.0") >= 0 ) {
            //====================================================================//
            // isProspect
            $this->FieldsFactory()->Create(SPL_T_BOOL)
                    ->Identifier("prospect")
                    ->Name($langs->trans("Prospect"))
                    ->Group("Meta")
                    ->MicroData("http://schema.org/Organization","prospect");        
        }

        //====================================================================//
        // isCustomer
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("client")
                ->Name($langs->trans("Customer"))
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization","customer");        

        //====================================================================//
        // isSupplier
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("fournisseur")
                ->Name($langs->trans("Supplier"))
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization","supplier");        

        
        //====================================================================//
        // isVAT
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("tva_assuj")
                ->Name($langs->trans("VATIsUsed"))
                ->Group("Meta")
                ->MicroData("http://schema.org/Organization","UseVAT");        
        
        
        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//        
        
        //====================================================================//
        // TMS - Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date_modification")
                ->Name($langs->trans("DateLastModification"))
                ->Group("Meta")
                ->MicroData("http://schema.org/DataFeedItem","dateModified")
                ->ReadOnly();
        
        //====================================================================//
        // datec - Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("date_creation")
                ->Name($langs->trans("DateCreation"))
                ->Group("Meta")
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
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
        // Read Company FullName => Firstname, Lastname - Compagny
        $fullname = $this->decodeFullName($this->Object->name);
        
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Fullname Readings
            case 'name':
            case 'firstname':
            case 'lastname':
                $this->Out[$FieldName] = $fullname[$FieldName];
                break;            
            
            //====================================================================//
            // Direct Readings
            case 'code_client':
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
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
                case 'phone':
                case 'email':
                case 'url':
                case 'idprof1':
                case 'idprof2':
                case 'idprof3':
                case 'idprof4':
                case 'idprof5':
                case 'idprof6':                    
                case 'tva_intra':
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
    private function getAddressFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
                case 'address':
                case 'zip':
                case 'town':
                case 'state':
                case 'state_code':
                case 'country':
                case 'country_code':
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
    private function getMetaFields($Key,$FieldName) {

        
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // STRUCTURAL INFORMATIONS
            //====================================================================//

            case 'status':
            case 'tva_assuj':
            case 'fournisseur':
                $this->getSingleBoolField($FieldName);
                break;                

            case 'client':
                $this->Out[$FieldName] = (bool) $this->Bitwise_Read($this->Object->client, 0);
                break;                

            case 'prospect':
                $this->Out[$FieldName] = (bool) $this->Bitwise_Read($this->Object->client, 1);
                break;                

            
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
        $this->Object = new Societe($db);
        $this->allowmodcodeclient = 0;
        
        //====================================================================//
        // If $id Given => Load Customer Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            if ( $this->Object->fetch($id) != 1 ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer (" . $id . ").");
            }
        }
        //====================================================================//
        // If NO $id Given  => Verify Input Data includes minimal valid values
        //                  => Setup Standard Parameters For New Customers
        //====================================================================//
        else
        {
            //====================================================================//
            // Check Customer Name is given
            if ( empty($this->In["name"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"name");
            }
            //====================================================================//
            // Dolibarr infos
            $this->Object->client             = 1;        // 0=no customer, 1=customer, 2=prospect
            $this->Object->prospect           = 0;        // 0=no prospect, 1=prospect
            $this->Object->fournisseur        = 0;        // 0=no supplier, 1=supplier
            $this->Object->code_client        = -1;       // If not erased, will be created by system
            $this->Object->code_fournisseur   = -1;       // If not erased, will be created by system
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
            // Fullname Writtings
            case 'name':
            case 'firstname':
            case 'lastname':
                $this->$FieldName = $Data;
                break;

            //====================================================================//
            // Direct Writtings
            case 'code_client':
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
            // Direct Writtings
                case 'phone':
                case 'email':
                case 'url':
                case 'idprof1':
                case 'idprof2':
                case 'idprof3':
                case 'idprof4':
                case 'idprof5':
                case 'idprof6':                    
                case 'tva_intra':
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
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setAddressFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
                case 'address':
                case 'zip':
                case 'town':
                    if ( $this->Object->$FieldName != $Data ) {
                        $this->Object->$FieldName = $Data;
                        $this->update = True;
                    }  
                    break;                    
                case 'country_code':
                    $country_id = Splash::Local()->getCountryByCode($Data);
                    if ( $this->Object->country_id != $country_id ) {
                        $this->Object->country_id = $country_id;
                        $this->update = True;
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
    private function setMetaFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
            case 'status':
            case 'tva_assuj':
            case 'fournisseur':
                if ( $this->Object->$FieldName != $Data ) {
                     $this->Object->$FieldName = $Data;
                     $this->update = True;
                 }  
                break; 
                
            case 'client':
                if ( $this->Bitwise_Read($this->Object->client, 0) != $Data ) {
                    $this->Object->client = $this->Bitwise_Write($this->Object->client, 0, $Data);                    
                    $this->update = True;
                }  
                break;                

            case 'prospect':
                if ( $this->Bitwise_Read($this->Object->client, 1) != $Data ) {
                    $this->Object->client = $this->Bitwise_Write($this->Object->client, 1, $Data);                    
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
        global $user,$langs,$user;

        //====================================================================//
        // Compute Changes on Customer Name
        $this->updateFullName();

        //====================================================================//
        // Verify Update Is requiered
        if ( $this->update == False ) {
            Splash::Log()->Deb("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return $this->Object->id;
        }
        
        //====================================================================//
        // LOAD USER FROM DATABASE
        Splash::Local()->LoadLocalUser();
        if ( empty($user->login) ) {
            return Splash::Log()->Err("ErrLocalUserMissing",__CLASS__,__FUNCTION__);
        }
        
//        Splash::Log()->www("Object",$this);
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        
        if (!empty($this->Object->id)) {
            if ( $this->Object->update($this->Object->id,$user,1,$this->allowmodcodeclient) <= 0) {  
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update ThirdParty. ");
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
            }
            
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"ThirdParty Updated");
            $this->update = False;
            return $this->Object->id;
        }
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//
        
        //====================================================================//
        // Create Object In Database
        if ( $this->Object->create($user) <= 0) {    
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new ThirdParty. ");
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
        }
        
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"ThirdParty Created");
        $this->update = False;
        return $this->Object->id; 
    }    
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//
    
    /**
    *   @abstract   Encode Full Name String using Firstname, Lastname & Compagny Name
    *   @param      string      $Firstname      Contact Firstname
    *   @param      string      $Lastname       Contact Lasttname
    *   @param      string      $Company        Contact Company
    *   @return     string                      Contact Full Name
    */    
    private static function encodeFullName($Firstname,$Lastname,$Company=Null)
    {
        //====================================================================//
        // Clean Input Data
        $FullName = preg_replace('/[^A-Za-z0-9\-]/', '', trim($Firstname));
        $last = preg_replace('/[^A-Za-z0-9\-]/', '', trim($Lastname));
        $comp = preg_replace('/[^A-Za-z0-9\-]/', '', trim($Company));
        //====================================================================//
        // Encode Full Name
        if ( !empty($last) ) {
            $FullName .= ", ".$last;
        }
        if ( !empty($comp) ) {
            $FullName .= " - ".$comp;
        }
        return $FullName;
    }   
    
    /**
    *   @abstract   Decode Firstname, Lastname & Compagny Name using Full Name String 
    *   @param      string      $FullName      Contact Full Name
    *   @return     array                      Contact Firstname, Lastname & Compagny Name
    */    
    private static function decodeFullName($FullName=Null)
    {
        //====================================================================//
        // Safety Checks 
        if ( empty($FullName) ) {
            return Null;
        }
        
        //====================================================================//
        // Init
        $result = array('name' => "", 'lastname' => "",'firstname' => ""  );
        
        //====================================================================//
        // Detect Single Company Name
        if ( (strpos($FullName,' - ') == FALSE) && (strpos($FullName,', ') == FALSE) ) {
            $result['name']  =   $FullName;
            return $result;
        }
        //====================================================================//
        // Detect Compagny Name
        if ( ( $pos = strpos($FullName,' - ') ) != FALSE)
        {
            $result['name']     =   substr($FullName,$pos + 3);
            $FullName           =   substr($FullName,0, $pos);
        }
        //====================================================================//
        // Detect Last Name
        if ( ( $pos = strpos($FullName,', ') ) != FALSE)
        {
            $result['lastname'] =   substr($FullName,$pos + 2);
            $FullName           =   substr($FullName,0, $pos);
        }
        $result['firstname']         =   $FullName;
        return $result;
    }      
    
    /**
    *   @abstract   Check FullName Array and update if needed 
    */    
    private function updateFullName() 
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__); 

        //====================================================================//
        // Get Current Values if Not Written
        $CurrentName = $this->decodeFullName($this->Object->name);
        if ( !isset($this->firstname) && !empty($CurrentName["firstname"])) {
            $this->firstname = $CurrentName["firstname"];
        }         
        if ( !isset($this->lastname) && !empty($CurrentName["lastname"])) {
            $this->lastname = $CurrentName["lastname"];
        }         
        if ( !isset($this->name) && !empty($CurrentName["name"])) {
            $this->name = $CurrentName["name"];
        }         
        
        //====================================================================//
        // No First or Last Name
        if (empty($this->firstname) && empty($this->lastname)) {
            if ( $this->Object->name !== $this->name ) {
                $this->Object->name = $this->name;
                $this->update   = True;
            }
            return True;
        } 

        //====================================================================//
        // Encode Full Name String
        $encodedFullName = $this->encodeFullName($this->firstname,$this->lastname,$this->name);
            
        //====================================================================//
        // Check if Updated
        if ( $this->Object->name === $encodedFullName ) {
            return False;
        }
        
        //====================================================================//
        // Update Name
        $this->Object->name = $encodedFullName;
        $this->update = True;   
        return True;
    }

}



?>
