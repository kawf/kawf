#!/bin/bash

apache_user=www-data
apache_group=www-data

mysql -h$DB_HOST -p$MYSQL_ROOT_PASSWORD -e "CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'localhost';"
mysql -h$DB_HOST -p$MYSQL_ROOT_PASSWORD -e "CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%';"
mysql -h$DB_HOST -p$MYSQL_ROOT_PASSWORD -e "SET PASSWORD FOR '${MYSQL_USER}'@'localhost' = PASSWORD('${MYSQL_PASSWORD}');"
mysql -h$DB_HOST -p$MYSQL_ROOT_PASSWORD -e "SET PASSWORD FOR '${MYSQL_USER}'@'%' = PASSWORD('${MYSQL_PASSWORD}');"
mysql -h$DB_HOST -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE};"
mysql -h$DB_HOST -p$MYSQL_ROOT_PASSWORD -e "GRANT ALL ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'localhost';"
mysql -h$DB_HOST -p$MYSQL_ROOT_PASSWORD -e "GRANT ALL ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';"
mysql -h$DB_HOST -p$MYSQL_ROOT_PASSWORD -e "FLUSH PRIVILEGES;"

# error in script:
# Base table or view already exists: 1050 Table 'acl_ips' already exists
# php /var/www/html/tools/initial.php
