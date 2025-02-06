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

namespace Splash\Local\Objects\Address;

use Contact;

trait MySocTrait
{
    /**
     * Build a Virtual Contact using My Soc Information
     */
    protected function getMySocAsContact(): Contact
    {
        global $db, $mysoc;

        $contact = new Contact($db);

        $contact->ref_ext = $mysoc->idprof1;

        $contact->lastname = $mysoc->name;
        $contact->phone_pro = $mysoc->phone;
        $contact->phone_mobile = $mysoc->phone_mobile;
        $contact->email = $mysoc->email;

        $contact->address = $mysoc->address;
        $contact->zip = $mysoc->zip;
        $contact->town = $mysoc->town;
        $contact->state = $mysoc->state;
        $contact->state_code = $mysoc->state_code;
        $contact->country = $mysoc->country;
        $contact->country_code = $mysoc->country_code;

        return $contact;
    }
}
