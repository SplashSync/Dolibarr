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

namespace Splash\Local\Models;

//====================================================================//
// PHP CS Overrides
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable PSR1.Methods.CamelCapsMethodName

//====================================================================//
// Splash Module Definitions
include_once(dirname(dirname(dirname(__FILE__)))."/_conf/defines.inc.php");

use Conf;
use DolibarrTriggers;
use Exception;
use Splash\Client\Splash;
use Splash\Components\Logger;
use Splash\Local\Objects;
use Translate;
use User;

/**
 * Splash Module Changes Detection Triggers
 */
class AbstractTrigger extends DolibarrTriggers
{
    //====================================================================//
    // Import Commit Triggers Action from Objects Namespaces
    //====================================================================//
    use Objects\ThirdParty\TriggersTrait;
    use Objects\Address\TriggersTrait;
    use Objects\Product\TriggersTrait;
    use Objects\Order\TriggersTrait;
    use Objects\Invoice\TriggersTrait;

    /**
     * Splash Action Type Name
     *
     * @var null|string
     */
    protected $action;

    /**
     * Detected Object IDs
     *
     * @var null|array|string
     */
    protected $objectId;

    /**
     * Detected Object Type
     *
     * @var null|string
     */
    protected $objectType;

    /**
     * User Name
     *
     * @var string
     */
    protected $login = "Unknown User";

    /**
     * Event Comment
     *
     * @var null|string
     */
    protected $comment = "Dolibarr Commit";

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
        // Load translations files required by by page
        $langs->load("errors");
    }

    /**
     * @param string    $action Event action code
     * @param Object    $object Object
     * @param User      $user   Object user
     * @param Translate $langs  Object langs
     * @param Conf      $conf   Object conf
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return int
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf): int
    {
        Splash::log()->deb("Start of Splash Module Trigger Actions (Action=".$action.")");

        //====================================================================//
        // Init Action Parameters
        $this->objectType = null;
        $this->objectId = null;
        $this->action = null;
        $this->login = $user->login ?:"Unknown";
        $this->comment = null;

        //====================================================================//
        // No Action To Perform
        if (!$this->doActionDetection($action, $object)) {
            //====================================================================//
            // Add Dolibarr Log Message
            dol_syslog(SPL_LOGPREFIX."End of Trigger for Action='".$action."'", LOG_DEBUG);

            return 1;
        }

        //====================================================================//
        // Commit change to Splash Server
        $this->doSplashCommit();

        //====================================================================//
        // Add Dolibarr Log Message
        dol_syslog(SPL_LOGPREFIX."End of Trigger for Action='".$action."'", LOG_DEBUG);

        return 1;
    }

    /**
     * Detect Object Changes
     *
     * @param string $action Event Code
     * @param object $object Impacted Objet
     *
     * @throws Exception
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doActionDetection(string $action, object $object): bool
    {
        throw new Exception("This method MUST be Defined on parent class");
    }

    /**
     * Publish Object Change to Splash Sync Server
     *
     * @throws Exception
     *
     * @return void
     */
    protected function doSplashCommit(): void
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
            // Commit Change to Splash Server
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

    /**
     * Read all log messages posted by OsWs and post it on dolibarr
     *
     * @param Logger $log Input Log Class
     *
     * @return void
     */
    private function postMessages(Logger $log)
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
}
