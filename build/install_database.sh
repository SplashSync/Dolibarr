echo "Setting up database"
mysql -e 'DROP DATABASE IF EXISTS travis;'
mysql -e 'CREATE DATABASE IF NOT EXISTS travis;'
mysql -e 'GRANT ALL PRIVILEGES ON travis.* TO travis@127.0.0.1;'
mysql -e 'FLUSH PRIVILEGES;'    

echo "Loading demo Data"
mysql -D travis < $DOL_BUILD_DIR/dev/initdemo/mysqldump_dolibarr_$DATA_VERSION.sql

echo
