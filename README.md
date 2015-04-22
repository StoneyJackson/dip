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

## Hooks

[[[In progress]]]

post-clone      Ran after a branch has been cloned.
post-delete     Ran after a branch has been deleted.
post-update     Ran after a branch has been updated.
pre-clone       Ran before a branch is cloned.
pre-delete      Ran before a branch is deleted.
pre-update      Ran before a branch is updated.
