#!/bin/bash
# Copyright (c) 2015, Stoney Jackson <dr.stoney@gmail.com>
# License: GPLv3


branch="$1"
git clone --branch "$branch" --single-branch ".dip/local.git" "$branch"
cp .dip/.htaccess "$branch/.git"
