#!/bin/bash
branch="$1"
git clone --branch "$branch" --single-branch ".dip/local.git" "$branch"
cp .dip/.htaccess "$branch/.git"
