FROM mariadb:10.7.7-focal
COPY .env /docker-entrypoint-initdb.d/.env
COPY dbinit.sh /docker-entrypoint-initdb.d/dbinit.sh
COPY sql /home/sql
COPY crm_db.cnf /etc/mysql/mariadb.conf.d/crm_db.cnf

CMD ["mysqld"]
EXPOSE 3306
