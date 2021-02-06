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

namespace   Splash\Local\Core;

use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;

/**
 * Access to Dolibarr States & Countries
 */
trait LocalizationTrait
{
    /**
     * Search for a Country by Code
     *
     * @param mixed $code
     *
     * @return false|int Country Dolibarr Id, else 0
     */
    protected function getCountryByCode($code)
    {
        global $db;
        require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';
        $pays = new \Ccountry($db);
        if ($pays->fetch(0, $code) > 0) {
            return $pays->id;
        }

        return false;
    }

    /**
     * Search For State Dolibarr Id using State Code & Country Id
     *
     * @param string $stateCode State Iso Code
     * @param mixed  $countryId
     *
     * @return false|int State Dolibarr Id, else False
     *
     * @deprecated since version 1.3.0
     */
    protected function getStateByCode($stateCode, $countryId)
    {
        global $db;

        if (empty($countryId)) {
            return false;
        }

        //====================================================================//
        // Select State Id &œ Code
        $sql = "SELECT d.rowid as id, d.code_departement as code";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_departements as d, ";
        if (Local::dolVersionCmp("3.7.0") >= 0) {
            $sql .= MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_country as p";
        } else {
            $sql .= MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_pays as p";
        }
        //====================================================================//
        // Search by Country & State Code
        $sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid";
        $sql .= " AND p.rowid = '".$countryId."'";
        $sql .= " AND d.code_departement = '".$stateCode."'";

        //====================================================================//
        // Execute final request
        $resql = $db->query($sql);
        if (empty($resql)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, $db->lasterror());
        }

        if (1 == $db->num_rows($resql)) {
            return $db->fetch_object($resql)->id;
        }

        return false;
    }
}
