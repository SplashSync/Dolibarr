#!/bin/bash
################################################################################
#
#  This file is part of SplashSync Project.
# 
#  Copyright (C) Splash Sync <www.splashsync.com>
# 
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
# 
#  For the full copyright and license information, please view the LICENSE
#  file that was distributed with this source code.
# 
#  @author Bernard Paquier <contact@splashsync.com>
#
################################################################################

################################################################################
# Wait For MySQL Database
while ! mysqladmin ping -h"$DOLI_DB_HOST" --silent; do
    echo "Waiting that SQL database is up ..."
    sleep 1
done

################################################################################
# Create Database
echo "Create Database if No Exists"
mysql -h $DOLI_DB_HOST -pdolibarr -e "CREATE DATABASE IF NOT EXISTS $DOLI_DB_NAME;"

################################################################################
# Generic Install
echo "Execute Tuxgasy Generic Install..."
bash /usr/local/bin/docker-run.sh

################################################################################
# Ensure Git is Installed
if ! [ -x "$(command -v git)" ];
then
    echo "Install Git"
    apt update && apt install -y git
fi

################################################################################
# Force Php Configuration
echo "memory_limit = -1" >> /usr/local/etc/php/conf.d/memory_limit.ini;
rm /usr/local/etc/php/conf.d/dolibarr-php.ini;

################################################################################
# Setup Splash Module
################################################################################

echo "Updating Dolibarr Custom folder ownership..."
chmod -R 777  /var/www/html/custom

echo "Execute PHP Bootstrap Script"
php /var/www/html/custom/docker/bootstrap.php

echo "Add A Warehouse"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "INSERT INTO llx_entrepot (ref, datec, entity, description, statut) VALUES ('Default', CURRENT_TIMESTAMP, '1', 'Default Warehouse', '1');"

echo "Configure Splash Module" 
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '$SPLASH_WS_ID' WHERE name = 'SPLASH_WS_ID';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '$SPLASH_WS_KEY' WHERE name = 'SPLASH_WS_KEY';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '1' WHERE name = 'SPLASH_WS_EXPERT';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '$SPLASH_WS_HOST' WHERE name = 'SPLASH_WS_HOST';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = 'SOAP' WHERE name = 'SPLASH_WS_METHOD';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '1' WHERE name = 'SPLASH_USER';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = 'fr_FR' WHERE name = 'SPLASH_LANG';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = 'a:2:{i:0;s:5:\"fr_BE\";i:1;s:5:\"en_US\";}' WHERE name = 'SPLASH_LANGS';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '1' WHERE name = 'SPLASH_STOCK';"

if [ -d /var/overrides ]; then
  echo "Import Code Overrides"
  cp -Rf /var/overrides/* /var/www/html
fi

################################################################################
# Move to Module Dir
cd /var/www/html/custom/

echo "Serving Dolibarr..."
exec "apache2-foreground"