#!/usr/bin/env bash

DB_NAME=${1-wp-tests}
DB_USER=${2-root}
DB_PASS=${3-root}
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

SCRIPTPATH=`pwd -P`

WP_TESTS_DIR=${SCRIPTPATH}/wordpress-tests-lib/
WP_CORE_DIR=${SCRIPTPATH}/wordpress/
WP_CORE_LANG_DIR=${SCRIPTPATH}/wordpress/wp-content/languages/

#set -ex

install_wp() {
	mkdir -p $WP_CORE_DIR

	if [ $WP_VERSION == 'latest' ]; then
		local ARCHIVE_NAME='latest'
	else
		local ARCHIVE_NAME="wordpress-$WP_VERSION"
	fi

	wget -qO /tmp/wordpress.tar.gz http://wordpress.org/${ARCHIVE_NAME}.tar.gz
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite
	mkdir -p $WP_TESTS_DIR
	cd $WP_TESTS_DIR || exit

	if [ $WP_VERSION == 'latest' ]; then
	    svn co --quiet http://develop.svn.wordpress.org/trunk/tests/phpunit/includes/
	    svn co --quiet http://develop.svn.wordpress.org/trunk/tests/phpunit/data/
    else
        svn co --quiet http://develop.svn.wordpress.org/tags/${WP_VERSION}/tests/phpunit/includes/
        svn co --quiet http://develop.svn.wordpress.org/tags/${WP_VERSION}/tests/phpunit/data/
    fi

	TESTS_ABSPATH=$WP_CORE_DIR
	TESTS_LANG_DIR=$WP_CORE_LANG_DIR
	if [ "$OSTYPE" == "linux-gnu" ] && grep -q Microsoft /proc/version; then
    # Fix paths if we are in Windows Subsystem Linux (WSL)
	  TESTS_ABSPATH=$(wslpath -m $WP_CORE_DIR)/
	  TESTS_LANG_DIR=$(wslpath -m $WP_CORE_LANG_DIR)
  fi

	sed $ioption "s#DIR_TESTDATA . '/languages'# '$TESTS_LANG_DIR'#" includes/bootstrap.php

	wget -qO wp-tests-config.php http://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php
	sed $ioption "s#dirname( __FILE__ ) . '/src/'#'$TESTS_ABSPATH'#" wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}|" wp-tests-config.php
}

install_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [[ "$DB_SOCK_OR_PORT" =~ ^[0-9]+$ ]] ; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
  mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_wp
install_test_suite
install_db
