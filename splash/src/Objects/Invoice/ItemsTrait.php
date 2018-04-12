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

namespace Splash\Local\Objects\Invoice;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Customer Invoice Items Fields
 */
trait ItemsTrait
{
    
   
    /**
     *  @abstract     Create a New Line Item
     *
     *  @return         FactureLigne
     */
    protected function createItem()
    {
        global $db;
        
        $Item   = new  \FactureLigne($db);
        
        //====================================================================//
        // Pre-Setup of Item
        $Item->fk_facture = $this->Object->id;
        
        //====================================================================//
        // Pre-Setup of Item with Common Values & Insert
        return $this->insertItem($Item);
    }
    
    /**
     *  @abstract     Delete a Line Item
     *
     *  @param        OrderLine     $FactureLigne  Order FactureLigne Item
     *
     *  @return         bool
     */
    protected function deleteItem($FactureLigne)
    {
//        global $user;
//        //====================================================================//
//        // Prepare Args
//        $Arg1 = ( Splash::local()->DolVersionCmp("5.0.0") > 0 ) ? $user : $FactureLigne->id;
//        $Arg2 = ( Splash::local()->DolVersionCmp("5.0.0") > 0 ) ? $FactureLigne->id : Null;
        //====================================================================//
        // Debug Mode => Force Allow Delete
        if (defined("SPLASH_DEBUG") && SPLASH_DEBUG) {
            //====================================================================//
            // Force Invoice Status To Draft
            $this->Object->brouillon      = 1;
        }
        //====================================================================//
        // Perform Line Delete
        if ($this->Object->deleteline($FactureLigne->id) <= 0) {
            return $this->catchDolibarrErrors();
        }
        return true;
    }
}
