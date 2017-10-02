echo "Upgrading Dolibarr"
# Ensure we catch errors
set +e
cd /tmp/Dolibarrhtdocs/install
  
if [[ $DOL_VERSION == "develop" ]]; 
then 
    php upgrade.php 3.5.0 3.6.0 ignoredbversion > $TRAVIS_BUILD_DIR/upgrade350360.log
    php upgrade2.php 3.5.0 3.6.0 > $TRAVIS_BUILD_DIR/upgrade350360-2.log
    php step5.php 3.5.0 3.6.0 > $TRAVIS_BUILD_DIR/upgrade350360-3.log
    php upgrade.php 3.6.0 3.7.0 ignoredbversion > $TRAVIS_BUILD_DIR/upgrade360370.log
    php upgrade2.php 3.6.0 3.7.0 > $TRAVIS_BUILD_DIR/upgrade360370-2.log
    php step5.php 3.6.0 3.7.0 > $TRAVIS_BUILD_DIR/upgrade360370-3.log
    php upgrade.php 3.7.0 3.8.0 ignoredbversion > $TRAVIS_BUILD_DIR/upgrade370380.log
    php upgrade2.php 3.7.0 3.8.0 > $TRAVIS_BUILD_DIR/upgrade370380-2.log
    php step5.php 3.7.0 3.8.0 > $TRAVIS_BUILD_DIR/upgrade370380-3.log
    php upgrade.php 3.8.0 3.9.0 ignoredbversion > $TRAVIS_BUILD_DIR/upgrade380390.log
    php upgrade2.php 3.8.0 3.9.0 > $TRAVIS_BUILD_DIR/upgrade380390-2.log
    php step5.php 3.8.0 3.9.0 > $TRAVIS_BUILD_DIR/upgrade380390-3.log
    php upgrade.php 3.9.0 4.0.0 ignoredbversion > $TRAVIS_BUILD_DIR/upgrade390400.log
    php upgrade2.php 3.9.0 4.0.0 MAIN_MODULE_API,MAIN_MODULE_SUPPLIERPROPOSAL > $TRAVIS_BUILD_DIR/upgrade390400-2.log
    php step5.php 3.9.0 4.0.0 > $TRAVIS_BUILD_DIR/upgrade390400-3.log
    php upgrade.php 4.0.0 5.0.0 ignoredbversion > $TRAVIS_BUILD_DIR/upgrade400500.log
    php upgrade2.php 4.0.0 5.0.0 MAIN_MODULE_API,MAIN_MODULE_SUPPLIERPROPOSAL > $TRAVIS_BUILD_DIR/upgrade400500-2.log
    php step5.php 4.0.0 5.0.0 > $TRAVIS_BUILD_DIR/upgrade400500-3.log
    php upgrade.php 5.0.0 6.0.0 ignoredbversion > $TRAVIS_BUILD_DIR/upgrade500600.log
    php upgrade2.php 5.0.0 6.0.0 MAIN_MODULE_API,MAIN_MODULE_SUPPLIERPROPOSAL > $TRAVIS_BUILD_DIR/upgrade500600-2.log
    php step5.php 5.0.0 6.0.0 > $TRAVIS_BUILD_DIR/upgrade500600-3.log
    php upgrade.php 6.0.0 7.0.0 ignoredbversion > $TRAVIS_BUILD_DIR/upgrade600700.log
    php upgrade2.php 6.0.0 7.0.0 MAIN_MODULE_API,MAIN_MODULE_SUPPLIERPROPOSAL > $TRAVIS_BUILD_DIR/upgrade600700-2.log
    php step5.php 6.0.0 7.0.0 > $TRAVIS_BUILD_DIR/upgrade600700-3.log's
fi  


cd -
set +e
echo
#cat $TRAVIS_BUILD_DIR/upgrade400500-2.log
#cat /tmp/dolibarr_install.log

