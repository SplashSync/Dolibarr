echo "Upgrading Dolibarr"
# Ensure we catch errors
set +e
cd $DOL_BUILD_DIR/htdocs/install
  
if [[ $DOL_VERSION == "develop" ]]; 
then 
    echo "Upgrading for Dolibarr Develop"
    php upgrade.php 6.0.0 7.0.0 ignoredbversion 
    php upgrade2.php 6.0.0 7.0.0 MAIN_MODULE_API,MAIN_MODULE_SUPPLIERPROPOSAL 
    php step5.php 6.0.0 7.0.0 
fi  

echo


