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
// PHP CS Overrides
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Files.SideEffects

//====================================================================//
// Splash Module Definitions
include_once(dirname(__FILE__, 3)."/_conf/defines.inc.php");

use Splash\Local\Models\AbstractTrigger;
use Splash\Local\Objects;

/**
 * Splash Module Changes Detection Triggers
 */
class InterfaceSplash extends AbstractTrigger
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
     * Detect Object Changes
     *
     * @param string $action
     * @param object $object
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function doActionDetection(string $action, object $object): bool
    {
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

        return (bool) $doCommit;
    }

    /**
     * Detect Secondary Object Changes
     *
     * @param string $action Event Code
     * @param object $object Impacted Objet
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doSecondaryActionDetection(string $action, object $object): bool
    {
        //====================================================================//
        // TRIGGER ACTION FOR : Address / Contact
        //====================================================================//
        return $this->doAddressSecondaryCommit($action, $object);
    }
}
