# dip

Used to clone and update all branches from a repository. Intended for use with a
GitHub webhook for continuous deployment of all branches.

* License: GPLv3
* Copyright 2015, Stoney Jackson &lt;dr.stoney@gmail.com>

## Install

```
$ git clone URL
$ echo "PATH=$PATH:$(pwd)/dip/bin" >> ~/.bashrc
$ source ~/.bashrc
```

## Simple Use

```
$ dip init URL/project.git
$ cd project
$ dip update
```

## Explicit Use

```
$ dip init URL/project.git path/to/destination
$ dip update path/to/destination
```
