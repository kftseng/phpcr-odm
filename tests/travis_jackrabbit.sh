#!/bin/bash

git submodule update --init --recursive

./lib/vendor/jackalope/tests/travis_jackrabbit.sh

cp cli-config.php.dist cli-config.php
./bin/phpcr odm:phpcr:register-system-node-types
