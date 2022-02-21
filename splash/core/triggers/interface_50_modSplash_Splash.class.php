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
use Splash\Local\Objects;

/**
 * Splash Module Changes Detection Triggers
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class InterfaceSplash extends DolibarrTriggers
{
    //====================================================================//
    // Import Commit Triggers Action from Objects Namespaces
    //====================================================================//
    use Objects\ThirdParty\TriggersTrait;
    use Objects\Address\TriggersTrait;
    use Objects\Product\TriggersTrait;
    use Objects\Order\TriggersTrait;
    use Objects\Invoice\TriggersTrait;
    use Objects\SupplierInvoice\TriggersTrait;

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
        parent::__construct($db);
        $this->family = "Modules";
        $this->description = "Triggers of Splash module.";
        $this->version = self::VERSION_DOLIBARR;

        //====================================================================//
        // Load traductions files required by by page
        $langs->load("errors");
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
            setEventMessage($log->getHtml($log->msg), 'mesgs');
        }
        if (!empty($log->war)) {
            setEventMessage($log->getHtml($log->war), 'warnings');
        }
        if (!empty($log->err)) {
            setEventMessage($log->getHtml($log->err), 'errors');
        }
        if (!empty($log->deb)) {
            setEventMessage($log->getHtml($log->deb), 'warnings');
        }

        $log->CleanLog();
    }

    /**
     * {@inheritDoc}
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        Splash::log()->deb("Start of Splash Module Trigger Actions (Action=".$action.")");

        //====================================================================//
        // Init Action Parameters
        $this->objectType = null;
        $this->objectId = null;
        $this->action = null;
        $this->login = $user->login ?:"Unknown";
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
        // TRIGGER ACTION FOR : SUPPLIER INVOICE
        //====================================================================//
        $doCommit |= $this->doSupplierInvoiceCommit($action, $object);

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
     * @throws Exception
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
                (string) $this->action,     // Splash Action Type
                $this->login,               // Current User Login
                (string) $this->comment     // Action Comment
            );
            Splash::log()->deb("Change Committed (Action=".$this->comment.") Object => ".$this->objectType);
        } else {
            Splash::log()->war("Commit Id Missing (Action=".$this->comment.") Object => ".$this->objectType);
        }

        //====================================================================//
        //  Post User Messages
        $this->postMessages(Splash::log());
    }
}
