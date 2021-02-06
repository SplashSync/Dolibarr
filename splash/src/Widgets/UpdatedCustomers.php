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
 * LAST CUSTOMER BOX WIDGET
 */
class UpdatedCustomers extends AbstractWidget
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
        "Footer" => false
    );

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Widget Name (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $NAME = "BoxLastCustomers";

    /**
     * Widget Description (Translated by Module)
     *
     * {@inheritdoc}
     */
    protected static $DESCRIPTION = "BoxTitleLastModifiedCustomers";

    /**
     * Widget Icon (FontAwesome or Glyph ico tag)
     *
     * {@inheritdoc}
     */
    protected static $ICO = "fa fa-users";

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
     * {@inheritdoc}
     */
    public function getParameters()
    {
        global $langs;
        $langs->load("admin");

        //====================================================================//
        // Max Number of Entities
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("max")
            ->Name($langs->trans("MaxNbOfLinesForBoxes"))
            ->Description($langs->trans("BoxTitleLastModifiedCustomers"));

        //====================================================================//
        // Publish Fields
        return $this->fieldsFactory()->publish();
    }

    /**
     * {@inheritdoc}
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
        // Build Disabled Block
        //====================================================================//
        $this->buildDisabledBlock();

        //====================================================================//
        // Build Disabled Block
        //====================================================================//
        $this->maxItems = !empty($parameters["max"]) ? $parameters["max"] : 10;
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

        if (!$user->rights->societe->lire) {
            $langs->load("admin");
            $contents = array("warning" => $langs->trans("PreviewNotAvailable"));
            //====================================================================//
            // Warning Block
            $this->blocksFactory()->addNotificationsBlock($contents);
        }
    }

    /**
     * Block Building - Text Intro
     *
     * @return void
     */
    private function buildTableBlock()
    {
        global $langs, $db, $user;

        if (!$user->rights->societe->lire) {
            return;
        }

        //====================================================================//
        // Execute SQL Request
        //====================================================================//
        $sql = "SELECT s.nom as name, s.rowid as socid, s.tms as modified, s.status as status";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
        $sql .= " ORDER BY s.tms DESC";
        $sql .= $db->plimit($this->maxItems, 0);
        dol_syslog(get_class($this)."::loadLastModifiedUsers", LOG_DEBUG);
        $result = $db->query($sql);

        //====================================================================//
        // Empty Contents
        //====================================================================//
        if ($db->num_rows($result) < 1) {
            $langs->load("admin");
            $contents = array("warning" => $langs->trans("NoRecordedCustomers"));
            //====================================================================//
            // Warning Block
            $this->blocksFactory()->addNotificationsBlock($contents);

            return;
        }

        //====================================================================//
        // Build Table Contents
        //====================================================================//
        $langs->load('companies');
        $contents = array();
        $num = $db->num_rows($result);           // Read number of results
        $index = 0;

        while ($index < $num) {
            $value = $db->fetch_array($result);
            $name = '<i class="fa fa-building-o" aria-hidden="true">&nbsp;-&nbsp;</i>'.$value["name"];
            if ($value["status"]) {
                $status = '<i class="fa fa-check-circle-o text-success" aria-hidden="true">&nbsp;';
                $status .= $langs->trans("InActivity").'</i>';
            } else {
                $status = '<i class="fa fa-times-circle-o text-danger" aria-hidden="true">&nbsp;';
                $status .= $langs->trans("ActivityCeased").'</i>';
            }
            $contents[] = array($name, $value["modified"], $status);
            $index++;
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
}
