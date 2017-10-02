export CONF_FILE=htdocs/conf/conf.php
echo "Setting up Dolibarr $CONF_FILE"
echo '<?php ' > $CONF_FILE
echo '$'dolibarr_main_url_root=\'http://127.0.0.1\'';' >> $CONF_FILE
echo '$'dolibarr_main_document_root=\'$TRAVIS_BUILD_DIR/htdocs\'';' >> $CONF_FILE
echo '$'dolibarr_main_data_root=\'$TRAVIS_BUILD_DIR/documents\'';' >> $CONF_FILE
echo '$'dolibarr_main_db_host=\'127.0.0.1\'';' >> $CONF_FILE
echo '$'dolibarr_main_db_name=\'travis\'';' >> $CONF_FILE
echo '$'dolibarr_main_db_user=\'travis\'';' >> $CONF_FILE
echo '$'dolibarr_main_db_type=\'mysqli\'';' >> $CONF_FILE
echo '$'dolibarr_main_authentication=\'dolibarr\'';' >> $CONF_FILE
cat $CONF_FILE
echo

echo "Create documents directory and set permissions"
# and admin/temp subdirectory needed for unit tests
mkdir -p documents/admin/temp
echo "first line" > documents/dolibarr.log
echo

