# dip

Continuously deploys all branches from a GitHub repository.



## License

* Copyright 2015, Stoney Jackson &lt;dr.stoney@gmail.com>
* License: GPLv3



## Requirements

* GitHub
* Bash
* Git 1.10+
* PHP



## Installing dip

On your deployment server:

1. Clone this repository outside the document root.
2. Add dip/bin to your system path.


Optionally, if you don't have realpath, build it as follows:

```
$ make
```


## Setup a project for continuous deployment

On your deployment server:

1. Create a dip project under the document root.

    $ dip create path/to/project https://github.com/your/project.git

2. Configure webhook.php

    $ vim path/to/project/webhook.php

In your GitHub repository, add a webhook that points to webhook.php and uses the
secret key in webhook.php.

After you add the webhook, GitHub will ping webhook.php on your server. If all
goes well, all of your branches will be cloned into subdirectories of your dip
project. If something goes wrong, check your webserver's error logs for problems
(or wherever PHP errors are logged; check phpinfo() and php.ini).

## Customization

When webhook.php receives a request, it calls `dip update` on your project.
This does three things:

1. **Clones** each branch in origin into a separate subdirectory of yor dip
   project.
2. **Pulls** all changes from each branch in origin to each of your local
   branches.
3. **Deletes** all branches in your dip project that do not appear in
   origin.

Each operation has a corresponding subdirectory in .dip/hooks:

* .dip/hooks/clone/
* .dip/hooks/delete/
* .dip/hooks/pull/

All the executable scripts (called hooks) in an operation's directory is ran for
each branch that needs that operation performed.  By default, there is only one
such script, e.g., `4_clone.bash`. This script is the one that actually performs
the operation. To extend an operation's behavior, simply add an executable
script to that operation's hooks directory. Here are some other important facts
about hooks:

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
