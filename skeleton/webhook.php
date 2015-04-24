<?php
require('.dip/lib/DipWebHook.php');
$webhook = new DipWebHook(array(

    // Some random secret key. Give it to GitHub when you create the webhook.
    'secret' => '',

    // These will be prepended to $PATH before any shell commands.
    // Use it to ensure dip and git are in your path.
    'paths' => array(),

));
$webhook->ProcessRequest();
