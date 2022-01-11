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

global $config, $finder;

$sdkPath = $_SERVER['PWD']."/splash/vendor/badpixxel/php-sdk/phpcs/";
$dirs = array(
    $_SERVER['PWD']."/ci",
    $_SERVER['PWD']."/docker",
    $_SERVER['PWD']."/grumphp",
    $_SERVER['PWD']."/splash",
);

include_once $sdkPath."headers/splashsync.php";

$finder = PhpCsFixer\Finder::create()
    ->in($dirs)
    ->exclude('vendor')
    ->exclude('tests/Fixtures')
    ->exclude('var');

include_once $sdkPath."cs.rules.php";

return $config;
