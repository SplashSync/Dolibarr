echo "Setting up database"
mysql -e 'DROP DATABASE IF EXISTS travis;'
mysql -e 'CREATE DATABASE IF NOT EXISTS travis;'
mysql -e 'GRANT ALL PRIVILEGES ON travis.* TO travis@127.0.0.1;'
mysql -e 'FLUSH PRIVILEGES;'    

echo "Loading demo Data"

FILE=$DOL_BUILD_DIR/dev/initdemo/mysqldump_dolibarr_$DATA_VERSION.sql    
if [ -f $FILE ]; then
   mysql -D travis < $DOL_BUILD_DIR/dev/initdemo/mysqldump_dolibarr_$DATA_VERSION.sql
else
    FILE2=$DOL_BUILD_DIR/dev/initdata/mysqldump_dolibarr_$DATA_VERSION.sql    
    if [ -f $FILE2 ]; then
        mysql -D travis < $DOL_BUILD_DIR/dev/initdata/mysqldump_dolibarr_$DATA_VERSION.sql
    fi
fi


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

if [ "$DOL_VERSION" = "develop" ];  
then 
    echo "Database Migrations for Dolibarr Develop"
    mysql -D travis < $DOL_BUILD_DIR/install/mysql/migration/7.0.0-8.0.0.sql
fi 

echo
