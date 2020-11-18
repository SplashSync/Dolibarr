#!/bin/bash
################################################################################
#
# Copyright (C) 2020 BadPixxel <www.badpixxel.com>
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
#
################################################################################

echo "----------------------------------------------------"
echo "--> Module Install"
echo "----------------------------------------------------"

################################################################
# Copy Contents
echo "Copy Splash Module to Dolibarr folder"
cp -Rf $CI_PROJECT_DIR/*                    /var/www/html/custom/
cp -Rf $CI_PROJECT_DIR/.git/                /var/www/html/custom/.git/
ls -l     /var/www/html/custom/

################################################################
# Install Splash Configuration File
echo "Install Splash Configuration File"
cp $CI_PROJECT_DIR/travis/splash.json       /var/www/html/conf/splash.json

echo "Move to Custom Path"
cd  /var/www/html/custom
