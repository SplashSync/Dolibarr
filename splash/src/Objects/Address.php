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
//               CONTACTS / ADDRESS DATA MANAGEMENT                   //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Objects;

use Splash\Models\ObjectBase;
use Splash\Core\SplashCore      as Splash;
use Contact;

/**
 *	\class      Address
 *	\brief      Address - Thirdparty Contacts Management Class
 */
class Address extends ObjectBase
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
    protected static    $NAME            =  "Contact Address";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Dolibarr Contact Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-envelope-o";
    
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
        
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
        
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
        $sql   .= " c.rowid as id,";                    // Object Id         
        $sql   .= " c.ref_ext as ref_ext,";             // Reference
        $sql   .= " c.firstname as firstname,";         // FirstName 
        $sql   .= " c.lastname as lastname,";           // LastName 
        $sql   .= " c.phone as phone_pro,";             // Professionnal Phone
        $sql   .= " c.phone_mobile as phone_mobile,";   // Mobile Phone
        $sql   .= " c.email as email,";                 // Email
        $sql   .= " c.zip as zip,";                     // ZipCode
        $sql   .= " c.town as town,";                   // City Name
        if (Splash::Local()->DolVersionCmp("3.7.0") >= 0) {
            $sql   .= " p.label as country,";          // Country Name
        } else {
            $sql   .= " p.libelle as country,";        // Country Name
        }          
        $sql   .= " c.statut as status,";              // Active
        $sql   .= " c.tms as modified";                // last modified date
        //====================================================================//
        // Select Database tables
        $sql   .= " FROM " . MAIN_DB_PREFIX . "socpeople as c ";
        
        if (Splash::Local()->DolVersionCmp("3.7.0") >= 0) {
            $sql   .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as p on c.fk_pays = p.rowid"; 
        } else {
            $sql   .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_pays as p on c.fk_pays = p.rowid"; 
        }        
        
        //====================================================================//
        // Setup filters
        //====================================================================//
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in External Ref
            $sql   .= " WHERE LOWER( c.ref_ext ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in FirstName
            $sql   .= " OR LOWER( c.firstname ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in LastName
            $sql   .= " OR LOWER( c.lastname ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Phone
            $sql   .= " OR LOWER( c.phone ) LIKE LOWER( '%" . $filter ."%') ";
            $sql   .= " OR LOWER( c.phone_mobile ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Email
            $sql   .= " OR LOWER( c.email ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Zip
            $sql   .= " OR LOWER( c.zip ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search in Town
            $sql   .= " OR LOWER( c.town ) LIKE LOWER( '%" . $filter ."%') ";
        }  
        
        //====================================================================//
        // Setup sortorder
        //====================================================================//
        $sortfield = empty($params["sortfield"])?"c.rowid":$params["sortfield"];
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
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__, " " . $i . " Contact Found.");
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
        // Load Required Translation Files
        $langs->load("dict");        
        
        //====================================================================//
        // Init Reading
        $this->In = $list;
        
        //====================================================================//
        // Init Object 
        $this->Object = new Contact($db);
        if ( $this->Object->fetch($id) != 1 )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Contact (" . $id . ").");
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
        // Create Object
        $Object = new Contact($db);
        
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
        if ( $Object->delete() <= 0) {  
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
                ->Identifier("ref_ext")
                ->Name($langs->trans("CustomerCode"))
                ->Description($langs->trans("CustomerCodeDesc"))
                ->IsListed()
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","name");
        
//        //====================================================================//
//        // Company
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
//                ->Identifier("name")
//                ->Name($langs->trans("CompanyName"))
//                ->Description($langs->trans("CompanyName"))
//                ->MicroData("http://schema.org/Organization","legalName")       // 8f0ca290d33f34b64658814bd2642d60
//                ->IsListed();

        //====================================================================//
        // Firstname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name($langs->trans("Firstname"))
                ->MicroData("http://schema.org/Person","familyName")
                ->IsListed()
                ->isLogged()
                ->isRequired();
        
        //====================================================================//
        // Lastname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name($langs->trans("Lastname"))
                ->MicroData("http://schema.org/Person","givenName")        
                ->isLogged()
                ->IsListed();
                
        //====================================================================//
        // Customer
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "ThirdParty" , SPL_T_ID))
                ->Identifier("socid")
                ->Name($langs->trans("Company"))
                ->MicroData("http://schema.org/Organization","ID");        
        
    }    

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        global $conf,$langs;
        
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
                ->MicroData("http://schema.org/PostalAddress","postalCode")
                ->Group($GroupName)
                ->isLogged()
                ->isListed();
        
        //====================================================================//
        // City Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("town")
                ->Name($langs->trans("CompanyTown"))
                ->MicroData("http://schema.org/PostalAddress","addressLocality")
                ->Group($GroupName)
                ->isLogged()
                ->isListed();
        
        //====================================================================//
        // Country Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("country")
                ->Name($langs->trans("CompanyCountry"))
                ->ReadOnly()
                ->Group($GroupName)
                ->isListed();
        
        //====================================================================//
        // Country ISO Code
        $this->FieldsFactory()->Create(SPL_T_COUNTRY)
                ->Identifier("country_code")
                ->Name($langs->trans("CountryCode"))
                ->Group($GroupName)
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","addressCountry");

        if (empty($conf->global->SOCIETE_DISABLE_STATE))
        {        
            //====================================================================//
            // State Name
            $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                    ->Identifier("state")
                    ->Name($langs->trans("State"))
                    ->Group($GroupName)
                    ->ReadOnly();

            //====================================================================//
            // State code
            $this->FieldsFactory()->Create(SPL_T_STATE)
                    ->Identifier("state_code")
                    ->Name($langs->trans("State Code"))
                    ->MicroData("http://schema.org/PostalAddress","addressRegion")
                    ->Group($GroupName)
                    ->isLogged()
                    ->NotTested();
        }

        //====================================================================//
        // Phone Pro
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_pro")
                ->Name($langs->trans("PhonePro"))
                ->MicroData("http://schema.org/Organization","telephone")
                ->isLogged()
                ->isListed();

        //====================================================================//
        // Phone Perso
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_perso")
                ->Name($langs->trans("PhonePerso"))
                ->isLogged()
                ->MicroData("http://schema.org/PostalAddress","telephone");
        
        //====================================================================//
        // Mobile Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_mobile")
                ->Name($langs->trans("PhoneMobile"))
                ->MicroData("http://schema.org/Person","telephone")
                ->isLogged()
                ->isListed();

        //====================================================================//
        // Email
        $this->FieldsFactory()->Create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name($langs->trans("Email"))
                ->MicroData("http://schema.org/ContactPoint","email")
                ->isLogged()
                ->isListed();  
        
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
        // Active
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("statut")
                ->Name($langs->trans("Active"))
                ->MicroData("http://schema.org/Person","active");        
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
            // Contact ThirdParty Id 
            case 'socid':
                $this->Out[$FieldName] = self::ObjectId_Encode( "ThirdParty" , $this->Object->$FieldName);
                break;
            
            //====================================================================//
            // Direct Readings
            case 'name':
            case 'firstname':
            case 'lastname':
            case 'ref_ext':
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
            case 'address':
            case 'zip':
            case 'town':
            case 'state':
            case 'state_code':
            case 'country':
            case 'country_code':
            case 'phone_pro':
            case 'phone_perso':
            case 'phone_mobile':
            case 'email':
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

            case 'statut':
                $this->getSingleBoolField($FieldName);
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
        $this->Object = new Contact($db);
        
        //====================================================================//
        // If $id Given => Load Customer Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            if ( $this->Object->fetch($id) != 1 ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Contact (" . $id . ").");
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
            if ( empty($this->In["firstname"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"firstname");
            }
            //====================================================================//
            // Pre-Setup of Dolibarr infos
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
            // Contact Company Id 
            case 'socid':
                $SocId = self::ObjectId_DecodeId( $Data );
                $this->setSingleField($FieldName,$SocId);
                break;       

            //====================================================================//
            // Direct Writtings
            case 'name':
            case 'firstname':
            case 'lastname':
                $this->setSingleField($FieldName,$Data);
                break;       
                
            case 'ref_ext':
                if ( $this->Object->$FieldName != $Data ) {
                    $this->ref_ext = $Data;
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
            case 'address':
            case 'zip':
            case 'town':
            case 'state_code':
            case 'phone_pro':
            case 'phone_perso':
            case 'phone_mobile':
            case 'email':           
                $this->setSingleField($FieldName,$Data);
                break;                    
            case 'state_code':
                $StateId = Splash::Local()->getStateByCode($Data,$this->Object->country_id);
                $this->setSingleField("state_id",$StateId);
                break;                    
            case 'country_code':
                $CountryId = Splash::Local()->getCountryByCode($Data);
                $this->setSingleField("country_id",$CountryId);
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
            case 'statut':
                $this->setSingleField($FieldName,$Data);
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
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Contact. ");
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
            }
            
            //====================================================================//
            // Update Ref_Ext if Modified
            if ( isset(  $this->ref_ext ) ) {
                Splash::Local()->setSingleField(
                    $this->Object->table_element,
                    $this->Object->id,
                    "ref_ext",$this->ref_ext);
            }  
                                
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Contact Updated");
            $this->update = False;
            return $this->Object->id;
        }
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//
        
        //====================================================================//
        // Create Object In Database
        if ( $this->Object->create($user) <= 0) {    
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new Contact. ");
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
        }

        //====================================================================//
        // Set Ref_Ext if Modified
        if ( isset(  $this->ref_ext ) ) {
            Splash::Local()->setSingleField(
                $this->Object->table_element,
                $this->Object->id,
                "ref_ext",$this->ref_ext);
        }  
        
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Contact Created");
        $this->update = False;
        return $this->Object->id; 
    }    
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

}



?>
