#!/bin/bash

if [ ! -f "/var" ]; then
    cp var /data/var -r
fi
rm var -rf
ln -s /data/var var
chmod 777 -R /data/var

if [ ! -f "/data/var/votix.sqlite" ]; then
    php ./bin/console doctrine:database:drop --force
    php ./bin/console doctrine:database:create
    php ./bin/console doctrine:schema:update --force
fi

if [ ! -f "/data/mails" ]; then
    cp -r templates/mails /data/mails
fi
rm -rf templates/mails
ln -s /data/mails templates/mails

printenv > .env
sed -i '$ d' .env

php ./bin/console cache:clear

apachectl -D FOREGROUND
