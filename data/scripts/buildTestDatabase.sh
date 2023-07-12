#!/bin/sh

# password hash equals "test"
DEV_PASSWORD="\$2y\$13\$cMyLSyniGkyrM2IhCm68vejEqypYm6vGCsngOgc4VARcSeky2yAw6"
DB_NAME="nkcs_test"
DB_USER="root"
DB_PASS="root"
SQL_TEMPLATE_DIR="./../sql"

# make sure sql template directory exists before running the script
if [ ! -d "${SQL_TEMPLATE_DIR}" ]; then
  echo "SQL Template Directory not found: ${SQL_TEMPLATE_DIR}"
  exit
fi

mysql -u${DB_USER} -p${DB_PASS} -e "DROP DATABASE IF EXISTS ${DB_NAME}"
mysql -u${DB_USER} -p${DB_PASS} -e "CREATE DATABASE ${DB_NAME} /*\!40100 DEFAULT CHARACTER SET utf8 */;"

CREATE_USER_SQL=$(cat "${SQL_TEMPLATE_DIR}"/db_live_user_template.sql)
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##USERNAME##|dev|g")
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##PASSWORD##|${DEV_PASSWORD}|g")
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##FIRST##|Dev|g")
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##LAST##|System|g")
CREATE_USER_SQL=$(echo "${CREATE_USER_SQL}" | sed -r "s|##EMAIL##|dev@vivid-crm.io|g")

# rebuild db
# core db schema
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/db_live.sql

# item module basic
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/item/variant_basic.sql
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/item/core_extensions.sql

# contact module basic
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/contact/variant_basic.sql
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/contact/core_extensions.sql

# contact signup extension
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/contact/extensions/signup.sql

# job module basic
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/job/variant_basic.sql
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" < $SQL_TEMPLATE_DIR/modules/job/core_extensions.sql

# add user
mysql -u${DB_USER} -p${DB_PASS} "${DB_NAME}" -e "${CREATE_USER_SQL}"