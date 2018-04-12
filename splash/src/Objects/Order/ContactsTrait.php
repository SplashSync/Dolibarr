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
 * @abstract    Dolibarr Customer Orders Address Fields
 */
trait ContactsTrait
{

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildContactsFields()
    {
        
        global $langs;
        
        //====================================================================//
        // Billing Address
        $this->fieldsFactory()->Create(self::Objects()->Encode("Address", SPL_T_ID))
                ->Identifier("BILLING")
                ->Name($langs->trans("TypeContact_commande_external_BILLING"))
                ->MicroData("http://schema.org/Order", "billingAddress");
        
        //====================================================================//
        // Shipping Address
        $this->fieldsFactory()->Create(self::Objects()->Encode("Address", SPL_T_ID))
                ->Identifier("SHIPPING")
                ->Name($langs->trans("TypeContact_commande_external_SHIPPING"))
                ->MicroData("http://schema.org/Order", "orderDelivery");
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getContactsFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'SHIPPING':
            case 'BILLING':
                $ContactsArray   =  $this->Object->liste_contact(-1, 'external', 1, $FieldName);
                if (!empty($ContactsArray)) {
                    $this->Out[$FieldName] = self::Objects()->Encode("Address", array_shift($ContactsArray));
                } else {
                    $this->Out[$FieldName] = null;
                }
                break;
            
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }

    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    protected function setContactsFields($FieldName, $Data)
    {
        switch ($FieldName) {
            case 'SHIPPING':
            case 'BILLING':
                //====================================================================//
                // Load Current Contact
                $ContactsArray   =  $this->Object->liste_contact(-1, 'external', 0, $FieldName);
                $Current    =   empty($ContactsArray) ? null : array_shift($ContactsArray);

                //====================================================================//
                // Compare to Expected
                $Expected = self::Objects()->Id($Data);
                if ($Current && ($Current["id"] == $Expected)) {
                    break;
                }
                //====================================================================//
                // Delete if Changed
                if ($Current && ($Current["id"] != $Expected)) {
                    $this->Object->delete_contact($Current["rowid"]);
                }
                //====================================================================//
                // Add New Contact
                $this->Object->add_contact($Expected, $FieldName, 'external');
                break;
            
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
