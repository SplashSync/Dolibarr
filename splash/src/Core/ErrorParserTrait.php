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

namespace Splash\Local\Core;

use Exception;
use Splash\Core\SplashCore as Splash;

/**
 * Push Dolibarr Errors Array to Splash Log
 */
trait ErrorParserTrait
{
    /**
     * Catch Dolibarr Common Objects Errors and Push to Splash Logger
     *
     * @param null|object $subject Focus on a specific object
     *
     * @return bool False if Error was Found
     */
    protected function catchDolibarrErrors(object $subject = null): bool
    {
        //====================================================================//
        // Use Current Parser Object
        if (is_null($subject)) {
            $subject = $this->object ?? null;
        }

        //====================================================================//
        // Safety Check
        if (!is_object($subject)) {
            return true;
        }

        //====================================================================//
        // Catch Database Errors
        $this->catchDatabaseErrors($subject);

        return $this->catchSimpleErrors($subject) && $this->catchArrayErrors($subject);
    }

    /**
     * Catch Dolibarr Common Objects Simple Errors
     *
     * @param null|object $subject Focus on a specific object
     *
     * @return bool False if Error was Found
     */
    private function catchSimpleErrors(object $subject = null): bool
    {
        global $langs;

        //====================================================================//
        // Simple Error
        if (isset($subject->error) && !empty($subject->error) && is_scalar($subject->error)) {
            $trace = (new Exception())->getTrace()[2];

            return  Splash::log()->err(
                "ErrLocalTpl",
                $trace["class"] ?? "",
                $trace["function"],
                html_entity_decode($langs->trans($subject->error))
            );
        }

        return true;
    }

    /**
     * Catch Dolibarr Common Objects Array Errors
     *
     * @param null|object $subject Focus on a specific object
     *
     * @return bool False if Error was Found
     */
    private function catchArrayErrors(object $subject = null): bool
    {
        global $langs;

        $noError = true;
        //====================================================================//
        // Array of Errors
        if (empty($subject->errors)) {
            return true;
        }
        $trace = (new Exception())->getTrace()[2];
        foreach ($subject->errors as $error) {
            if (is_scalar($error) && !empty($error)) {
                $noError = Splash::log()->err(
                    "ErrLocalTpl",
                    $trace["class"] ?? "",
                    $trace["function"],
                    html_entity_decode($langs->trans($error))
                );
            }
        }

        return $noError;
    }

    /**
     * Catch Dolibarr Common Objects Simple Errors
     *
     * @param null|object $subject Focus on a specific object
     *
     * @return void
     */
    private function catchDatabaseErrors(object $subject = null): void
    {
        global $db;

        //====================================================================//
        // DataBase Error
        if (isset($subject->error) && !empty($subject->error) && !empty($db->lasterror())) {
            $trace = (new Exception())->getTrace()[2];
            Splash::log()->err(
                "ErrLocalTpl",
                $trace["class"] ?? "",
                $trace["function"],
                html_entity_decode($db->lasterror())
            );
        }
    }
}
