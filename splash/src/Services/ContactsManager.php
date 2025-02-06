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

namespace Splash\Local\Services;

use CommonObject;
use Splash\Local\Dictionary\StaticContactTypes;
use Splash\Local\Objects\Address;

/**
 * Manage Access to Objects Connected Contacts
 */
class ContactsManager
{
    /**
     * Get ID of First Contacts Attached to this Object
     */
    public static function getFirstContactId(CommonObject $object, string $contactType): ?string
    {
        //====================================================================//
        // Detect Contact using Connected Contacts IDs
        $contactIds = self::getAllContactIds($object, $contactType);
        if (!empty($contactIds)) {
            return (string) array_shift($contactIds);
        }

        //====================================================================//
        // Use My Soc as default Contact for Shipping
        if ((StaticContactTypes::SHIPPING == $contactType)) {
            if (ShippingMethods::isMySocMethod($object->shipping_method_id)) {
                return Address::MY_SOC_ID;
            }
        }

        return null;
    }

    /**
     * Get IDs List of All Contacts Attached to this Object
     *
     * @return int[]
     */
    public static function getAllContactIds(CommonObject $object, string $contactType): array
    {
        /** @var int|int[] $contacts  */
        $contacts = $object->liste_contact(-1, 'external', 1, $contactType);

        return is_array($contacts) ? $contacts : array();
    }
}
