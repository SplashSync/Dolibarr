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

namespace Splash\Local\Services;

use ArrayObject;
use CommonObject;
use Exception;
use Splash\Core\SplashCore  as Splash;
use Splash\Local\Local;
use stdClass;

/**
 * MultiCompany Module Manager
 */
class MultiCompany
{
    /**
     * @var int
     */
    private static $defaultEntity = 1;

    /**
     * Cache for Entities Informations
     *
     * @var null|stdClass[]
     */
    private static $entityInfos;

    /**
     * @var mixed
     */
    private static $mcVarBackup;

    /**
     * Configure Multi-company to Use Current Entity
     *
     * @return null|int
     */
    public static function setup(): ?int
    {
        //====================================================================//
        // Detect MultiCompany Module
        if (!self::isMultiCompany()) {
            return null;
        }
        //====================================================================//
        // Detect Required to Switch Entity
        $entityId = Splash::input("Entity", INPUT_GET);
        if (empty($entityId) || ($entityId == static::$defaultEntity)) {
            return null;
        }
        //====================================================================//
        // Switch Entity
        return self::forceEntity((int) $entityId);
    }

    /**
     * Check if MultiCompany Module is Active
     *
     * @param bool $reload
     *
     * @return bool
     */
    public static function isMultiCompany(bool $reload = false): bool
    {
        static $isMultiCompany;

        if (!isset($isMultiCompany) || $reload) {
            $isMultiCompany = (bool) Local::getParameter("MAIN_MODULE_MULTICOMPANY");
        }

        return $isMultiCompany;
    }

    /**
     * Check if Marketplace Mode is Active
     *
     * @param bool $reload
     *
     * @return bool
     */
    public static function isMarketplaceMode(bool $reload = false): bool
    {
        static $isMarketplaceMode;

        if (!isset($isMarketplaceMode) || $reload) {
            $isMarketplaceMode = self::isMultiCompany() && !empty(Splash::configuration()->MarketplaceMode);
            self::$entityInfos = null;
        }

        return $isMarketplaceMode;
    }

    /**
     * Ensure Dolibarr Object Access is Allowed from this Entity
     *
     * @param CommonObject $subject Focus on a specific object
     *
     * @return bool False if Error was Found
     */
    public static function isAllowed($subject = null): bool
    {
        global $langs;

        //====================================================================//
        // Detect MultiCompany Module
        if (!self::isMultiCompany()) {
            return true;
        }
        //====================================================================//
        // Detect MultiCompany Module
        if (self::isMarketplaceMode()) {
            //====================================================================//
            // Force Current Entity
            if (isset($subject->entity) && !empty($subject->entity)) {
                self::forceEntity((int) $subject->entity);
            }

            return true;
        }
        //====================================================================//
        // Check Object
        if (is_null($subject)) {
            return false;
        }
        //====================================================================//
        // Load Object Entity
        if (isset($subject->entity) && !empty($subject->entity)) {
            $entityId = $subject->entity;
        } else {
            $entityId = $subject->getValueFrom($subject->table_element, $subject->id, "entity");
        }
        //====================================================================//
        // Check Object Entity
        if ($entityId != self::getCurrentId()) {
            $trace = (new Exception())->getTrace()[1];
            $langs->load("errors");

            return  Splash::log()->err(
                "ErrLocalTpl",
                $trace["class"],
                $trace["function"],
                html_entity_decode($langs->trans('ErrorForbidden'))
            );
        }

        return true;
    }

    /**
     * Check if Current Entity is Default Multi-company Entity
     *
     * @return bool
     */
    public static function isDefault(): bool
    {
        return self::isMultiCompany() && (self::getCurrentId() == static::$defaultEntity);
    }

    /**
     * Check if Current Entity is a Child Multi-company Entity
     *
     * @return bool
     */
    public static function isMultiCompanyChildEntity(): bool
    {
        return self::isMultiCompany() && (self::getCurrentId() != static::$defaultEntity);
    }

    /**
     * Get Multi-company Current Entity Id
     *
     * @return int
     */
    public static function getCurrentId(): int
    {
        global $conf;

        return $conf->entity;
    }

    /**
     * Configure Company for Marketplace Entity
     *
     * @param null|array|ArrayObject $list
     *
     * @return null|int
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function setupForMarketplace($list): ?int
    {
        //====================================================================//
        // Detect Marketplace Module
        if (!self::isMarketplaceMode() || empty($list)) {
            return null;
        }
        $entityInfos = self::getMultiCompanyInfos();
        //====================================================================//
        // Detect by Entity Id
        if (isset($list["entity_id"]) && in_array((int) $list["entity_id"], array_keys($entityInfos), true)) {
            return self::forceEntity($list["entity_id"]);
        }
        //====================================================================//
        // Detect by Entity Code
        if (isset($list["entity_code"]) && is_string($list["entity_code"])) {
            foreach ($entityInfos as $entityId => $entityInfo) {
                if ($entityInfo->code == $list["entity_code"]) {
                    return self::forceEntity($entityId);
                }
            }
        }
        //====================================================================//
        // Detect by Entity Name
        if (isset($list["entity_label"]) && is_string($list["entity_label"])) {
            foreach ($entityInfos as $entityId => $entityInfo) {
                if ($entityInfo->label == $list["entity_label"]) {
                    return self::forceEntity($entityId);
                }
            }
        }
        //====================================================================//
        // No Entity Identified
        return null;
    }

    /**
     * Get Web Path for Multi-company Server
     *
     * @return null|string
     */
    public static function getServerPath(): ?string
    {
        $serverRoot = (string) realpath((string) Splash::input("DOCUMENT_ROOT"));
        $prefix = self::isMultiCompanyChildEntity() ? ("?Entity=".self::getCurrentId()) : "";
        $fullPath = dirname(dirname(__DIR__))."/vendor/splash/phpcore/soap.php".$prefix;
        $relativePath = explode($serverRoot, $fullPath);

        if (is_array($relativePath) && isset($relativePath[1])) {
            return  $relativePath[1];
        }

        return   null;
    }

    /**
     * Get Multi-company Informations
     *
     * @param int $entityId
     *
     * @return null|stdClass
     */
    public static function getInfos(int $entityId): ?stdClass
    {
        self::loadMultiCompanyInfos();

        return isset(self::$entityInfos[$entityId]) ? self::$entityInfos[$entityId] : null;
    }

    /**
     * Get Multi-company Informations
     *
     * @return string
     */
    public static function getVisibleSqlIds(): string
    {
        return implode(" ,", array_keys(self::getMultiCompanyInfos()));
    }

    /**
     * Backup & Replace Multi-company Static Variable
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public static function replaceMcGlobal(): void
    {
        global $mc;

        if (self::isMarketplaceMode() && !isset(self::$mcVarBackup) && is_object($mc)) {
            self::$mcVarBackup = $mc;
            $mc = new MultiCompany();
        }
    }
    /**
     * Restore Multi-company Static Variable
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public static function restoreMcGlobal(): void
    {
        global $mc;

        if (isset(self::$mcVarBackup) && self::isMarketplaceMode()) {
            $mc = self::$mcVarBackup;
        }
    }

    /**
     * Override Get Entity Function of MultiCompany Module
     *
     * @param mixed       $element
     * @param int|string  $shared
     * @param null|object $currentobject
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getEntity($element, $shared = 1, $currentobject = null)
    {
        return self::getVisibleSqlIds();
    }

    /**
     * Load Multi-company Informations Cache
     *
     * @return stdClass[]
     */
    public static function getMultiCompanyInfos(bool $reload = false): array
    {
        self::loadMultiCompanyInfos($reload);

        return isset(self::$entityInfos) ? self::$entityInfos : array();
    }

    /**
     * Load Multi-company Informations Cache
     *
     * @return void
     */
    private static function loadMultiCompanyInfos(bool $reload = false): void
    {
        global $db;

        if (isset(self::$entityInfos) && empty($reload)) {
            return;
        }
        self::$entityInfos = array();
        $sql = "SELECT rowid as id, label, description, visible, active";
        $sql .= " FROM ".MAIN_DB_PREFIX."entity";
        $sql .= " WHERE active = 1";
        $result = $db->query($sql);
        if (!$result) {
            return;
        }
        for ($i = 0; $i < $db->num_rows($result); $i++) {
            $entity = $db->fetch_object($result);
            $entityCode = (string) iconv('UTF-8', 'ASCII//TRANSLIT', html_entity_decode($entity->label));
            $entity->code = strtoupper(str_replace(array(" ", "(", ")", "[", "]", "+", "/", "?"), "", $entityCode));

            self::$entityInfos[(int) $entity->id] = $entity;
        }
    }

    /**
     * Force Company to Use as Current Entity
     *
     * @param int $entityId
     *
     * @return int
     */
    private static function forceEntity(int $entityId): int
    {
        global $conf, $db, $user;

        //====================================================================//
        // Switch Entity
        if ($entityId && ($entityId != $conf->entity)) {
            $conf->entity = (int)   $entityId;
            $conf->setValues($db);
            $user->entity = $conf->entity;
        }

        return $conf->entity;
    }
}
