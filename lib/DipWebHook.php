<?php
# Copyright (c) 2015, Stoney Jackson <dr.stoney@gmail.com>
# License: GPLv3


require('GitHub_WebHook.php');


class DipWebHook {

    private $SecretKey;
    private $PathsToPrepend;
    private $PathToDipProject;
    private $WebHook;

    public function __construct($settings) {
        $this->SecretKey = $settings['secret'];
        $this->PathsToPrepend = $settings['paths'];
        $this->PathToDipProject = dirname(dirname(__DIR__));
        $this->WebHook = new GitHub_WebHook();
    }

    public function ProcessRequest() {
        try {
            $this->GetRequestData();
            $this->SendResponse();
            $this->ValidateRequest();
            $this->ProcessEvent();
        } catch (Exception $exception) {
            error_log($exception);
        }
    }

    public function GetRequestData() {
        $this->WebHook->ProcessRequest();
    }

    public function SendResponse() {
        ob_start();
        http_response_code(200);
        header('Connection: close');
        header('Content-Length: '.ob_get_length());
        echo('Thanks!');
        ob_end_flush();
        flush();
    }

    public function ValidateRequest() {
        if (!$this->WebHook->ValidateHubSignature($this->SecretKey)) {
            throw new Exception(
                'Invalid Hub Signature: request not sent from GitHub or keys do not match.');
        }
    }

    public function ProcessEvent() {
        $EventTypeHandler = 'Handle' . ucfirst($this->WebHook->GetEventType());
        $this->$EventTypeHandler();
    }

    public function HandlePush() {
        $this->RunDip();
    }

    public function HandleCreate() {
        $this->RunDip();
    }

    public function HandleDelete() {
        $this->RunDip();
    }

    public function HandlePing() {
        $this->RunDip();
    }

    private function RunDip() {
        $this->PrependPathsToEnvironmentsPath();
        exec('dip update '.$this->PathToDipProject.' 2>&1', $out, $return);
        if ($return !== 0) {
            error_log(implode("\n", $out));
        }
    }

    private function PrependPathsToEnvironmentsPath() {
        if ($this->PathsToPrepend) {
            $path = implode(':', $this->PathsToPrepend);
            if ($_ENV['PATH']) {
                $path .= ':'.$_ENV['PATH'];
            }
            putenv("PATH=$path");
        }
    }
}
