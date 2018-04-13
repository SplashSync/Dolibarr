<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace Splash\Local\Objects\Order;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Customer Orders Items Fields
 */
trait ItemsTrait
{

    /**
     *  @abstract     Create a New Line Item
     *
     *  @return         OrderLine
     */
    protected function createItem()
    {
        global $db;
        
        $Item   = new  \OrderLine($db);
        
        //====================================================================//
        // Pre-Setup of Item
        $Item->fk_commande = $this->Object->id;
        
        //====================================================================//
        // Pre-Setup of Item with Common Values & Insert
        return $this->insertItem($Item);
    }
    
    /**
     *  @abstract     Delete a Line Item
     *
     *  @param        OrderLine     $OrderLine  Order OrderLine Item
     *
     *  @return         bool
     */
    protected function deleteItem($OrderLine)
    {
        global $user;
        //====================================================================//
        // Force Order Status To Draft
        $this->Object->statut         = 0;
        $this->Object->brouillon      = 1;
        //====================================================================//
        // Prepare Args
        $Arg1 = ( Splash::local()->dolVersionCmp("5.0.0") > 0 ) ? $user : $OrderLine->id;
        $Arg2 = ( Splash::local()->dolVersionCmp("5.0.0") > 0 ) ? $OrderLine->id : null;
        //====================================================================//
        // Perform Line Delete
        if ($this->Object->deleteline($Arg1, $Arg2) <= 0) {
            return $this->catchDolibarrErrors();
        }
        return true;
    }
}
