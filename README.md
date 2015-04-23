# dip

dip clones each branch in a git repository into its own subdirectory.

* License: GPLv3
* Copyright 2015, Stoney Jackson &lt;dr.stoney@gmail.com>

## Using

Initialize a dip project for a git repository.

    $ dip init URL-TO-GIT-REPO mydipproj

Now everytime you run...

    $ dip update mydipproject

dip...

1. Deletes branch repositories that have been deleted from origin.
2. Pulls changes from origin into each existing branch repository.
3. Clones any new branch in origin into subdirectory of mydipproject.

## Dependencies

* git
* bash

## Install

1. Clone this repository.
2. Add bin to your path.

## Connecting with GitHub's Webhook

To implement continuous deployment for a GitHub repository, simply set up a dip
project for GitHub repository onto your deployment server, and then call `dip
update` from a webhook listener. Below is a very simple example in PHP.

***The example below is not secure. It is mearly an example. Use at your own
risk.***

    <?php exec('dip update /path/to/mydipproj');

Add the URL as a webhook to your GitHub project and let the auto deployment
begin.

## Customization: hooks

You can extend dip's behavior with hooks. A hook is an executable script that is
placed in one of the following directories:

* .dip/hooks/clone/
* .dip/hooks/delete/
* .dip/hooks/pull/

Each directory corresponds to one of the basic operations performed on branches
when `dip update` is ran. `clone` is performed to clone out a branch for the
first time. `delete` is performed when a branch no longer exists in origin.
`pull` is performed to pull changes from origin.

* Each hook in an operation directory is ran in lexicographical order to perform
  the operation.
* If any hook returns non-zero the operation is aborted for the current branch.
* Before a hook is run, the current working directory is set to the root of the
  dip project.
* The name of the target branch is passed as the first command-line argument.
* The default behavior for each operation is in a script `4_main.bash`. This
  makes it convenient to alter its behavior directly, or to install "pre" and
  "post" scripts.

### Example hook - database migration

This hook runs the application's bin/migrate-database command that presumably
updates the application's database.

    #!/bin/bash
    # FILE: dbmigrate.bash
    branch="$1"                 # retrieve the branch name from the command-line
    cd "$branch"                # move into the branch subdirectory
    bin/migrate-database        # run database migrations
    exit $?                     # return the exit code of bin/migrate-database

We want this to run after a branch is first cloned and after it is updated.

    $ chmod +x dbmigrate.bash
    $ cp dbmigrate.bash .dip/hooks/clone/9_dbmigrate.bash
    $ cp dbmigrate.bash .dip/hooks/pull/9_dbmigrate.bash

### Example hook - filter non-deployment branches

This hook demonstrates how you might exclude some branches from being cloned
(deployed). Here we assume that all branches that are prefixed with `wip-` are a
work in progress and should not be deployed.

    #!/bin/bash
    # FILE: filterwip.bash
    branch="$1"
    if [ "$branch" = "wip-*" ] ; then
        exit 1                      # abort operation
    fi
    exit 0                          # continue operation

Let's link it in.

    $ chmod +x filterwip.bash
    $ mv filterwip.bash .dip/hooks/clone/0_filterwip.bash

We prefixed the hook with `0-` to help ensure it would run before any other
hooks in branch-clone-pre. That way if our filter returns non-zero, no other
hooks will be ran nor will the operation.
