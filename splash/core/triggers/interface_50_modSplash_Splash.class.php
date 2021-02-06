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
// PHP CS Overrides
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable PSR1.Methods.CamelCapsMethodName

//====================================================================//
// Splash Module Definitions
include_once(dirname(dirname(dirname(__FILE__)))."/_conf/defines.inc.php");

use Splash\Client\Splash;
use Splash\Components\Logger;

/**
 * Classe des fonctions triggers des actions personalisees du workflow
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class InterfaceSplash
{
    //====================================================================//
    // Import Commit Triggers Action from Objects Namespaces
    //====================================================================//
    use \Splash\Local\Objects\ThirdParty\TriggersTrait;
    use \Splash\Local\Objects\Address\TriggersTrait;
    use \Splash\Local\Objects\Product\TriggersTrait;
    use \Splash\Local\Objects\Order\TriggersTrait;
    use \Splash\Local\Objects\Invoice\TriggersTrait;
    private $db;
    private $name;
    private $family;
    private $version;
    private $description;

    /** @var null|array|string */
    private $objectId;
    /** @var null|string */
    private $action;
    /** @var null|string */
    private $objectType;
    /** @var string */
    private $login = "Unknown User";
    /** @var null|string */
    private $comment = "Dolibarr Commit";

    /**
     * Class Constructor.
     *
     * @param mixed $db Dolibarr Database Object
     */
    public function __construct($db)
    {
        global $langs;

        //====================================================================//
        // Class Init
        $this->db = $db ;
        $this->name = (string) preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "Modules";
        $this->description = "Triggers of Splash module.";
        $this->version = 'dolibarr';

        //====================================================================//
        // Load traductions files requiredby by page
        $langs->load("errors");
    }

    /**
     * Renvoi nom du lot de triggers
     *
     * @return string Nom du lot de triggers
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Renvoi descriptif du lot de triggers
     *
     * @return string Descriptif du lot de triggers
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Renvoi version du lot de triggers
     *
     * @return string Version du lot de triggers
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ('development' == $this->version) {
            return $langs->trans("Development");
        }
        if ('experimental' == $this->version) {
            return $langs->trans("Experimental");
        }
        if ('dolibarr' == $this->version) {
            return DOL_VERSION;
        }
        if ($this->version) {
            return $this->version;
        }

        return $langs->trans("Unknown");
    }

    /**
     * Read all log messages posted by OsWs and post it on dolibarr
     *
     * @param Logger $log Input Log Class
     *
     * @return void
     */
    public function postMessages(Logger $log)
    {
        //====================================================================//
        // When Library is called in server mode, no Message Storage
        if (!empty(SPLASH_SERVER_MODE)) {
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

        $log->CleanLog();
    }

    /**
     * Fonction appelee lors du declenchement d'un evenement Dolibarr.
     * D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *
     * @param string $action Code de l'evenement
     * @param object $object Objet concerne
     * @param User   $user   Objet user
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function run_trigger($action, $object, $user)
    {
        Splash::log()->deb("Start of Splash Module Trigger Actions (Action=".$action.")");

        //====================================================================//
        // Init Action Parameters
        $this->objectType = null;
        $this->objectId = null;
        $this->action = null;
        $this->login = ($user->login)?$user->login:"Unknown";
        $this->comment = null;

        $doCommit = false;

        //====================================================================//
        // TRIGGER ACTION FOR : ThirdParty
        //====================================================================//
        $doCommit |= $this->doThirdPartyCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : Address / Contact
        //====================================================================//
        $doCommit |= $this->doAddressCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : Products
        //====================================================================//
        $doCommit |= $this->doProductCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : Categories
        //====================================================================//

        //====================================================================//
        // TRIGGER ACTION FOR : ORDER
        //====================================================================//
        $doCommit |= $this->doOrderCommit($action, $object);
        //====================================================================//
        // TRIGGER ACTION FOR : INVOICE
        //====================================================================//
        $doCommit |= $this->doInvoiceCommit($action, $object);

        //====================================================================//
        // Log Trigger Action
        Splash::log()->deb(
            "Trigger for action '${action}' launched by '".$this->login."' for Object id=".$this->objectId
        );

        //====================================================================//
        // No Action To Perform
        if (!$doCommit) {
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
    }

    /**
     * Publish Object Change to Splash Sync Server
     *
     * @return void
     */
    protected function doSplashCommit()
    {
        //====================================================================//
        // Safety Check => Required Infos Provided
        if ((null === $this->objectId) || (null === $this->objectType)) {
            return;
        }
        //====================================================================//
        // Safety Check => ObjectType is Active
        if (!in_array($this->objectType, Splash::objects(), true)) {
            return;
        }
        //====================================================================//
        // Prevent Repeated Commit if Needed
        if ((SPL_A_UPDATE == $this->action) && Splash::object($this->objectType)->isLocked()) {
            return;
        }

        //====================================================================//
        // Verify Id Before commit
        if ($this->objectId > 0) {
            //====================================================================//
            // Commit Change to OsWs Module
            Splash::commit(
                $this->objectType,          // Object Type
                $this->objectId,            // Object Identifier (RowId ro Array of RowId)
                $this->action,              // Splash Action Type
                $this->login,               // Current User Login
                (string) $this->comment     // Action Comment
            );
            Splash::log()->deb("Change Commited (Action=".$this->comment.") Object => ".$this->objectType);
        } else {
            Splash::log()->war("Commit Id Missing (Action=".$this->comment.") Object => ".$this->objectType);
        }

        //====================================================================//
        //  Post User Messages
        $this->postMessages(Splash::log());
    }
}
