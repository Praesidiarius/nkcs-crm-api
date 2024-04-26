#!/bin/bash

docker cp dbinit.sh crm.db:/docker-entrypoint-initdb.d/dbinit.sh
docker cp sql crm.db:/home/