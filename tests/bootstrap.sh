#!/usr/bin/env bash

set -e

USAGE="First argument should be install or update, followed by optional verbosity for commands"

if test $# -lt 1; then echo "$USAGE" && exit -1; fi
case "$1" in
    install)
        INSTALL_MODE="install";
        ;;
    update)
        INSTALL_MODE="update";
        ;;
    *)
        echo "Wrong mode."
	echo "$USAGE"
        exit -1
esac
shift
VERBOSITY=$@

set -x

if ! ./bin/developer system:uninstall --env=test > /dev/null 2>&1
then
    rm -f config/configuration.test.yml config/configuration-compiled.test.php
    rm -f /tmp/phraseanet.test.databox.sqlite /tmp/phraseanet.test.appbox.sqlite
fi

WORKDIR=`pwd`;

./bin/setup system:install \
    --env=test \
    --email=test@phraseanet.com \
    --password=test \
    --db-dsn="sqlite:path=/tmp/phraseanet.test.databox.sqlite;dbname=/tmp/phraseanet.test.databox.sqlite;user=root" \
    --ab-dsn="sqlite:path=/tmp/phraseanet.test.appbox.sqlite;dbname=/tmp/phraseanet.test.appbox.sqlite;user=root" \
    --db-template=en \
    --server-name=http://127.0.0.1 -y $VERBOSITY
case "$INSTALL_MODE" in
    update)
        ./bin/developer ini:reset --env=test --email=test@phraseanet.com --password=test --run-patches --no-setup-dbs $VERBOSITY
        php resources/hudson/cleanupSubdefs.php $VERBOSITY
        ;;
    install)
        ;;
esac

./bin/developer ini:setup-tests-dbs --env=test $VERBOSITY
./bin/console searchengine:index:create --env=test $VERBOSITY
./bin/developer phraseanet:regenerate-sqlite --env=test $VERBOSITY
