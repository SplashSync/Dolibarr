<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
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
 * BANK ACCOUNTS LEVELS WIDGET
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class StatGraphs extends AbstractWidget
{
    /**
     * Define Standard Options for this Widget
     * Override this array to change default options for your widget
     *
     * @var array
     */
    public static array $options = array(
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
    protected static string $name = "Statistics";

    /**
     * Widget Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static string $description = "Statistics";

    /**
     * Widget Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static string $ico = "fa fa-line-chart";

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /** @var CommandeStats|FactureStats */
    private $stats;

    /** @var string */
    private string $select;

    /** @var string */
    private string $where;

    /** @var string */
    private string $title;

    /** @var array */
    private array $labels;

    /** @var string */
    private string $mode = "CustomerInvoices";

    /** @var string */
    private string $chartType = "Line";

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
    public function getParameters(): array
    {
        global $langs;
        Local::loadDefaultLanguage();
        $langs->load("compta");
        $langs->load("bills");

        //====================================================================//
        // Select Data Type Mode
        $this->fieldsFactory()->create(SPL_T_TEXT)
            ->identifier("mode")
            ->name($langs->trans("Model"))
            ->isRequired()
            ->addChoice("CustomerInvoices", $langs->trans("ReportTurnover"))
            ->addChoice("CustomerOrders", $langs->trans("OrderStats"))
            ->addChoice("SupplierInvoices", $langs->trans("BillsSuppliers"))
        ;

        //====================================================================//
        // Select Chart Rendering Mode
        $this->fieldsFactory()->create(SPL_T_TEXT)
            ->identifier("chart_type")
            ->name($langs->trans("Type"))
            ->isRequired()
            ->addChoice("Line", "Line Chart")
            ->addChoice("Bar", "Bar Chart")
            ->addChoice("Area", "Area Chart")
        ;

        //====================================================================//
        // Publish Fields
        return $this->fieldsFactory()->publish() ?? array();
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $parameters = null): array
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

        $allowedModes = array("CustomerInvoices", "CustomerOrders", "SupplierInvoices");
        if (isset($parameters["mode"]) && in_array($parameters["mode"], $allowedModes, true)) {
            $this->mode = $parameters["mode"];
        }

        $allowedTypes = array("Bar", "Line", "Area");
        if (isset($parameters["chart_type"]) && in_array($parameters["chart_type"], $allowedTypes, true)) {
            $this->chartType = $parameters["chart_type"];
        }

        $this->importDates(empty($parameters) ? array() : $parameters);
        $this->setupMode();

        $this->buildMorrisBarBlock();

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
    // Override Splash Functions
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        global $langs;
        $langs->load("main");

        return $langs->trans(static::$name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDesc(): string
    {
        global $langs;
        $langs->load("main");

        return $langs->trans(static::$description);
    }

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    /**
     * @return void
     */
    private function setupMode(): void
    {
        global $db, $langs;

        switch ($this->mode) {
            case "CustomerInvoices":
                $langs->load("compta");
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
                $this->stats = new \FactureStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select = "date_format(f.datef,'%".$this->groupBy."') as step, SUM(f.total) as total";
                $this->where = "f.datef ";
                $this->title = $langs->trans("SalesTurnover");
                $this->labels = array($langs->trans("AmountTTCShort"));

                break;
            case "SupplierInvoices":
                $langs->load("compta");
                $langs->load("bills");
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
                $this->stats = new \FactureStats($db, 0, 'supplier', 0);
                //====================================================================//
                // Setup Mode
                $this->select = "date_format(f.datef,'%".$this->groupBy."') as step, SUM(f.total_ht) as total";
                $this->where = "f.datef ";
                $this->title = $langs->trans("BillsSuppliers");
                $this->labels = array($langs->trans("AmountHTShort"));

                break;
            case "CustomerOrders":
                $langs->load("compta");
                //====================================================================//
                // Load Stat Class
                include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';
                $this->stats = new \CommandeStats($db, 0, 'customer', 0);
                //====================================================================//
                // Setup Mode
                $this->select = "date_format(c.date_commande,'%".$this->groupBy."') "
                        ."as step, SUM(c.total_ht) as total";
                $this->where = "c.date_commande ";
                $this->title = $langs->trans("OrderStats");
                $this->labels = array($langs->trans("AmountHTShort"));

                break;
        }
    }

    /**
     * Read Widget Datas
     *
     * @return array
     */
    private function getData(): array
    {
        global $db;

        //====================================================================//
        // Execute SQL Query
        //====================================================================//

        $sql = "SELECT ".$this->select;
        $sql .= " FROM ".$this->stats->from;
        $sql .= " WHERE ".$this->where." BETWEEN '".$this->dateStart."' AND '".$this->dateEnd."'";
        $sql .= " AND ".$this->stats->where;
        $sql .= " GROUP BY step";
        $sql .= $db->order('step', 'ASC');

        $result = $db->query($sql);
        $num = $db->num_rows($result);           // Read number of results
        $index = 0;
        $rawData = array();

        while ($index < $num) {
            $value = $db->fetch_array($result);
            $rawData[$value["step"]] = $value["total"];
            $index++;
        }

        return $this->parseDatedData($rawData);
    }

    /**
     * Block Building - Morris Bar Graph
     *
     * @return void
     */
    private function buildMorrisBarBlock(): void
    {
        global $langs;

        $langs->load("compta");
        $langs->load("bills");

        //====================================================================//
        // Build Chart Contents
        //====================================================================//
        $data = $this->getData();

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
        $this->blocksFactory()->addMorrisGraphBlock($data, $this->chartType, $chartOptions, $options);
    }
}
