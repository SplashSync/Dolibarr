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

//====================================================================//
// *******************************************************************//
//                     SPLASH FOR DOLIBARR                            //
// *******************************************************************//
//                  BANK ACCOUNTS LEVELS WIDGET                       //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Widgets;

use Exception;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractWidget;

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CustomerInvoicesStats extends AbstractWidget
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
     * Widget Disable Flag. Uncomment this line to Override this flag and disable Object.
     *
     * {@inheritdoc}
     */
    protected static bool $disabled = true;

    /**
     * Widget Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static string $name = "CustomersInvoices";

    /**
     * Widget Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static string $description = "CustomersInvoices";

    /**
     * Widget Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static string $ico = "fa fa-line-chart";

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
    public function get(array $parameters = array()): array
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

        $this->importDates($parameters);

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
    // Overide Splash Functions
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function getName():string
    {
        global $langs;
        $langs->load("main");
        $langs->load("boxes");

        return $langs->trans(static::$name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDesc(): string
    {
        global $langs;
        $langs->load("main");
        $langs->load("boxes");

        return $langs->trans(static::$description);
    }

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    /**
     * Read Widget Datas
     *
     * @throws Exception
     *
     * @return array
     */
    private function getData(): array
    {
        global $db;

        include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';

        $stats = new \FactureStats($db, 0, 'customer', 0);

        //====================================================================//
        // Execute SQL Query
        //====================================================================//

        $sql = "SELECT date_format(f.datef,'%".$this->groupBy."') as step,";
        $sql .= " COUNT(*) as nb, SUM(f.total) as total";
        $sql .= " FROM ".$stats->from;
        $sql .= " WHERE f.datef BETWEEN '".$this->dateStart."' AND '".$this->dateEnd."'";
        $sql .= " AND ".$stats->where;
        $sql .= " GROUP BY step";
        $sql .= $db->order('step', 'ASC');

        $result = $db->query($sql);

        $rawData = array();
        $results = mysqli_fetch_all($result, MYSQLI_ASSOC);
        if (is_iterable($results)) {
            foreach ($results as $value) {
                $rawData[$value["step"]] = $value["total"];
            }
        }

        return $this->parseDatedData($rawData);
    }

    /**
     * Block Building - Morris Bar Graph
     *
     * @throws Exception
     *
     * @return void
     */
    private function buildMorrisBarBlock(): void
    {
        global $langs;

        $langs->load("compta");

        //====================================================================//
        // Build Chart Contents
        //====================================================================//
        $data = $this->getData();

        //====================================================================//
        // Chart Options
        $chartOptions = array(
            "title" => $langs->trans("SalesTurnover"),
            "labels" => array($langs->trans("AmountTTCShort")),
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
