<?php
# Copyright (c) 2015, Stoney Jackson <dr.stoney@gmail.com>
# License: GPLv3


require('DIP_HOME/lib/DipWebHook.php');
$webhook = new DipWebHook(array(

    // Give this secret key to GitHub when you create the webhook.
    // This value was generated when dip created this project.
    // Feel free to change it.
    'secret' => 'SECRET_KEY',

    // These will be prepended to $PATH before any shell commands.
    // Use it to ensure dip and git are in your path.
    'paths' => array(

        // Uncomment to include the detected path to git.
        #'GIT_PATH',

        // Uncomment to include the detected path to dip.
        #'DIP_HOME/bin',

    ),

    // Path to the dip project. If this webhook.php is in the root of the 
    // project, you can leave this alone.
    'project-path' => 'DIP_PROJ', 

));
$webhook->ProcessRequest();
