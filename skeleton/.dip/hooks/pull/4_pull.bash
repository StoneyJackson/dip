#!/bin/bash
# Copyright (c) 2015, Stoney Jackson <dr.stoney@gmail.com>
# License: GPLv3


branch="$1"
git --git-dir="$branch/.git" pull
