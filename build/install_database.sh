echo "Setting up database"
mysql -e 'DROP DATABASE IF EXISTS travis;'
mysql -e 'CREATE DATABASE IF NOT EXISTS travis;'
mysql -e 'GRANT ALL PRIVILEGES ON travis.* TO travis@127.0.0.1;'
mysql -e 'FLUSH PRIVILEGES;'    
mysql -D travis < dev/initdemo/mysqldump_dolibarr_3.5.0.sql
echo
