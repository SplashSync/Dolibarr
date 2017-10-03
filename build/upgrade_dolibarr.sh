echo "Upgrading Dolibarr"
# Ensure we catch errors
set +e
cd $DOL_BUILD_DIR/htdocs/install
  
if [[ $DOL_VERSION == "develop" ]]; 
then 
    echo "Upgrading for Dolibarr Develop"
    php upgrade.php 6.0.0 7.0.0 ignoredbversion                                 > $TRAVIS_BUILD_DIR/upgrade600700.log
    php upgrade2.php 6.0.0 7.0.0 MAIN_MODULE_API,MAIN_MODULE_SUPPLIERPROPOSAL   > $TRAVIS_BUILD_DIR/upgrade600700-2.log
    php step5.php 6.0.0 7.0.0                                                   > $TRAVIS_BUILD_DIR/upgrade600700-3.log
fi  

echo
