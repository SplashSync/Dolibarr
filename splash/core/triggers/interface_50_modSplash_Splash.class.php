<?php
/*
 * Copyright (C) 2011 Bernard Paquier       <bernard.paquier@gmail.com>
 * 
 * This program is Copyright (C) Protected.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY.
 *
 * 
 *  \Id 	$Id: interface_modOsconnect_Triggers.class.php 187 2012-09-02 11:38:17Z u58905340 $
 *  \version    $Revision: 187 $
 *  \ingroup    OsConnect Module For Dolibarr
 *  \brief      Fonctions Triggers pour le module Osconnect
 *  \remarks	
*/

//====================================================================//
// Splash Module Definitions
include_once(dirname(dirname(dirname(__FILE__))) ."/_conf/defines.inc.php");

global $langs; 

use Splash\Client\Splash;
use Splash\Components\Logger;

//====================================================================//
// Load traductions files requiredby by page
$langs->load("errors");
$langs->load("products");

//====================================================================//
// Classes Dolibarr
require_once(DOL_DOCUMENT_ROOT ."/product/class/product.class.php");

/**
 *      \class      InterfaceSplash
 *      \brief      Classe des fonctions triggers des actions personalisees du workflow
 */
class InterfaceSplash
{
    var $db;
    
    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceSplash($DB)
    {
        $this->db = $DB ;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "Modules";
        $this->description = "Triggers of Splash module.";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
    }
    
    
    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
    *  @abstract    Read all log messages posted by OsWs and post it on dolibarr
    *
    *  @param       SpashLog    			Input Log Class
    *  @return      None
    */
    function PostMessages(Logger $log)
    {
        //====================================================================//
        // When Library is called in server mode, no Message Storage
        if ( SPLASH_SERVER_MODE ) { return; }    

        if ( !empty ($log->msg ) ) 	{	
            setEventMessage($log->GetHtml($log->msg),'mesgs'); 
        } 
        if ( !empty ($log->war ) ) 	{	
            setEventMessage($log->GetHtml($log->war),'warnings');  
        } 
        if ( !empty ($log->err ) ) 	{	
            setEventMessage($log->GetHtml($log->err),'errors');  
        } 
        if ( !empty ($log->deb ) ) 	{	
            setEventMessage($log->GetHtml($log->deb),'warnings');  
        } 
        
        if ( isset ($log ) )            {       $log->CleanLog(); }
    }

    /**
     *      @abstract      Prepare Object Commit for ThirdParty
     * 
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     * 
     *      @return bool        Commit is required
     */
    function ThirdPartyCommit($Action, $Object)
    {    
        global $db;

        if (    ($Action !== 'COMPANY_CREATE') 
            &&  ($Action !== 'COMPANY_MODIFY')
            &&  ($Action !== 'COMPANY_DELETE') ) 
        {
            return False;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters 
        $this->Type      = "ThirdParty";
        $this->Id        = $Object->id;
        
        if ( $Action == 'COMPANY_CREATE')  {  
            $this->Action    = SPL_A_CREATE;
            $this->Comment   = "Company Created on Dolibarr";
        } else if ($Action == 'COMPANY_MODIFY') {
            $this->Action    = SPL_A_UPDATE;
            $this->Comment   = "Company Updated on Dolibarr";
        } else if ($Action == 'COMPANY_DELETE') {
            $this->Action    = SPL_A_DELETE;
            $this->Comment   = "Company Deleted on Dolibarr";
        }        
        
        return True;
    }   
    
    /**
     *      @abstract      Prepare Object Commit for Address
     * 
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     * 
     *      @return bool        Commit is required
     */
    function AddressCommit($Action, $Object)
    {    
        global $db;
        
        if (    ($Action !== 'CONTACT_CREATE') 
            &&  ($Action !== 'CONTACT_MODIFY')
            &&  ($Action !== 'CONTACT_DELETE') ) 
        {
            return False;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters 
        $this->Type      = "Address";
        $this->Id        = $Object->id;
        
        if ( $Action        == 'CONTACT_CREATE')  {  
            $this->Action   = SPL_A_CREATE;
            $this->Comment  = "Contact Created on Dolibarr";
        } else if ($Action  == 'CONTACT_MODIFY') {
            $this->Action   = SPL_A_UPDATE;
            $this->Comment  = "Contact Updated on Dolibarr";
        } else if ($Action  == 'CONTACT_DELETE') {
            $this->Action   = SPL_A_DELETE;
            $this->Comment  = "Contact Deleted on Dolibarr";
        }     
        
        return True;
    }       
    
    /**
     *      @abstract      Prepare Object Commit for Product
     * 
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     * 
     *      @return bool        Commit is required
     */
    function ProductCommit($Action, $Object)
    {    
        global $db;
        
        //====================================================================//
        // Check if object if in Remote Create Mode 
        $isLockedForCreation    =    Splash::Object("Product")->isLocked();
        
        //====================================================================//
        // Filter Triggered Actions 
        if (    ($Action !== 'PRODUCT_CREATE') 
            &&  ($Action !== 'PRODUCT_MODIFY')
            &&  ($Action !== 'PRODUCT_DELETE')
            //====================================================================//
            // Since Dol 3.9 Was introduced Trigger On setMultiLang
            // We need to filter this trigger when Product Remotly Created                
            &&  ($Action !== 'PRODUCT_SET_MULTILANGS')
            &&  ($Action !== 'PRODUCT_PRICE_MODIFY')
            &&  ($Action !== 'STOCK_MOVEMENT') ) 
        {
            return False;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters 
        $this->Type     = "Product";
        if ( is_a($Object, "Product") ) {
            $this->Id   = $Object->id;
        } elseif ( is_a($Object, "MouvementStock") ) {
            $this->Id   = $Object->product_id;
        }
        
        if ( $Action        == 'PRODUCT_CREATE')  {  
            $this->Action       = SPL_A_CREATE;
            $this->Comment      = "Product Created on Dolibarr";
        } else if ($Action  == 'PRODUCT_MODIFY') {
            $this->Action       = SPL_A_UPDATE;
            $this->Comment      = "Product Updated on Dolibarr";
        } else if ($Action  == 'PRODUCT_SET_MULTILANGS') {
            $this->Action       = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->Comment      = "Product Description Updated on Dolibarr";
        } else if ($Action  == 'STOCK_MOVEMENT') {
            $this->Action       = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->Comment      = "Product Stock Updated on Dolibarr";            
        } else if ($Action  == 'PRODUCT_PRICE_MODIFY') {
            $this->Action       = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->Comment  = "Product Price Updated on Dolibarr";               
        } else if ($Action  == 'PRODUCT_DELETE') {
            $this->Action       = SPL_A_DELETE;
            $this->Comment      = "Product Deleted on Dolibarr";
        }        
        
        return True;
    }   
    
    /**
     *      @abstract      Prepare Object Commit for ProductCategorie
     * 
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     * 
     *      @return bool        Commit is required
     */
    function ProductCategorieCommit($Action, $Object)
    {    
        if (    ($Action !== 'PRODUCT_CREATE') 
            &&  ($Action !== 'PRODUCT_MODIFY')
            &&  ($Action !== 'PRODUCT_DELETE')
            &&  ($Action !== 'PRODUCT_PRICE_MODIFY')
            &&  ($Action !== 'PRODUCT_DELETE') ) 
        {
            return False;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters 
        $this->Type      = "Product";
        $this->Id        = isset($Object->id)?$object->id:$object->product_id;
        
        if ( $Action        == 'PRODUCT_CREATE')  {  
            $this->Action   = SPL_A_CREATE;
            $this->Comment  = "Product Created on Dolibarr";
        } else if ($Action  == 'PRODUCT_MODIFY') {
            $this->Action   = SPL_A_UPDATE;
            $this->Comment  = "Product Updated on Dolibarr";
        } else if ($Action  == 'STOCK_MOVEMENT') {
            $this->Action   = SPL_A_UPDATE;
            $this->Comment  = "Product Stock Updated on Dolibarr";            
        } else if ($Action  == 'PRODUCT_PRICE_MODIFY') {
            $this->Action   = SPL_A_UPDATE;
            $_Comment       = "Product Price Updated on Dolibarr";               
        } else if ($Action  == 'PRODUCT_DELETE') {
            $this->Action   = SPL_A_DELETE;
            $this->Comment  = "Product Deleted on Dolibarr";
        }        
        
//        } elseif ( ($action == 'CATEGORY_CREATE') || ($action == 'CATEGORY_MODIFY')
//          || ($action == 'CATEGORY_DELETE') ) 
//        {
            //====================================================================//
            // Commit Last Changes done On DataBase
            $db->Commit();
            //====================================================================//
            // Store Global Action Parameters 
            if ($object->type == '0' || $object->type == 'product') { 
                $_Type      = SPL_O_PRODCAT;
                $_Comment   = "Product";
            } else if ($type == '1' || $type == 'supplier') { 
//                $_Type      = SPL_O_PRODCAT;
                $_Comment   = "Supplier";
            } else if ($type == '2' || $type == 'customer') { 
//                $_Type      = SPL_O_PRODCAT;
                $_Comment   = "Customer";
            } else if ($type == '3' || $type == 'member') { 
//                $_Type      = SPL_O_PRODCAT;
                $_Comment   = "Member";
            } else if ($type == '4' || $type == 'contact') { 
//                $_Type      = SPL_O_PRODCAT;
                $_Comment   = "Contact";
            }             
            $_Id        = $object->id;
            
            if ( $action == 'CATEGORY_CREATE')  {  
                $_Action    = OSWS_A_CREATE;
                $_Comment  .= " Category Created on Dolibarr";
            } else if ($action == 'CATEGORY_MODIFY') {
                $_Action    = OSWS_A_UPDATE;
                $_Comment  .= " Category Updated on Dolibarr";
            } else if ($action == 'CATEGORY_DELETE') {
                $_Action    = OSWS_A_DELETE;
                $_Comment  .= " Category Deleted on Dolibarr";
            }
        
        
        
        return True;
    }       
    
    /**
     *      @abstract      Prepare Object Commit for Order
     * 
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     * 
     *      @return bool        Commit is required
     */
    function OrderCommit($Action, $Object)
    {    
        global $db;

        //====================================================================//
        // Check if object if in Remote Create Mode 
        $isLockedForCreation    =    Splash::Object("Order")->isLocked();
        
        //====================================================================//
        // Filter Triggered Actions 
        if (    ($Action !== 'ORDER_CREATE') 
            &&  ($Action !== 'ORDER_VALIDATE')
            &&  ($Action !== 'ORDER_MODIFY')
            &&  ($Action !== 'ORDER_UPDATE')
            &&  ($Action !== 'ORDER_DELETE')
            &&  ($Action !== 'LINEORDER_INSERT')
            &&  ($Action !== 'LINEORDER_UPDATE')
            &&  ($Action !== 'LINEORDER_DELETE')
            &&  ($Action !== 'COMMANDE_ADD_CONTACT')
            &&  ($Action !== 'COMMANDE_DELETE_CONTACT')
            &&  ($Action !== 'ORDER_CLOSE')
            &&  ($Action !== 'ORDER_REOPEN')
            &&  ($Action !== 'ORDER_CLASSIFY_BILLED')
            &&  ($Action !== 'ORDER_CANCEL') ) 
        {
            return False;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters 
        $this->Type      = "Order";
        if (is_a($Object, "OrderLine")) {
            if ($Object->fk_commande) {
                $this->Id        = $Object->fk_commande;
            } else {
                $this->Id        = $Object->oldline->fk_commande;
            }
        } else {
            $this->Id        = $Object->id;
        } 
        
        if ( $Action        == 'ORDER_CREATE')  {  
            $this->Action       = SPL_A_CREATE;
            $this->Comment      = "Order Created on Dolibarr";
        } elseif (    ($Action == 'ORDER_VALIDATE') 
            ||  ($Action == 'ORDER_MODIFY')
            ||  ($Action == 'ORDER_UPDATE')
            ||  ($Action == 'LINEORDER_INSERT')
            ||  ($Action == 'LINEORDER_UPDATE')
            ||  ($Action == 'LINEORDER_DELETE')
            ||  ($Action == 'COMMANDE_ADD_CONTACT')
            ||  ($Action == 'COMMANDE_DELETE_CONTACT')                
            ||  ($Action == 'ORDER_CLOSE')
            ||  ($Action == 'ORDER_REOPEN')
            ||  ($Action == 'ORDER_CLASSIFY_BILLED')
            ||  ($Action == 'ORDER_CANCEL') ) 
        {
            $this->Action       = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->Comment      = "Order Updated on Dolibarr";
        } else if ($Action  == 'ORDER_DELETE') {
            $this->Action       = SPL_A_DELETE;
            $this->Comment      = "Order Deleted on Dolibarr";
        }         
        return True;
    }  
    
    /**
     *      @abstract      Prepare Object Commit for Order
     * 
     *      @param  string      $Action      Code de l'evenement
     *      @param  object      $Object      Objet concerne
     * 
     *      @return bool        Commit is required
     */
    function InvoiceCommit($Action, $Object)
    {    
        global $db;

        //====================================================================//
        // Check if object if in Remote Create Mode 
        $isLockedForCreation    =    Splash::Object("Invoice")->isLocked();
        
        //====================================================================//
        // Filter Triggered Actions 
        if (    ($Action !== 'BILL_CREATE') 
            &&  ($Action !== 'BILL_CLONE')
            &&  ($Action !== 'BILL_MODIFY')
            &&  ($Action !== 'BILL_VALIDATE')
            &&  ($Action !== 'BILL_UNVALIDATE')
            &&  ($Action !== 'BILL_CANCEL')
            &&  ($Action !== 'BILL_DELETE')
            &&  ($Action !== 'BILL_PAYED')
            &&  ($Action !== 'BILL_UNPAYED')
            &&  ($Action !== 'PAYMENT_CUSTOMER_CREATE')
            &&  ($Action !== 'PAYMENT_CUSTOMER_DELETE')
// Not Managed up to now. User Select Default Bank for payments created by the module
//            &&  ($Action !== 'PAYMENT_ADD_TO_BANK')             
            &&  ($Action !== 'PAYMENT_DELETE')
            &&  ($Action !== 'LINEBILL_INSERT')
            &&  ($Action !== 'LINEBILL_UPDATE')
            &&  ($Action !== 'LINEBILL_DELETE') ) 
        {
            return False;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();

        //====================================================================//
        // Store Global Action Parameters 
        $this->Type      = "Invoice";
        if (is_a($Object, "FactureLigne")) {
            if ($Object->fk_facture) {
                $this->Id        = $Object->fk_facture;
            } else {
                $this->Id        = $Object->oldline->fk_facture;
            }
        } elseif (is_a($Object, "Paiement")) {
            //====================================================================//
            // Read Paiement Object Invoices Amounts 
            $Amounts = Splash::Object("Invoice")->getPaiementAmounts($Object->id);
            //====================================================================//
            // Create Impacted Invoices Ids Array  
            $this->Id        = array();
            foreach ($Amounts as $InvoiceId => $Amount)
            {            
                $this->Id[]        = $InvoiceId;
            }
        } else {
            $this->Id        = $Object->id;
        } 
        
        if ( empty($this->Id) ) {
            return False;
        } 
        
        if ( $Action        == 'BILL_CREATE')  {  
            $this->Action       = SPL_A_CREATE;
            $this->Comment      = "Invoice Created on Dolibarr";
        } elseif ( ($Action == 'BILL_MODIFY') 
            ||  ($Action == 'BILL_CLONE')
            ||  ($Action == 'BILL_VALIDATE')
            ||  ($Action == 'BILL_UNVALIDATE')
            ||  ($Action == 'BILL_CANCEL')
            ||  ($Action == 'BILL_PAYED')
            ||  ($Action == 'BILL_UNPAYED')
            ||  ($Action == 'PAYMENT_CUSTOMER_CREATE')
            ||  ($Action == 'PAYMENT_CUSTOMER_DELETE')
            ||  ($Action == 'PAYMENT_DELETE')                
            ||  ($Action == 'LINEBILL_INSERT')
            ||  ($Action == 'LINEBILL_UPDATE')
            ||  ($Action == 'LINEBILL_DELETE') ) 
        {
            $this->Action       = ($isLockedForCreation ?   SPL_A_CREATE : SPL_A_UPDATE);
            $this->Comment      = "Invoice Updated on Dolibarr";
        } else if ($Action  == 'BILL_DELETE') {
            $this->Action       = SPL_A_DELETE;
            $this->Comment      = "Invoice Deleted on Dolibarr";
        }       
        //====================================================================//
        // Commit Last Changes done On DataBase
//        $db->Commit();
        return True;
    }  
        
    /**
     *      @abstract      Publish Object Change to Splash Sync Server
     * 
     *      @return bool 
     */
    function Commit()
    {    
        //====================================================================//
        // Prevent Repeated Commit if Needed
        if ( ($this->Action == SPL_A_UPDATE) && Splash::Object($this->Type)->isLocked() ) {
            return;
        }

        //====================================================================//
        // Verify Id Before commit
        if ($this->Id > 0 ) {
            //====================================================================//
            // Commit Change to OsWs Module
            Splash::Commit(
                    $this->Type,                    // Object Type
                    $this->Id,                      // Object Identifier (RowId ro Array of RowId)
                    $this->Action,                  // Splash Action Type
                    $this->Login,                   // Current User Login
                    $this->Comment);                // Action Comment
            Splash::Log()->Deb("Change Commited (Action=" . $this->Comment . ") Object => ". $this->Type);
        } else {
            Splash::Log()->War("Commit Id Missing (Action=" . $this->Comment . ") Object => ". $this->Type);
        }
        
        //====================================================================//
        //  Post User Messages
        $this->PostMessages(Splash::Log());
        
        return;
    
        
    }
    /**
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 if fatal error, 0 si nothing done, >0 if ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {
        Splash::Log()->Deb("Start of Splash Module Trigger Actions (Action=" . $action . ")");
            
        //====================================================================//
        // Init Action Parameters 
        $this->Type         = Null;
        $this->Id           = Null;
        $this->Action       = Null;
        $this->Login        = ($user->login)?$user->login:"Unknown";
        $this->Comment      = Null;
        
        $DoCommit           = False;
        
        //====================================================================//
        // TRIGGER ACTION FOR : ThirdParty
        //====================================================================//
        $DoCommit |= $this->ThirdPartyCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : Address / Contact
        //====================================================================//
        $DoCommit |= $this->AddressCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : Products
        //====================================================================//
        $DoCommit |= $this->ProductCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : Categories
        //====================================================================//

        //====================================================================//
        // TRIGGER ACTION FOR : ORDER
        //====================================================================//
        $DoCommit |= $this->OrderCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : INVOICE
        //====================================================================//
        $DoCommit |= $this->InvoiceCommit($action, $object);

        //====================================================================//
        // Log Trigger Action
        Splash::Log()->Deb("Trigger for action '$action' launched by '".$this->Login."' for Object id=".$this->Id);
        
        //====================================================================//
        // No Action To Perform
        if ( !$DoCommit ) 
        {
            //====================================================================//
            // Add Dolibarr Log Message
            dol_syslog(SPL_LOGPREFIX."End of Trigger for Action='".$action."'",LOG_DEBUG);
            return;
        }
        
        //====================================================================//
        // Commit change to Splash Server
        $this->Commit();
        
        //====================================================================//
        // Add Dolibarr Log Message
        dol_syslog(SPL_LOGPREFIX."End of Trigger for Action='".$action."'",LOG_DEBUG);
        return;
    }
        
}    