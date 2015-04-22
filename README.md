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
project for GitHub repository onto your deployment server, and then call
`dip update` from a webhook listener. Below is a very simple example in PHP.

***The example below is not secure. It is mearly an example. Use at your own
risk.***

    <?php exec('dip update /path/to/mydipproj');

Add the URL as a webhook to your GitHub project and let the auto deployment
begin.

## Customization with hooks

A hook is an executable script that runs either before or after each major
operation on a branch. Hooks are added by placing them in the directory
corresponding to when it should be ran.

    .dip/hooks/branch-clone-post/   - After branch is cloned
    .dip/hooks/branch-clone-pre/    - Before branch is cloned
    .dip/hooks/branch-delete-post/  - After branch is deleted
    .dip/hooks/branch-delete-pre/   - Before branch is deleted
    .dip/hooks/branch-update-post/  - After branch is updated
    .dip/hooks/branch-update-pre/   - Before branch is updated

* Before a hook is run, the current working directory is set to the root of the
  dip project.
* The name of the target branch is passed as the first command-line argurment.
* If a pre-hook exits with a non-zero value, all following hooks and the
  operation itself is aborted.
* More than one hook may be installed for each event. Hooks are executed in the
  order they are globbed.

## Example hook - database migration

This hook runs the application's bin/migrate-database command that presumably
updates the application's database. Let's assume it's called `dbmigrate.bash`:

    #!/bin/bash
    branch="$1"                 # retrieve the branch name from the command-line
    cd "$branch"                # move into the branch subdirectory
    bin/migrate-database        # run database migrations

We want this to run after a branch is first cloned and after it is updated.
You can either copy dbmigrate.bash into each hook subdirectory, or you could
symlink it (or any variation thereof). In this example, we'll store our hook in
.dip/hooks and symlink it into branch-clone-post and branch-update-post

    $ cd .dip/hooks
    $ cp path/to/dbmigrate.bash .
    $ chmod +x dbmigrate.bash
    $ cd branch-clone-post
    $ ln -s ../dbmigrate.bash .
    $ cd ../branch-update-post
    $ ln -s ../dbmigrate.bash .

## Example hook - filter non-deployment branches

This hook demonstrates how you might exclude some branches from being cloned
(deployed). Here we assume that all branches that are prefixed with `wip-` are a
work in progress and should not be deployed. We'll assume this script is called
filter-wip.bash

    #!/bin/bash
    branch="$1"
    if [ "$branch" = "wip-*" ] ; then
        exit 1                      # Exit non-zero to stop further processing
    fi
    exit 0                          # Exit zero continues processing

Let's link it in.

    $ cd .dip/hooks
    $ cp path/to/filter-wip.bash .
    $ chmod +x filter-wip.bash
    $ cd branch-clone-pre
    $ ln -s ../filter-wip.bash  0-filter-wip.bash

We prefixed the hook with `0-` to help ensure it would run before any other
hooks in branch-clone-pre. That way if our filter returns non-zero, no other
hooks will be ran nor will the operation.
