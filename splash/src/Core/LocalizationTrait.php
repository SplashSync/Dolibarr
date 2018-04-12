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

namespace   Splash\Local\Core;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Access to Dolibarr States & Countries
 */
trait LocalizationTrait
{
    
    /**
     *  @abstract   Search for a Country by Code
     *  @return     string  $code         Country Iso Code
     *  @return     int                   Country Dolibarr Id, else 0
     */
    protected function getCountryByCode($code)
    {
        global $db;
        if (Splash::local()->DolVersionCmp("3.7.0") >= 0) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/ccountry.class.php';
            $pays = new \Ccountry($db);
            if ($pays->fetch(null, $code) > 0) {
                return $pays->id;
            }
        } else {
            require_once DOL_DOCUMENT_ROOT . '/core/class/cpays.class.php';
            $pays = new \Cpays($db);
            if ($pays->fetch(null, $code) > 0) {
                return $pays->id;
            }
        }
        return false;
    }
    
    /**
     *      @abstract   Search For State Dolibarr Id using State Code & Country Id
     *      @param      string  $StateCode          State Iso Code
     *      @return     string  $CountryId          Country Dolibarr Id
     *
     *      @return     int                   State Dolibarr Id, else 0
     * @deprecated since version 1.3.0
     *
     */


    protected function getStateByCode($StateCode, $CountryId)
    {
        global $db;
        
        if (empty($CountryId)) {
            return false;
        }
        
        //====================================================================//
        // Select State Id &œ Code
        $sql =  "SELECT d.rowid as id, d.code_departement as code";
        $sql .= " FROM ".MAIN_DB_PREFIX ."c_departements as d, ";
        if (Splash::local()->DolVersionCmp("3.7.0") >= 0) {
            $sql .= MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_country as p";
        } else {
            $sql .= MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_pays as p";
        }
        //====================================================================//
        // Search by Country & State Code
        $sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid";
        $sql .= " AND p.rowid = '".$CountryId."'";
        $sql .= " AND d.code_departement = '".$StateCode."'";
        
        //====================================================================//
        // Execute final request
        $resql = $db->query($sql);
        if (empty($resql)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, $db->lasterror());
        }
        
        if ($db->num_rows($resql) == 1) {
            return $db->fetch_object($resql)->id;
        }
                
        return false;
    }
}
