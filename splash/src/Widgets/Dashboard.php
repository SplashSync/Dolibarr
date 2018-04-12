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
 *  \ingroup    Splash - Open Synchronisation WebService
 *  \brief      Local Function Definition for Management of Customers Data
 *  \class      SplashDemo
 *  \remarks	Designed for Splash Module - Dolibar ERP Version
*/
                    
//====================================================================//
// *******************************************************************//
//                     SPLASH FOR DOLIBARR                            //
// *******************************************************************//
//                  BANK ACCOUNTS LEVELS WIDGET                       //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Widgets;

use Splash\Models\WidgetBase;
use Splash\Core\SplashCore      as Splash;

class Dashboard extends WidgetBase
{
    
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Widget Name (Translated by Module)
     */
    protected static $NAME            =  "DolibarrWorkBoard";
    
    /**
     *  Widget Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "DolibarrWorkBoard";
    
    /**
     *  Widget Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO            =  "fa fa-briefcase";
    
    //====================================================================//
    // Define Standard Options for this Widget
    // Override this array to change default options for your widget
    public static $OPTIONS       = array(
        "Width"         =>  self::SIZE_SM,
        "Header"        =>  true,
        "Footer"        =>  true,
        'UseCache'      =>  true,
        'CacheLifeTime' =>  60,
    );
    
    //====================================================================//
    // General Class Variables
    //====================================================================//

    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    public function __construct()
    {
        //====================================================================//
        // Load Default Language
        Splash::local()->LoadDefaultLanguage();
    }
    
    /**
     *  @abstract     Return requested Customer Data
     *
     *  @param        array   $params               Search parameters for result List.
     *                        $params["start"]      Maximum Number of results
     *                        $params["end"]        List Start Offset
     *                        $params["groupby"]    Field name for sort list (Available fields listed below)

     */
    public function get()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Default Language
        Splash::local()->LoadDefaultLanguage();

        //====================================================================//
        // Setup Widget Core Informations
        //====================================================================//

        $this->setTitle($this->getName());
        $this->setIcon($this->getIcon());
        
        //====================================================================//
        // Build Data Blocks
        //====================================================================//
        $this->buildTableBlock();
        
        //====================================================================//
        // Set Blocks to Widget
        $this->setBlocks($this->blocksFactory()->Render());

        //====================================================================//
        // Publish Widget
        return $this->Render();
    }
        

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    /**
     * @abstract    Read Widget Datas
     */
    private function getData()
    {

        //Array that contains all WorkboardResponse classes to process them
        $dashboardlines=array();

        require DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

        $this->getLateActions($dashboardlines);
        $this->getCustomerOrders($dashboardlines);
        $this->getSupplierOrders($dashboardlines);
        $this->getOpenPropals($dashboardlines);
        $this->getDelayedServices($dashboardlines);
        $this->getCustomersInvoices($dashboardlines);
        $this->getSupplierInvoices($dashboardlines);
        $this->getTransactionsDashboard($dashboardlines);
        $this->getBankWire($dashboardlines);
        $this->getMembers($dashboardlines);
        $this->getExpenesDashboard($dashboardlines);
        
        return $dashboardlines;
    }
    
    /**
     * @abstract    Read Late Actions Dashboard
     */
    private function getLateActions(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of actions to do (late)
        if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->read) {
            include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
            $board=new \ActionComm($db);

            $dashboardlines[] = $board->load_board($user);
        }
    }

    /**
     * @abstract    Read Customers Orders Dashboard
     */
    private function getCustomerOrders(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of customer orders a deal
        if (! empty($conf->commande->enabled) && $user->rights->commande->lire) {
            include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
            $board=new \Commande($db);

                $dashboardlines[] = $board->load_board($user);
        }
    }
    
    /**
     * @abstract    Read Suppliers Orders Dashboard
     */
    private function getSupplierOrders(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of suppliers orders a deal
        if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire) {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
            $board=new \CommandeFournisseur($db);

                $dashboardlines[] = $board->load_board($user);
        }
    }
    /**
     * @abstract    Read Suppliers Orders Dashboard
     */
    private function getOpenPropals(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of commercial proposals opened (expired)
        if (! empty($conf->propal->enabled) && $user->rights->propale->lire) {
            include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
            $board=new \Propal($db);
                $dashboardlines[] = $board->load_board($user, "opened");

                // Number of commercial proposals CLOSED signed (billed)
                $dashboardlines[] = $board->load_board($user, "signed");
        }
    }
    
    /**
     * @abstract    Read Suppliers Orders Dashboard
     */
    private function getDelayedServices(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of services enabled (delayed)
        if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire) {
            include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
            $board=new \Contrat($db);
            $dashboardlines[] = $board->load_board($user, "inactives");

                // Number of active services (expired)
            $dashboardlines[] = $board->load_board($user, "expired");
        }
    }
    
    /**
     * @abstract    Read Customers Invoices Dashboard
     */
    private function getCustomersInvoices(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of invoices customers (has paid)
        if (! empty($conf->facture->enabled) && $user->rights->facture->lire) {
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $board=new \Facture($db);
            $dashboardlines[] = $board->load_board($user);
        }
    }
  
    /**
     * @abstract    Read Supplier Invoices Dashboard
     */
    private function getSupplierInvoices(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of supplier invoices (has paid)
        if (! empty($conf->fournisseur->enabled) && ! empty($conf->facture->enabled) && $user->rights->facture->lire) {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
            $board=new \FactureFournisseur($db);
            $dashboardlines[] = $board->load_board($user);
        }
    }
    
    /**
     * @abstract    Read Transactions Dashboard
     */
    private function getTransactionsDashboard(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of transactions to conciliate
        if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id) {
            include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
            $board=new \Account($db);
            $count = $board::countAccountToReconcile();
            if ($count > 0) {
                $dashboardlines[] = $board->load_board($user);
            }
        }
    }
    
    /**
     * @abstract    Read Bank Wire Dashboard
     */
    private function getBankWire(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of cheque to send
        if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id) {
            include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
            $board=new \RemiseCheque($db);
            $dashboardlines[] = $board->load_board($user);
        }
    }
    
    /**
     * @abstract    Read Bank Wire Dashboard
     */
    private function getMembers(&$dashboardlines)
    {

        global $user, $db, $conf;

        // Number of foundation members
        if (! empty($conf->adherent->enabled) && $user->rights->adherent->lire && ! $user->societe_id) {
            include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
            $board=new \Adherent($db);
            $dashboardlines[] = $board->load_board($user);
        }
    }
    
    /**
     * @abstract    Read Expenses Dashboard
     */
    private function getExpenesDashboard(&$dashboardlines)
    {

        global $user, $db, $conf;
        
        // Number of expense reports to pay
        if (! empty($conf->expensereport->enabled) && $user->rights->expensereport->lire) {
            include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
            $board=new \ExpenseReport($db);

            $dashboardlines[] = $board->load_board($user);
        }
    }
    
    /**
    *   @abstract     Block Building - Text Intro
    */
    private function buildTableBlock()
    {

        global $langs;
        
        $langs->load("orders");
        
        $Data   = $this->getData();
        
        //====================================================================//
        // Build Table Contents
        //====================================================================//
        $Contents       = array();
        
        $Contents[] = array(
            $langs->trans("DolibarrWorkBoard"),
            $langs->trans("Number"),
            $langs->trans("Late"),
            );
        
        foreach ($Data as $WorkboardResponse) {
            if ($WorkboardResponse->nbtodolate > 0) {
                $Late = $WorkboardResponse->nbtodolate;
                $Late.= '&nbsp;<i class="fa fa-exclamation-triangle text-warning" aria-hidden="true"></i>';
                $Late.= "&nbsp;( >". ceil($WorkboardResponse->warning_delay) . ' ' . $langs->trans("days") . ")";
            } else {
                $Late = '<i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>';
            }
            
            $Contents[] = array(
                $WorkboardResponse->label,
                $WorkboardResponse->nbtodo,
                $Late,
            );
        }
        
        //====================================================================//
        // Build Table Options
        //====================================================================//
        $Options = array(
            "AllowHtml"         => true,
            "HeadingRows"       => 1,
        );
        //====================================================================//
        // Add Table Block
        $this->blocksFactory()->addTableBlock($Contents, $Options);
    }
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

    //====================================================================//
    // Overide Splash Functions
    //====================================================================//

    /**
     *      @abstract   Return name of this Widget Class
     */
    public function getName()
    {
        global $langs;
        $langs->load("main");
        $langs->load("boxes");
        return html_entity_decode($langs->trans(static::$NAME));
    }

    /**
     *      @abstract   Return Description of this Widget Class
     */
    public function getDesc()
    {
        global $langs;
        $langs->load("main");
        $langs->load("boxes");
        return html_entity_decode($langs->trans(static::$DESCRIPTION));
    }
    
    /**
     *      @abstract   Return Widget Status
     */
    public static function getIsDisabled()
    {
        if (Splash::local()->DolVersionCmp("3.9.0") >= 0) {
            return static::$DISABLED;
        }
        return false;
    }
}
