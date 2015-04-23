# dip

Continuous deployment of all branches of a GitHub hosted repository.

## License

* Copyright 2015, Stoney Jackson &lt;dr.stoney@gmail.com>
* License: GPLv3

## Requirements

For the `dip` command:

* Bash
* Git

For the `dip_github_webhook.php` PHP webhook:

* GitHub
* PHP

## Installing `dip`

Clone this repository and add its `bin` directory to your path. Alternatively,
always supply a relative or absolute path when calling `dip`.

## Using `dip`

    $ dip init URL/to/my/project.git path/to/mydipproj
    $ dip update path/to/mydipproj

The first line creates a dip project. The second line updates your dip project
with origin; run as often as you like.

`dip update` does three things:

1. It **clones** each branch in origin into a separate subdirectory of yor dip
   project.
2. It **pulls** all changes from each branch in origin to each of your local
   branches.
3. It **deletes** all branches in your dip project that do not appear in
   origin.

The behavior of each operation can be controlled through hooks (see Hooks).

## Using `dip_github_webhook.php`

1. Create a dip project for GitHub hosted git repository (see Using `dip`).
2. Copy `dip_github_webhook.php` under your document root, edit it, and adjust
   the attributes shown below.
    <?php
    class Dip_GitHub_WebHook {
        private $GitHub_WebHook_SecretKey = 'secret key';
        private $PathToDipCommand = '/path/to/dip';
        private $PathToDipProject = '/path/to/dip/project';
        ...
3. Use GitHub to create a webhook for your porject, giving it the secret key and
   the URL to `dip_github_webhook.php`.

Now everytime anything is pushed to your GitHub project, your dip project will
be updated. You'll probably want dip to run some additional command after it
updates a branch. See Hooks for how to customize dip's update behavior.

### Debugging

Check your webservers error logs for problems (or wherever PHP errors are
logged).

## Hooks

When you run `dip update`, three operations are performed:

1. delete - Deletes each local branch that no longer exists in origin.
2. pull - Pull changes from origin into each local branch.
3. clone - Clone each branch in origin that is not represented locally.

Each operation has a corresponding subdirectory in .dip/hooks:

* .dip/hooks/clone/
* .dip/hooks/delete/
* .dip/hooks/pull/

All the executable scripts (called hooks) in an operation's directory is ran for
each branch that needs that operation performed. They are ran in lexicographical
order.  By default, there is only one such script, `4_main.bash`. This script is
the one that actually performs the operation.

This framework makes it easy to customize each operation. To update an
operation, simply add an executable script to that operation's hooks directory.
The order that your hook will be executed is determined by its name.

Here are some other important facts about hooks:

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
(deployed). Here we assume that all branches that are prefixed with `wip_` are a
work in progress and should not be deployed.

    #!/bin/bash
    # FILE: filterwip.bash
    branch="$1"
    if [ "$branch" = "wip_*" ] ; then
        exit 1                      # abort operation
    fi
    exit 0                          # continue operation

Let's link it in.

    $ chmod +x filterwip.bash
    $ mv filterwip.bash .dip/hooks/clone/0_filterwip.bash

We prefixed the hook with `0_` to help ensure it would run before any other
hooks in branch-clone-pre. That way if our filter returns non-zero, no other
hooks will be ran nor will the operation.


## FAQ

### Q: Why dip and not dep (ala short for deployment)?

We wanted to stick with git's insulting naming convention.

## Credits

Thanks to 

* Using xPaw's [GitHub-WebHook](https://github.com/xPaw/GitHub-WebHook) via MIT
  License (Expat)
