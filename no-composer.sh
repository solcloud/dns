#!/bin/sh

set -e

if [ -f vendor/ ]; then
  echo "Vendor folder exists!"
  exit 1
fi

git submodule init
git submodule update

mkdir vendor
cp no-composer.php vendor/autoload.php
