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
        
use Splash\Client\Splash;
use Splash\Components\Logger;

/**
 *  @abstract      Classe des fonctions triggers des actions personalisees du workflow
 *
 *  @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class InterfaceSplash
{
    private $db;
    private $name;
    private $family;
    private $version;
    private $description;
    
    private $Id         =   null;
    private $Action     =   null;
    private $Type       =   null; 
    private $Login      =   "Unknown User";
    private $Comment    =   "Dolibarr Commit";
    
    
    //====================================================================//
    // Import Commit Triggers Action from Objects Namespaces
    //====================================================================//
    use \Splash\Local\Objects\ThirdParty\TriggersTrait;
    use \Splash\Local\Objects\Address\TriggersTrait;
    use \Splash\Local\Objects\Product\TriggersTrait;
    use \Splash\Local\Objects\Order\TriggersTrait;
    use \Splash\Local\Objects\Invoice\TriggersTrait;
    
    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    public function __construct($db)
    {
        global $langs;
        


        //====================================================================//
        // Class Init
        $this->db = $db ;
        $this->name         = preg_replace('/^Interface/i', '', get_class($this));
        $this->family       = "Modules";
        $this->description  = "Triggers of Splash module.";
        $this->version      = 'dolibarr';
        
        //====================================================================//
        // Load traductions files requiredby by page
        $langs->load("errors");        
    }
        
    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental') {
            return $langs->trans("Experimental");
        } elseif ($this->version == 'dolibarr') {
            return DOL_VERSION;
        } elseif ($this->version) {
            return $this->version;
        } else {
            return $langs->trans("Unknown");
        }
    }

    /**
     *  @abstract    Read all log messages posted by OsWs and post it on dolibarr
     *
     *  @param       Logger $log    Input Log Class
     * 
     *  @return      void
     */
    public function postMessages(Logger $log)
    {
        //====================================================================//
        // When Library is called in server mode, no Message Storage
        if (SPLASH_SERVER_MODE) {
            return;
        }

        if (!empty($log->msg)) {
            setEventMessage($log->GetHtml($log->msg), 'mesgs');
        }
        if (!empty($log->war)) {
            setEventMessage($log->GetHtml($log->war), 'warnings');
        }
        if (!empty($log->err)) {
            setEventMessage($log->GetHtml($log->err), 'errors');
        }
        if (!empty($log->deb)) {
            setEventMessage($log->GetHtml($log->deb), 'warnings');
        }
        
        if (isset($log)) {
            $log->CleanLog();
        }
    }

    
//    /**
//     *      @abstract      Prepare Object Commit for ProductCategorie
//     *
//     *      @param  string      $Action      Code de l'evenement
//     *      @param  object      $Object      Objet concerne
//     *
//     *      @return bool        Commit is required
//     */
//    function doProductCategorieCommit($Action, $Object)
//    {
//        if (    ($Action !== 'PRODUCT_CREATE')
//            &&  ($Action !== 'PRODUCT_MODIFY')
//            &&  ($Action !== 'PRODUCT_DELETE')
//            &&  ($Action !== 'PRODUCT_PRICE_MODIFY')
//            &&  ($Action !== 'PRODUCT_DELETE') )
//        {
//            return False;
//        }
//
//        //====================================================================//
//        // Commit Last Changes done On DataBase
//        $db->Commit();
//
//        //====================================================================//
//        // Store Global Action Parameters
//        $this->Type      = "Product";
//        $this->Id        = isset($Object->id)?$object->id:$object->product_id;
//
//        if ( $Action        == 'PRODUCT_CREATE')  {
//            $this->Action   = SPL_A_CREATE;
//            $this->Comment  = "Product Created on Dolibarr";
//        } else if ($Action  == 'PRODUCT_MODIFY') {
//            $this->Action   = SPL_A_UPDATE;
//            $this->Comment  = "Product Updated on Dolibarr";
//        } else if ($Action  == 'STOCK_MOVEMENT') {
//            $this->Action   = SPL_A_UPDATE;
//            $this->Comment  = "Product Stock Updated on Dolibarr";
//        } else if ($Action  == 'PRODUCT_PRICE_MODIFY') {
//            $this->Action   = SPL_A_UPDATE;
//            $_Comment       = "Product Price Updated on Dolibarr";
//        } else if ($Action  == 'PRODUCT_DELETE') {
//            $this->Action   = SPL_A_DELETE;
//            $this->Comment  = "Product Deleted on Dolibarr";
//        }
//
////        } elseif ( ($action == 'CATEGORY_CREATE') || ($action == 'CATEGORY_MODIFY')
////          || ($action == 'CATEGORY_DELETE') )
////        {
//            //====================================================================//
//            // Commit Last Changes done On DataBase
//            $db->Commit();
//            //====================================================================//
//            // Store Global Action Parameters
//            if ($object->type == '0' || $object->type == 'product') {
//                $_Type      = SPL_O_PRODCAT;
//                $_Comment   = "Product";
//            } else if ($type == '1' || $type == 'supplier') {
////                $_Type      = SPL_O_PRODCAT;
//                $_Comment   = "Supplier";
//            } else if ($type == '2' || $type == 'customer') {
////                $_Type      = SPL_O_PRODCAT;
//                $_Comment   = "Customer";
//            } else if ($type == '3' || $type == 'member') {
////                $_Type      = SPL_O_PRODCAT;
//                $_Comment   = "Member";
//            } else if ($type == '4' || $type == 'contact') {
////                $_Type      = SPL_O_PRODCAT;
//                $_Comment   = "Contact";
//            }
//            $_Id        = $object->id;
//
//            if ( $action == 'CATEGORY_CREATE')  {
//                $_Action    = OSWS_A_CREATE;
//                $_Comment  .= " Category Created on Dolibarr";
//            } else if ($action == 'CATEGORY_MODIFY') {
//                $_Action    = OSWS_A_UPDATE;
//                $_Comment  .= " Category Updated on Dolibarr";
//            } else if ($action == 'CATEGORY_DELETE') {
//                $_Action    = OSWS_A_DELETE;
//                $_Comment  .= " Category Deleted on Dolibarr";
//            }
//
//
//
//        return True;
//    }
    
        
    /**
     *      @abstract      Publish Object Change to Splash Sync Server
     *
     *      @return bool
     */
    protected function doSplashCommit()
    {
        //====================================================================//
        // Prevent Repeated Commit if Needed
        if (($this->Action == SPL_A_UPDATE) && Splash::object($this->Type)->isLocked()) {
            return;
        }

        //====================================================================//
        // Verify Id Before commit
        if ($this->Id > 0) {
            //====================================================================//
            // Commit Change to OsWs Module
            Splash::commit(
                $this->Type,                    // Object Type
                $this->Id,                      // Object Identifier (RowId ro Array of RowId)
                $this->Action,                  // Splash Action Type
                $this->Login,                   // Current User Login
                $this->Comment
            );                // Action Comment
            Splash::log()->deb("Change Commited (Action=" . $this->Comment . ") Object => ". $this->Type);
        } else {
            Splash::log()->war("Commit Id Missing (Action=" . $this->Comment . ") Object => ". $this->Type);
        }
        
        //====================================================================//
        //  Post User Messages
        $this->postMessages(Splash::log());
        
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
     *
     *  @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function run_trigger($action, $object, $user)
    {
        Splash::log()->deb("Start of Splash Module Trigger Actions (Action=" . $action . ")");
            
        //====================================================================//
        // Init Action Parameters
        $this->Type         = null;
        $this->Id           = null;
        $this->Action       = null;
        $this->Login        = ($user->login)?$user->login:"Unknown";
        $this->Comment      = null;
        
        $DoCommit           = false;
        
        //====================================================================//
        // TRIGGER ACTION FOR : ThirdParty
        //====================================================================//
        $DoCommit |= $this->doThirdPartyCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : Address / Contact
        //====================================================================//
        $DoCommit |= $this->doAddressCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : Products
        //====================================================================//
        $DoCommit |= $this->doProductCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : Categories
        //====================================================================//

        //====================================================================//
        // TRIGGER ACTION FOR : ORDER
        //====================================================================//
        $DoCommit |= $this->doOrderCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : INVOICE
        //====================================================================//
        $DoCommit |= $this->doInvoiceCommit($action, $object);

        //====================================================================//
        // Log Trigger Action
        Splash::log()->deb("Trigger for action '$action' launched by '".$this->Login."' for Object id=".$this->Id);
        
        //====================================================================//
        // No Action To Perform
        if (!$DoCommit) {
            //====================================================================//
            // Add Dolibarr Log Message
            dol_syslog(SPL_LOGPREFIX."End of Trigger for Action='".$action."'", LOG_DEBUG);
            return;
        }
        
        //====================================================================//
        // Commit change to Splash Server
        $this->doSplashCommit();
        
        //====================================================================//
        // Add Dolibarr Log Message
        dol_syslog(SPL_LOGPREFIX."End of Trigger for Action='".$action."'", LOG_DEBUG);
        return;
    }
}
