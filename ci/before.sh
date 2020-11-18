#!/bin/sh
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
echo "--> Before Script"
echo "----------------------------------------------------"

################################################################
# Remove PHP Calendar Extension
echo "Remove Duplicate Calendar Module"
rm /usr/local/etc/php/conf.d/docker-php-ext-calendar.ini
################################################################
# Install Git
echo "Install Git"
apt-get update -q                     > /dev/null
apt-get install git -y -q             > /dev/null