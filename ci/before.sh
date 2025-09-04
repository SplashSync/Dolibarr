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
echo "--> Before Script"
echo "----------------------------------------------------"

################################################################
# Remove PHP Calendar Extension
echo "Remove Duplicate Calendar Module"
rm /usr/local/etc/php/conf.d/docker-php-ext-calendar.ini

################################################################
# Php => Force Memory Limit
echo "Composer => Force Memory Limit";
echo "memory_limit = -1" >> /usr/local/etc/php/conf.d/memory_limit.ini;
rm /usr/local/etc/php/conf.d/dolibarr-php.ini;

################################################################
# Check if we're on Debian and update sources.list for archived versions
if [ -f /etc/debian_version ]; then
    DEBIAN_VERSION=$(cat /etc/debian_version | cut -d. -f1)
    if grep -q "buster" /etc/apt/sources.list 2>/dev/null; then
        echo "Updating Debian Buster repositories to archive..."
        sed -i 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' /etc/apt/sources.list
        sed -i 's|http://security.debian.org/debian-security|http://archive.debian.org/debian-security|g' /etc/apt/sources.list
        sed -i '/buster-updates/d' /etc/apt/sources.list
        echo "deb http://archive.debian.org/debian buster main" > /etc/apt/sources.list
        echo "deb http://archive.debian.org/debian-security buster/updates main" >> /etc/apt/sources.list
    fi
fi

################################################################
# Install Git
echo "Install Git"
apt-get update -q                     > /dev/null
apt-get install git wget -y -q        > /dev/null