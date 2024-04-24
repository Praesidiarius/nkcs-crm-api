#!/bin/bash

if test -f /docker-entrypoint-initdb.d/.env; then
  . /docker-entrypoint-initdb.d/.env
else
  echo "Config file missing. please create a .env.local file directory"
  exit
fi

SQL_TEMPLATE_DIR="/home/sql"
USER_USERNAME=${2:-dev}
# password hash equals "test"
USER_PASSWORD=${3:-"\$2y\$13\$cMyLSyniGkyrM2IhCm68vejEqypYm6vGCsngOgc4VARcSeky2yAw6"}
USER_FIRSTNAME=${4-"Dev"}
USER_LASTNAME=${5-"System"}
USER_EMAIL=${6-"dev@example.com"}

# make sure sql template directory exists before running the script
if [ ! -d "${SQL_TEMPLATE_DIR}" ]; then
  echo "SQL Template Directory not found: ${SQL_TEMPLATE_DIR}"
  exit
fi

mysql -u${DB_USER} -p${DB_PASS} -e "DROP DATABASE IF EXISTS ${DB_NAME}"
mysql -u${DB_USER} -p${DB_PASS} -e "CREATE DATABASE ${DB_NAME} /*\!40100 DEFAULT CHARACTER SET utf8 */;"

CREATE_USER_SQL=$(cat "${SQL_TEMPLATE_DIR}"/db_live_user_template.sql)
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##USERNAME##|${USER_USERNAME}|g")
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##PASSWORD##|${USER_PASSWORD}|g")
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##FIRST##|${USER_FIRSTNAME}|g")
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##LAST##|${USER_LASTNAME}|g")
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##EMAIL##|${USER_EMAIL}|g")

# rebuild db
# core db schema
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/db_live.sql

# item module basic
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/item/variant_basic.sql
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/item/core_extensions.sql

# contact module basic
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/contact/variant_basic.sql
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/contact/core_extensions.sql
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/contact/extensions/history.sql

# contact signup extension
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/contact/extensions/signup.sql

# job module basic
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/job/variant_basic.sql
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/job/core_extensions.sql

# item voucher extension
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/item/extensions/voucher.sql

# add user
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" -e "${CREATE_USER_SQL}"