# Copyright (c) 2015, Stoney Jackson <dr.stoney@gmail.com>
# License: GPLv3

is_command() {
    hash "$1" > /dev/null 2>&1
}
