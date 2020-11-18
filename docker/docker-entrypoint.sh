#!/bin/sh
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

echo "Create Database if No Exists" 
mysql -h $DOLI_DB_HOST -pdolibarr -e "CREATE DATABASE IF NOT EXISTS $DOLI_DB_NAME;"

echo "Execute Monogramm Generic Install..."
curl -s https://raw.githubusercontent.com/Monogramm/docker-dolibarr/master/docker-entrypoint.sh | sh

echo "Updating Dolibarr Custom folder ownership..."
chmod -R 777  /var/www/html/custom

echo "Install Git" 
apt update && apt install -y git

if [ ! -f /var/www/documents/install.lock ]; then

	echo "Wait for MySql Container to Start"
	sleep 10 

	echo "Complete Dolibarr Install" 
	cd /var/www/html/install/
	php step2.php set
	php step5.php 0 0 fr_FR set admin admin admin 

	echo "Configure Dolibarr Company" 
	mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = 'Dolibarr for Splash' WHERE name = 'MAIN_INFO_SOCIETE_NOM';"
	mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '1:FR:France' WHERE name = 'MAIN_INFO_SOCIETE_COUNTRY';"

	echo "Add A Warehouse" 
	mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "INSERT INTO llx_entrepot (ref, datec, entity, description, statut) VALUES ('Default', CURRENT_TIMESTAMP, '1', 'Default Warehouse', '1');"

fi

echo "Configure Splash Module" 
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '$SPLASH_WS_ID' WHERE name = 'SPLASH_WS_ID';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '$SPLASH_WS_KEY' WHERE name = 'SPLASH_WS_KEY';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '1' WHERE name = 'SPLASH_WS_EXPERT';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '$SPLASH_WS_HOST' WHERE name = 'SPLASH_WS_HOST';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = 'SOAP' WHERE name = 'SPLASH_WS_METHOD';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '1' WHERE name = 'SPLASH_USER';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = 'fr_FR' WHERE name = 'SPLASH_LANG';"
mysql -h $DOLI_DB_HOST -D $DOLI_DB_NAME -pdolibarr -e "UPDATE llx_const SET value = '1' WHERE name = 'SPLASH_STOCK';"

if [ -d /var/overrides ]; then
  echo "Import Code Overrides"
  cp -Rf /var/overrides/* /var/www/html
fi

echo "Serving Dolibarr..."
exec "apache2-foreground"