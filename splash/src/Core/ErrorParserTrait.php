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

use Exception;
use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Push Dolibarr Errors Array to Splash Log 
 */
trait ErrorParserTrait {
    
    /**
     * @abstract    Catch Dolibarr Common Objects Errors and Push to Splash Logger
     * 
     * @param   object  $Subject    Focus on a specific object
     * 
     * @return  bool                False if Error was Found
     */
    protected function CatchDolibarrErrors( $Subject = Null ) {
        
        global $langs;
        $NoError    =   True;        
        //====================================================================//
        // Use Current Parser Object        
        if ( is_null($Subject) ) {
            $Subject    = $this->Object;
        } 
        //====================================================================//
        // Simple Error        
        if ( isset($Subject->error) && !empty($Subject->error) && is_scalar($Subject->error)) {
            $Trace = (new Exception())->getTrace()[1];
            $NoError    =    Splash::Log()->Err("ErrLocalTpl",$Trace["class"],$Trace["function"], html_entity_decode($langs->trans($Subject->error)));
        } 
        //====================================================================//
        // Array of Errors        
        if ( !isset($Subject->errors) || empty($Subject->errors)) {
            return $NoError;
        }
        $Trace = (new Exception())->getTrace()[1];
        foreach ($Subject->errors as $Error) {
            if ( is_scalar($Error) && !empty($Error) ) {
                $NoError    =    Splash::Log()->Err("ErrLocalTpl",$Trace["class"],$Trace["function"], html_entity_decode($langs->trans($Error)));
            } 
        }
        return $NoError;
    }
    
}
