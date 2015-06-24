#! /bin/bash
set -ex

BASE_PATH=$(pwd)
MW_INSTALL_PATH=$BASE_PATH/../mw

cd $MW_INSTALL_PATH/extensions/SemanticWatchlist

if [ "$TYPE" == "coverage" ]
then
	composer phpunit -q -- --coverage-clover $BASE_PATH/build/coverage.clover
else
	composer phpunit -q
fi
