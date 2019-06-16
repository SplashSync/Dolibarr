echo "Setting up database"
mysql -e 'DROP DATABASE IF EXISTS travis;'
mysql -e 'CREATE DATABASE IF NOT EXISTS travis;'
mysql -e 'GRANT ALL PRIVILEGES ON travis.* TO travis@127.0.0.1;'
mysql -e 'FLUSH PRIVILEGES;'    

echo "Loading demo Data"

FILE=$DOL_BUILD_DIR/dev/initdemo/mysqldump_dolibarr_$DATA.sql    
if [ -f $FILE ]; then
   mysql -D travis < $DOL_BUILD_DIR/dev/initdemo/mysqldump_dolibarr_$DATA.sql
else
    FILE2=$DOL_BUILD_DIR/dev/initdata/mysqldump_dolibarr_$DATA.sql    
    if [ -f $FILE2 ]; then
        mysql -D travis < $DOL_BUILD_DIR/dev/initdata/mysqldump_dolibarr_$DATA.sql
    fi
fi


VERSION=`expr substr $DOL 1 1`
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

if [ "$VERSION" = "8" ];  
then 
#    echo "BugFix Update for Dolibarr 8.0.0"
#    mysql -D travis -e "ALTER TABLE llx_website_page DROP COLUMN fk_user_create;"
#    mysql -D travis -e "ALTER TABLE llx_website_page DROP COLUMN fk_user_modif;"
#    mysql -D travis -e "ALTER TABLE llx_website_page DROP COLUMN type_container;"

    echo "Database Migrations for Dolibarr 8.0"
#    sed -i '53d;60d;' $DOL_BUILD_DIR/htdocs/install/mysql/migration/7.0.0-8.0.0.sql
#    cat -n $DOL_BUILD_DIR/htdocs/install/mysql/migration/7.0.0-8.0.0.sql

    mysql -D travis < $DOL_BUILD_DIR/htdocs/install/mysql/migration/7.0.0-8.0.0.sql --force
fi 

if [ "$DOL" = "develop" ];  
then 
    echo "Database Migrations for Dolibarr Develop"
    mysql -D travis < $DOL_BUILD_DIR/htdocs/install/mysql/migration/7.0.0-8.0.0.sql --force
    mysql -D travis < $DOL_BUILD_DIR/htdocs/install/mysql/migration/8.0.0-9.0.0.sql --force
fi 

echo
