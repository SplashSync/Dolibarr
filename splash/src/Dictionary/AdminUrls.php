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

namespace Splash\Local\Dictionary;

/**
 * Dictionary for Dolibarr Back-Office Card Urls, Relative to Dolibarr Root Url
 */
class AdminUrls
{
    /**
     * ThirdParty Card
     */
    const SOCIETE = "/societe/card.php?socid=";

    /**
     * Contact Card
     */
    const CONTACT = "/contact/card.php?id=";

    /**
     * Product Card
     */
    const PRODUCT = "/product/card.php?id=";

    /**
     * Customer Order Card
     */
    const COMMANDE = "/commande/card.php?id=";

    /**
     * Customer Invoice & Credit Note Card
     */
    const FACTURE = "/compta/facture/card.php?id=";

    /**
     * Card Urls Indexed by Dolibarr Object Element
     *
     * @var array<string, string>
     */
    const BY_ELEMENT = array(
        "societe" => self::SOCIETE,
        "contact" => self::CONTACT,
        "product" => self::PRODUCT,
        "commande" => self::COMMANDE,
        "facture" => self::FACTURE,
    );
}
