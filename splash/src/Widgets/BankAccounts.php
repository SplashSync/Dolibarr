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

use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractWidget;

/**
 * BANK ACCOUNTS LEVELS WIDGET
 */
class BankAccounts extends AbstractWidget
{
    //====================================================================//
    // Define Standard Options for this Widget
    // Override this array to change default options for your widget
    public static $OPTIONS = array(
        "Width" => self::SIZE_M,
        "Header" => true,
        "Footer" => false,
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
    protected static $NAME = "BoxCurrentAccounts";

    /**
     * Widget Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $DESCRIPTION = "BoxTitleCurrentAccounts";

    /**
     * Widget Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static $ICO = "fa fa-money";

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var int
     */
    private $maxItems = 10;

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
     * Return Widget Customs Parameters
     *
     * @return array|false
     */
    public function getParameters()
    {
        global $langs;
        $langs->load("admin");

        //====================================================================//
        // Use Compact Mode
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("compact")
            ->Name($langs->trans("Compact Mode"));

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
        // Build Disabled Block
        //====================================================================//
        $this->buildDisabledBlock();

        //====================================================================//
        // Build Data Blocks
        //====================================================================//
        $this->maxItems = !empty($parameters["max"]) ? $parameters["max"] : 10;
        if ($parameters["compact"]) {
            $this->buildSparkBlock();
        } else {
            $this->buildTableBlock();
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
        $langs->load("boxes");

        return html_entity_decode($langs->trans(static::$NAME));
    }

    /**
     * {@inheritdoc}
     */
    public function getDesc()
    {
        global $langs;
        $langs->load("boxes");

        return html_entity_decode($langs->trans(static::$DESCRIPTION));
    }

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    /**
     * Block Building - Box is Disabled
     *
     * @return void
     */
    private function buildDisabledBlock()
    {
        global $langs, $user;

        if (!$user->rights->banque->lire) {
            $langs->load("admin");
            $contents = array("warning" => $langs->trans("ReadPermissionNotAllowed"));
            //====================================================================//
            // Warning Block
            $this->blocksFactory()->addNotificationsBlock($contents);
        }
    }

    /**
     * Read Widget Datas
     *
     * @return array
     */
    private function getData()
    {
        global $langs, $user, $db, $conf;

        if (!$user->rights->banque->lire) {
            return array();
        }

        //====================================================================//
        // Execute SQL Request
        //====================================================================//
        $sql = "SELECT rowid, ref, label, bank, clos, account_number, currency_code, min_desired, comment";
        $sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
        $sql .= " WHERE entity = ".$conf->entity;
        $sql .= " AND clos = 0";
        $sql .= " ORDER BY label";
        $sql .= $db->plimit($this->maxItems, 0);
        dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
        $result = $db->query($sql);

        //====================================================================//
        // Empty Contents
        //====================================================================//
        if ($db->num_rows($result) < 1) {
            $langs->load("admin");
            $contents = array("warning" => $langs->trans("PreviewNotAvailable"));
            //====================================================================//
            // Warning Block
            $this->blocksFactory()->addNotificationsBlock($contents);

            return array();
        }

        $index = 0;
        $rawData = array();
        while ($index < $db->num_rows($result)) {
            $rawData[$index] = $db->fetch_array($result);
            $index++;
        }

        return $rawData;
    }

    /**
     * Block Building - Text Intro
     *
     * @return void
     */
    private function buildTableBlock()
    {
        global $langs, $db;

        $data = $this->getData();

        //====================================================================//
        // Build Table Contents
        //====================================================================//
        $contents = array();
        include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
        $accountStatic = new \Account($db);
        $prefix = '<i class="fa fa-university" aria-hidden="true">&nbsp;</i>';

        foreach ($data as $line) {
            $accountStatic->id = $line["rowid"];
            $accountStatic->label = $line["label"];
            $accountStatic->number = $line["number"];
            $solde = $accountStatic->solde(0);

            if ($solde < 0) {
                $value = '<span class="text-danger">';
                $value .= price($solde, 0, $langs, 0, -1, -1, $line["currency_code"]);
                $value .= '</span>';
                $value .= '&nbsp;<i class="fa fa-exclamation-triangle text-danger" aria-hidden="true"></i>';
            } elseif ($solde < $line["min_desired"]) {
                $value = '<span class="text-warning">';
                $value .= price($solde, 0, $langs, 0, -1, -1, $line["currency_code"]);
                $value .= '</span>';
                $value .= '&nbsp;<i class="fa fa-exclamation text-warning" aria-hidden="true"></i>';
            } else {
                $value = '<span class="text-success">';
                $value .= price($solde, 0, $langs, 0, -1, -1, $line["currency_code"]);
                $value .= '</span>';
            }

            $contents[] = array(
                $prefix.$line["ref"], $line["label"], $line["bank"],
                $value,
            );
        }

        //====================================================================//
        // Build Table Options
        //====================================================================//
        $options = array(
            "AllowHtml" => true,
            "HeadingRows" => 0,
        );

        //====================================================================//
        // Add Table Block
        $this->blocksFactory()->addTableBlock($contents, $options);
    }

    /**
     * Block Building - Text Intro
     *
     * @return void
     */
    private function buildSparkBlock()
    {
        global $langs, $db;

        $data = $this->getData();

        //====================================================================//
        // Build SparkInfo Options
        //====================================================================//
        switch (count($data)) {
            case 1:
                $width = self::SIZE_XL;

                break;
            case 2:
                $width = self::SIZE_M;

                break;
            case 3:
                $width = self::SIZE_SM;

                break;
            default:
                $width = self::SIZE_XS;

                break;
        }
        $options = array(
            "AllowHtml" => true,
            "Width" => $width
        );

        //====================================================================//
        // Build SparkInfo Contents
        //====================================================================//

        include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
        $accountStatic = new \Account($db);

        foreach ($data as $line) {
            $accountStatic->id = $line["rowid"];
            $accountStatic->label = $line["label"];
            $accountStatic->number = $line["number"];
            $solde = $accountStatic->solde(0);

            if ($solde < 0) {
                $class = "text-danger";
                $value = '<span class="text-danger">';
                $value .= price($solde, 0, $langs, 0, -1, -1, $line["currency_code"]);
                $value .= '</span>';
                $value .= '&nbsp;<i class="fa fa-exclamation-triangle text-danger" aria-hidden="true"></i>';
            } elseif ($solde < $line["min_desired"]) {
                $class = "text-warning";
                $value = '<span class="text-warning">';
                $value .= price($solde, 0, $langs, 0, -1, -1, $line["currency_code"]);
                $value .= '</span>';
                $value .= '&nbsp;<i class="fa fa-exclamation text-warning" aria-hidden="true"></i>';
            } else {
                $class = "text-success";
                $value = '<span class="text-success">';
                $value .= price($solde, 0, $langs, 0, -1, -1, $line["currency_code"]);
                $value .= '</span>';
            }

            $contents = array(
                "title" => $line["ref"],
                "fa_icon" => "university ".$class,
                "value" => $value,
            );
            //====================================================================//
            // Add SparkInfo Block
            $this->blocksFactory()->addSparkInfoBlock($contents, $options);
        }
    }
}
