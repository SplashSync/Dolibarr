echo "Setting up database"
mysql -e 'DROP DATABASE IF EXISTS travis;'
mysql -e 'CREATE DATABASE IF NOT EXISTS travis;'
mysql -e 'GRANT ALL PRIVILEGES ON travis.* TO travis@127.0.0.1;'
mysql -e 'FLUSH PRIVILEGES;'    

echo "Loading demo Data"
mysql -D travis < $DOL_BUILD_DIR/dev/initdemo/mysqldump_dolibarr_$DATA_VERSION.sql

VERSION=`expr substr $DOL_VERSION 1 1`
if [ "$VERSION" = "5" ];  
then 
    echo "BugFix Update for Dolibarr 5.0.0"
    mysql -D travis -e "ALTER TABLE llx_socpeople CHANGE zip zip varchar(25) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;"
fi 

if [ "$VERSION" = "4" ];  
then 
    echo "BugFix Update for Dolibarr 4.0.0"
    mysql -D travis -e "ALTER TABLE llx_socpeople CHANGE zip zip varchar(25) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;"
fi 

echo
