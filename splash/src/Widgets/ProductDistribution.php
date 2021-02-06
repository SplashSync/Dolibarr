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

namespace   Splash\Local\Widgets;

use CommandeStats;
use FactureStats;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractWidget;

/**
 * Dolibarr Products Distributions Widget
 */
class ProductDistribution extends AbstractWidget
{
    /**
     * Define Standard Options for this Widget
     * Override this array to change default options for your widget
     *
     * @var array
     */
    public static $OPTIONS = array(
        "Width" => self::SIZE_M,
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
    protected static $NAME = "BoxProductDistribution";

    /**
     * Widget Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $DESCRIPTION = "BoxProductDistribution";

    /**
     * Widget Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static $ICO = "fa fa-pie-chart";

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /** @var CommandeStats|FactureStats */
    private $stats;

    /** @var string */
    private $select;

    /** @var string */
    private $where;

    /** @var string */
    private $from;

    /** @var string */
    private $title;

    /** @var array */
    private $labels;

    /** @var string */
    private $mode = "Invoices";

    /** @var string */
    private $chartType = "Line";

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
     */
    public function getParameters()
    {
        global $langs;
        Local::loadDefaultLanguage();

        $langs->load("main");
        $langs->load("bills");
        $langs->load("orders");
        $langs->load("compta");

        $paramTitle = $langs->transnoentitiesnoconv("Products").'/'.$langs->transnoentitiesnoconv("Services");
        $titleInvoices = $langs->trans(
            "BoxProductDistributionFor",
            $paramTitle,
            $langs->transnoentitiesnoconv("Invoices")
        );
        $titleOrders = $langs->trans(
            "BoxProductDistributionFor",
            $paramTitle,
            $langs->transnoentitiesnoconv("Orders")
        );

        //====================================================================//
        // Select Data Type Mode
        $this->fieldsFactory()->create(SPL_T_TEXT)
            ->identifier("mode")
            ->name($langs->trans("Model"))
            ->isRequired()
            ->addChoice("Invoices", html_entity_decode($titleInvoices))
            ->addChoice(
                "InvoicesCount",
                html_entity_decode($titleInvoices." (".$langs->trans("NbOfLines").")")
            )
            ->addChoice("Orders", html_entity_decode($titleOrders))
            ->addChoice("OrdersCount", html_entity_decode($titleOrders." (".$langs->trans("NbOfLines").")"))
        ;

        //====================================================================//
        // Select Chart Rendering Mode
        $this->fieldsFactory()->create(SPL_T_TEXT)
            ->identifier("chart_type")
            ->name($langs->trans("Type"))
            ->isRequired()
            ->addChoice("Pie", "Pie Chart")
            ->addChoice("Bar", "Bar Chart")
        ;

        //====================================================================//
        // Publish Fields
        return $this->fieldsFactory()->publish();
    }

    /**
     * {@inheritdoc}
     */
    public function get($parameters = array())
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

        if (isset($parameters["mode"])
                && in_array($parameters["mode"], array("Invoices", "InvoicesCount", "Orders", "OrdersCount"), true)) {
            $this->mode = $parameters["mode"];
        }

        if (isset($parameters["chart_type"]) && in_array($parameters["chart_type"], array("Bar", "Pie"), true)) {
            $this->chartType = $parameters["chart_type"];
        }

        $this->importDates($parameters);
        $this->setupMode();

        if ("Bar" == $this->chartType) {
            $this->buildMorrisBarBlock();
        } else {
            $this->buildMorrisDonutBlock();
        }

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

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    /**
     * @return void
     */
    private function setupMode()
    {
        global $db, $langs;
        $langs->load("main");
        $langs->load("bills");
        $langs->load("compta");
        $langs->load("orders");
        $paramTitle = $langs->transnoentitiesnoconv("Products").'/'.$langs->transnoentitiesnoconv("Services");

        switch ($this->mode) {
            case "Invoices":
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
                $this->stats = new \FactureStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select = "product.ref as label, SUM(tl.".$this->stats->field_line.") as value";
                $this->from = $this->stats->from.", ";
                $this->from .= $this->stats->from_line.", ";
                $this->from .= MAIN_DB_PREFIX."product as product";
                $this->where = "f.rowid = tl.fk_facture AND tl.fk_product = product.rowid AND f.datef";
                //====================================================================//
                // Setup Titles
                $this->title = $langs->trans(
                    "BoxProductDistributionFor",
                    $paramTitle,
                    $langs->transnoentitiesnoconv("Invoices")
                );
                $this->labels = array($langs->trans("AmountHTShort"));

                break;
            case "InvoicesCount":
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
                $this->stats = new \FactureStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select = "product.ref as label, COUNT(product.ref) as value";
                $this->from = $this->stats->from.", ";
                $this->from .= $this->stats->from_line.", ";
                $this->from .= MAIN_DB_PREFIX."product as product";
                $this->where = "f.rowid = tl.fk_facture AND tl.fk_product = product.rowid AND f.datef";
                //====================================================================//
                // Setup Titles
                $this->title = $langs->trans(
                    "BoxProductDistributionFor",
                    $paramTitle,
                    $langs->transnoentitiesnoconv("Invoices")
                )." (".$langs->trans("NbOfLines").")";
                $this->labels = array($langs->trans("NbOfLines"));

                break;
            case "Orders":
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';
                $this->stats = new \CommandeStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select = "product.ref as label, SUM(tl.".$this->stats->field_line.") as value";
                $this->from = $this->stats->from.", ";
                $this->from .= $this->stats->from_line.", ";
                $this->from .= MAIN_DB_PREFIX."product as product";
                $this->where = "c.rowid = tl.fk_commande AND tl.fk_product = product.rowid AND c.date_commande";
                //====================================================================//
                // Setup Titles
                $this->title = $langs->trans(
                    "BoxProductDistributionFor",
                    $paramTitle,
                    $langs->transnoentitiesnoconv("Orders")
                );
                $this->labels = array($langs->trans("AmountHTShort"));

                break;
            case "OrdersCount":
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';
                $this->stats = new \CommandeStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select = "product.ref as label, COUNT(product.ref) as value";
                $this->from = $this->stats->from.", ";
                $this->from .= $this->stats->from_line.", ";
                $this->from .= MAIN_DB_PREFIX."product as product";
                $this->where = "c.rowid = tl.fk_commande AND tl.fk_product = product.rowid AND c.date_commande";
                //====================================================================//
                // Setup Titles
                $this->title = $langs->trans(
                    "BoxProductDistributionFor",
                    $paramTitle,
                    $langs->transnoentitiesnoconv("Orders")
                )." (".$langs->trans("NbOfLines").")";
                $this->labels = array($langs->trans("NbOfLines"));

                break;
        }
    }

    /**
     * Read Widget Datas
     *
     * @param null|int $limit
     *
     * @return array
     */
    private function getData($limit = null)
    {
        global $db;

        //====================================================================//
        // Execute SQL Query
        //====================================================================//
        $sql = "SELECT ".$this->select." FROM ".$this->from;
        $sql .= " WHERE ".$this->where." BETWEEN '".$this->DateStart."' AND '".$this->DateEnd."'";
        $sql .= " AND ".$this->stats->where;
        $sql .= " GROUP BY label";
        $sql .= $db->order('value', 'DESC');
        if ($limit) {
            $sql .= $db->plimit($limit);
        }

        $result = $db->query($sql);
        $num = $db->num_rows($result);           // Read number of results
        $index = 0;
        $rawData = array();
        while ($index < $num) {
            $rawData[$index] = $db->fetch_array($result);
            $index++;
        }

        return $rawData;
    }

    /**
     * Block Building - Morris Donut Graph
     *
     * @return void
     */
    private function buildMorrisDonutBlock()
    {
        global $langs;

        //====================================================================//
        // Build Chart Contents
        //====================================================================//
        $data = $this->getData();

        if (empty($data)) {
            $langs->load("admin");
            $this->blocksFactory()->addNotificationsBlock(array(
                "warning" => $langs->trans("PreviewNotAvailable")
            ));

            return;
        }

        $langs->load("compta");

        //====================================================================//
        // Chart Options
        $chartOptions = array(
            "title" => $this->title,
            "labels" => $this->labels,
        );
        //====================================================================//
        // Block Options
        $options = array(
            "AllowHtml" => true,
        );
        //====================================================================//
        // Add Table Block
        $this->blocksFactory()->addMorrisDonutBlock($data, $chartOptions, $options);
    }

    /**
     * Block Building - Morris Bar Graph
     *
     * @return void
     */
    private function buildMorrisBarBlock()
    {
        global $langs;

        //====================================================================//
        // Build Chart Contents
        //====================================================================//
        $data = $this->getData(5);

        if (empty($data)) {
            $langs->load("admin");
            $this->blocksFactory()->addNotificationsBlock(array(
                "warning" => $langs->trans("PreviewNotAvailable")
            ));

            return;
        }

        $langs->load("compta");

        //====================================================================//
        // Chart Options
        $chartOptions = array(
            "title" => $this->title,
            "labels" => $this->labels,
        );
        //====================================================================//
        // Block Options
        $options = array(
            "AllowHtml" => true,
        );
        //====================================================================//
        // Add Table Block
        $this->blocksFactory()->addMorrisGraphBlock($data, "Bar", $chartOptions, $options);
    }
}
