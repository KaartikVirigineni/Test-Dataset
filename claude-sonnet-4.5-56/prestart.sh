#!/bin/bash

cd /var/www/html

if [ ! -f ".env" ]; then
    cp env .env
fi

php spark migrate

exec "$@"