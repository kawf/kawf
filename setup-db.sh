#!/bin/bash

db=kawf
db_user=kawf

apache_user=www-data
apache_group=www-data

#dry=echo
#verbose=echo
verbose=true

if command -v apg > /dev/null 2>&1; then
    db_password=$(apg -c /dev/random -a 1 -n 1 -m 16 -M NCL)
else
    echo >&2 "FATAL ERROR: Can't find apg. Please install it!"
    exit 1
fi

if [ ! -r config/config-local.inc ]; then
    echo >&2 "FATAL ERROR: You do not have read permissions to config/config-local.inc or it does not exist!"
    exit 1
fi

$verbose creating db user \"${db_user}\"
$dry sudo mysql -e "CREATE USER IF NOT EXISTS '${db_user}'@'localhost';"
$verbose setting password for \"${db_user}\"
$dry sudo mysql -e "SET PASSWORD FOR '${db_user}'@'localhost' = PASSWORD('${db_password}');"

$verbose creating db \"${db}\"
#$dry sudo mysql -e "DROP DATABASE IF EXISTS ${db};"
$dry sudo mysql -e "CREATE DATABASE IF NOT EXISTS ${db};"
$verbose giving perms for db \"${db}\" to user \"${db_user}\"
$dry sudo mysql -e "GRANT ALL ON ${db}.* TO '${db_user}'@'localhost';"

$dry sudo mysql -e "FLUSH PRIVILEGES;"

newpass="\$sql_password = \"${db_password}\";"
$verbose "storing password in config/config-local.inc"
$dry sudo sed -i.bak 's/^\$sql_password.*/'"${newpass}"'/' config/config-local.inc
$dry sudo chown ${apache_user}.${apache_group} config/config-local.inc
$dry sudo chmod 440 config/config-local.inc

if [ ! -r config/config-local.inc ]; then
    echo >&2 "Warning: You do not have read permissions to config/config-local.inc!"
    exit 1
fi

echo Now run tools/initial.php!

