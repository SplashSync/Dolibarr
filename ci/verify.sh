
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

set -e

################################################################################
# Docker Compose Container you want to check
CONTAINERS="dol-21,dol-20,dol-18,dol-17,dol-16"
WORKDIR="/var/www/html/custom"
################################################################################
# Start Docker Compose Stack
echo '===> Start Docker Stack'
docker compose up -d

######################################
# Run Grumphp Test Suites Locally
php splash/vendor/bin/grumphp run --testsuite=travis
php splash/vendor/bin/grumphp run --testsuite=csfixer

######################################
# Walk on Docker Compose Container
for ID in $(echo $CONTAINERS | tr "," "\n")
do
    echo "----------------------------------------------------"
    echo "===> CHECKS ON $ID"
    echo "----------------------------------------------------"
    # Ensure Git is Installed
    docker compose exec $ID apt update
    docker compose exec $ID apt install git -y
    # Run Grumphp Test Suites
    docker compose exec -w $WORKDIR $ID php splash/vendor/bin/grumphp run --testsuite=travis
    docker compose exec -w $WORKDIR $ID php splash/vendor/bin/grumphp run --testsuite=phpstan
done
