<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Core;

use CommonObject;
use Exception;
use Splash\Core\SplashCore  as Splash;
use Splash\Local\Local;

/**
 * MultiCompany Module Manager
 */
trait MultiCompanyTrait
{
    private static $DEFAULT_ENTITY    =   1;
    
    /**
     * Check if MultiCompany Module is Active
     *
     * @return bool
     */
    public function isMultiCompany()
    {
        return (bool) Local::getParameter("MAIN_MODULE_MULTICOMPANY");
    }
    
    /**
     * Ensure Dolibarr Object Access is Allowed from this Entity
     *
     * @param CommonObject $subject Focus on a specific object
     *
     * @return bool False if Error was Found
     */
    public function isMultiCompanyAllowed($subject = null)
    {
        global $langs;
        
        //====================================================================//
        // Detect MultiCompany Module
        if (!$this->isMultiCompany()) {
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
            $entityId   =   $subject->entity;
        } else {
            $entityId   =   $subject->getValueFrom($subject->table_element, $subject->id, "entity");
        }
        //====================================================================//
        // Check Object Entity
        if ($entityId != $this->getMultiCompanyEntityId()) {
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
     * Check if Current Entity is Default Multicompany Entity
     *
     * @return bool
     */
    protected function isMultiCompanyDefaultEntity()
    {
        return $this->isMultiCompany() && ($this->getMultiCompanyEntityId() == static::$DEFAULT_ENTITY);
    }
    
    /**
     * Check if Current Entity is a Child Multicompany Entity
     *
     * @return bool
     */
    protected function isMultiCompanyChildEntity()
    {
        return $this->isMultiCompany() && ($this->getMultiCompanyEntityId() != static::$DEFAULT_ENTITY);
    }
    
    /**
     * Get Multicompany Current Entity Id
     *
     * @return int
     */
    protected function getMultiCompanyEntityId()
    {
        global $conf;

        return $conf->entity;
    }

    /**
     * Configure Multicompany to Use Current Entity
     *
     * @return null|int
     */
    protected function setupMultiCompany()
    {
        global $conf, $db, $user;
        
        //====================================================================//
        // Detect MultiCompany Module
        if (!$this->isMultiCompany()) {
            return null;
        }
        //====================================================================//
        // Detect Required to Switch Entity
        if (empty(Splash::input("Entity", INPUT_GET))
                || (Splash::input("Entity", INPUT_GET) == static::$DEFAULT_ENTITY)) {
            return null;
        }
        //====================================================================//
        // Switch Entity
        $conf->entity   =   (int)   Splash::input("Entity", INPUT_GET);
        $conf->setValues($db);
        $user->entity   =   $conf->entity;

        return $conf->entity;
    }
    
    /**
     * Get Web Path for Multicompany Server
     *
     * @return null|string
     */
    protected function getMultiCompanyServerPath()
    {
        $serverRoot     =   (string) realpath((string) Splash::input("DOCUMENT_ROOT"));
        $prefix         =   $this->isMultiCompanyChildEntity() ? ("?Entity=" . $this->getMultiCompanyEntityId()) : "";
        $fullPath       =   dirname(dirname(__DIR__)) . "/vendor/splash/phpcore/soap.php" . $prefix;
        $relativePath   =   explode($serverRoot, $fullPath);
        
        if (is_array($relativePath) && isset($relativePath[1])) {
            return  $relativePath[1];
        }
        
        return   null;
    }
}
