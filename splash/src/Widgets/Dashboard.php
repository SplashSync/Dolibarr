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

//====================================================================//
// *******************************************************************//
//                     SPLASH FOR DOLIBARR                            //
// *******************************************************************//
//                  BANK ACCOUNTS LEVELS WIDGET                       //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Widgets;

use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractWidget;

/**
 * Dolibarr Dashboard Widget
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Dashboard extends AbstractWidget
{
    /**
     * Define Standard Options for this Widget
     * Override this array to change default options for your widget
     *
     * @var array
     */
    public static $OPTIONS = array(
        "Width" => self::SIZE_SM,
        "Header" => true,
        "Footer" => true,
        'UseCache' => true,
        'CacheLifeTime' => 60,
    );

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Widget Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $NAME = "DolibarrWorkBoard";

    /**
     * Widget Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $DESCRIPTION = "DolibarrWorkBoard";

    /**
     * Widget Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static $ICO = "fa fa-briefcase";

    //====================================================================//
    // Class Main Functions
    //====================================================================//

    /**
     * Class Constructor
     */
    public function __construct()
    {
        //====================================================================//
        // Load Default Language
        Local::loadDefaultLanguage();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function get($parameters = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Load Default Language
        Local::loadDefaultLanguage();

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
        $blocks = $this->blocksFactory()->render();
        if (false !== $blocks) {
            $this->setBlocks($blocks);
        }

        //====================================================================//
        // Publish Widget
        return $this->render();
    }

    //====================================================================//
    // Overide Splash Functions
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        global $langs;
        $langs->load("main");
        $langs->load("boxes");

        return html_entity_decode($langs->trans(static::$NAME));
    }

    /**
     * {@inheritdoc}
     */
    public function getDesc()
    {
        global $langs;
        $langs->load("main");
        $langs->load("boxes");

        return html_entity_decode($langs->trans(static::$DESCRIPTION));
    }

    /**
     * {@inheritdoc}
     */
    public static function getIsDisabled()
    {
        if (Local::dolVersionCmp("3.9.0") >= 0) {
            return static::$DISABLED;
        }

        return false;
    }

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    /**
     * Read Widget Datas
     *
     * @return array
     */
    private function getData()
    {
        //Array that contains all WorkboardResponse classes to process them
        $dashboardlines = array();

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
     * Read Late Actions Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getLateActions(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of actions to do (late)
        if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->read) {
            include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
            $board = new \ActionComm($db);

            $dashboardlines[] = $board->load_board($user);
        }
    }

    /**
     * Read Customers Orders Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getCustomerOrders(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of customer orders a deal
        if (! empty($conf->commande->enabled) && $user->rights->commande->lire) {
            include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
            $board = new \Commande($db);

            $dashboardlines[] = $board->load_board($user);
        }
    }

    /**
     * Read Suppliers Orders Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getSupplierOrders(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of suppliers orders a deal
        if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire) {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
            $board = new \CommandeFournisseur($db);

            $dashboardlines[] = $board->load_board($user);
        }
    }

    /**
     * Read Suppliers Orders Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getOpenPropals(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of commercial proposals opened (expired)
        if (! empty($conf->propal->enabled) && $user->rights->propale->lire) {
            include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
            $board = new \Propal($db);
            $dashboardlines[] = $board->load_board($user, "opened");

            // Number of commercial proposals CLOSED signed (billed)
            $dashboardlines[] = $board->load_board($user, "signed");
        }
    }

    /**
     * Read Suppliers Orders Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getDelayedServices(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of services enabled (delayed)
        if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire) {
            include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
            $board = new \Contrat($db);
            $dashboardlines[] = $board->load_board($user, "inactives");

            // Number of active services (expired)
            $dashboardlines[] = $board->load_board($user, "expired");
        }
    }

    /**
     * Read Customers Invoices Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getCustomersInvoices(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of invoices customers (has paid)
        if (! empty($conf->facture->enabled) && $user->rights->facture->lire) {
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $board = new \Facture($db);
            $dashboardlines[] = $board->load_board($user);
        }
    }

    /**
     * Read Supplier Invoices Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getSupplierInvoices(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of supplier invoices (has paid)
        if (! empty($conf->fournisseur->enabled) && ! empty($conf->facture->enabled) && $user->rights->facture->lire) {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
            $board = new \FactureFournisseur($db);
            $dashboardlines[] = $board->load_board($user);
        }
    }

    /**
     * Read Transactions Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getTransactionsDashboard(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of transactions to conciliate
        if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id) {
            include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
            $board = new \Account($db);
            $dashboardlines[] = $board->load_board($user);
        }
    }

    /**
     * Read Bank Wire Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getBankWire(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of cheque to send
        if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id) {
            include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
            $board = new \RemiseCheque($db);
            $dashboardlines[] = $board->load_board($user);
        }
    }

    /**
     * Read Bank Wire Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getMembers(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of foundation members
        if (! empty($conf->adherent->enabled) && $user->rights->adherent->lire && ! $user->societe_id) {
            include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
            $board = new \Adherent($db);
            $dashboardlines[] = $board->load_board($user, "expired");
        }
    }

    /**
     * Read Expenses Dashboard
     *
     * @param array $dashboardlines
     *
     * @return void
     */
    private function getExpenesDashboard(&$dashboardlines)
    {
        global $user, $db, $conf;

        // Number of expense reports to pay
        if (! empty($conf->expensereport->enabled) && $user->rights->expensereport->lire) {
            include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
            $board = new \ExpenseReport($db);

            $dashboardlines[] = $board->load_board($user);
        }
    }

    /**
     * Block Building - Text Intro
     *
     * @return void
     */
    private function buildTableBlock()
    {
        global $langs;

        $langs->load("orders");

        $data = $this->getData();

        //====================================================================//
        // Build Table Contents
        //====================================================================//
        $contents = array();

        $contents[] = array(
            $langs->trans("DolibarrWorkBoard"),
            $langs->trans("Number"),
            $langs->trans("Late"),
        );

        foreach ($data as $workboardResponse) {
            if ($workboardResponse->nbtodolate > 0) {
                $late = $workboardResponse->nbtodolate;
                $late .= '&nbsp;<i class="fa fa-exclamation-triangle text-warning" aria-hidden="true"></i>';
                $late .= "&nbsp;( >".ceil($workboardResponse->warning_delay).' '.$langs->trans("days").")";
            } else {
                $late = '<i class="fa fa-check-circle-o text-success" aria-hidden="true"></i>';
            }

            $contents[] = array(
                $workboardResponse->label,
                $workboardResponse->nbtodo,
                $late,
            );
        }

        //====================================================================//
        // Build Table Options
        //====================================================================//
        $options = array(
            "AllowHtml" => true,
            "HeadingRows" => 1,
        );
        //====================================================================//
        // Add Table Block
        $this->blocksFactory()->addTableBlock($contents, $options);
    }
}
