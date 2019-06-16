echo "Upgrading Dolibarr"
# Ensure we catch errors
set +e
cd $DOL_BUILD_DIR/htdocs/install
  
if [[ $DOL == "develop" ]]; 
then 
    echo "Upgrading for Dolibarr Develop"
    php upgrade.php  7.0.0 8.0.0 ignoredbversion                                > $TRAVIS_BUILD_DIR/upgrade700800.log
    php upgrade2.php 7.0.0 8.0.0 MAIN_MODULE_API,MAIN_MODULE_SUPPLIERPROPOSAL   > $TRAVIS_BUILD_DIR/upgrade700800-2.log
    php step5.php    7.0.0 8.0.0                                                > $TRAVIS_BUILD_DIR/upgrade700800-3.log
fi  

echo
