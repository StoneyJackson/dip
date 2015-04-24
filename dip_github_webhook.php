<?php
/*
 * Edit the values below to configure your webhook.
 */
class Dip_GitHub_WebHook_Settings {

    /*
     * Secret key given to GitHub when defining the webhook.
     */
    public $SecretKey = '';

    /*
     * Prepended to $PATH before running dip in the shell.
     * Example: '/home/me/bin'
     */
    public $PathsToPrepend = array(
    );

    /*
     * Path to dip project.
     */
    public $PathToDipProject = '/path/to/project';
}


class Dip_GitHub_WebHook {

    private $SecretKey;
    private $PathsToPrepend;
    private $PathToDipProject;
    private $WebHook;

    public function __construct($settings) {
        $this->SecretKey = $settings->SecretKey;
        $this->PathsToPrepend = $settings->PathsToPrepend;
        $this->PathToDipProject = $settings->PathToDipProject;
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
        if (!$this->WebHook->ValidateIPAddress()) {
            throw new Exception(
                'Invalid IP Address: request not sent from GitHub.');
        }
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


/* GitHub_WebHook

The MIT License (MIT)

Copyright (c) 2015 xPaw

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
class GitHub_WebHook
{
    /**
     * GitHub's IP mask
     *
     * Get it from https://api.github.com/meta
     */
    const GITHUB_IP_BASE = '192.30.252.0';
    //const GITHUB_IP_BITS = 22;
    const GITHUB_IP_MASK = -1024; // ( pow( 2, self :: GITHUB_IP_BITS ) - 1 ) << ( 32 - self :: GITHUB_IP_BITS )
    
    private $Gogs = false;
    private $EventType;
    private $Payload;
    private $RawPayload;
    
    /**
     * Validates and processes current request
     */
    public function ProcessRequest( )
    {
        if( !array_key_exists( $this->GetEventHeaderName(), $_SERVER ) )
        {
            throw new Exception( 'Missing event header.' );
        }
        
        if( !array_key_exists( 'REQUEST_METHOD', $_SERVER ) || $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' )
        {
            throw new Exception( 'Invalid request method.' );
        }
        
        if( !array_key_exists( 'CONTENT_TYPE', $_SERVER ) )
        {
            throw new Exception( 'Missing content type.' );
        }
        
        $this->EventType = filter_input( INPUT_SERVER, $this->GetEventHeaderName(), FILTER_SANITIZE_STRING );
        
        $ContentType = $_SERVER[ 'CONTENT_TYPE' ];
        
        if( $ContentType === 'application/x-www-form-urlencoded' )
        {
            if( !array_key_exists( 'payload', $_POST ) )
            {
                throw new Exception( 'Missing payload.' );
            }
            
            $this->RawPayload = filter_input( INPUT_POST, 'payload' );
        }
        else if( $ContentType === 'application/json' )
        {
            $this->RawPayload = file_get_contents( 'php://input' );
        }
        else
        {
            throw new Exception( 'Unknown content type.' );
        }
        
        $this->Payload = json_decode( $this->RawPayload );
        
        if( $this->Payload === null )
        {
            throw new Exception( 'Failed to decode JSON: ' .
                function_exists( 'json_last_error_msg' ) ? json_last_error_msg() : json_last_error()
            );
        }
        
        if( !isset( $this->Payload->repository ) )
        {
            throw new Exception( 'Missing repository information.' );
        }
        
        return true;
    }
    
    /**
     * Set this to true to process webhook from Gogs (http://gogs.io/)
     */
    public function SetGogsFormat( $Value )
    {
        $this->Gogs = $Value == true;
    }
    
    /**
     * Optional function to check if request came from GitHub's IP range.
     *
     * @return bool
     */
    public function ValidateIPAddress( )
    {
        if( !array_key_exists( 'REMOTE_ADDR', $_SERVER ) )
        {
            throw new Exception( 'Missing remote address.' );
        }
        
        $Remote = ip2long( $_SERVER[ 'REMOTE_ADDR' ] );
        $Base   = ip2long( self :: GITHUB_IP_BASE );
        
        return ( $Base & self :: GITHUB_IP_MASK ) === ( $Remote & self :: GITHUB_IP_MASK );
    }
    
    /**
     * Optional function to check if HMAC hex digest of the payload matches GitHub's.
     *
     * @return bool
     */
    public function ValidateHubSignature( $SecretKey )
    {
        if( !array_key_exists( 'HTTP_X_HUB_SIGNATURE', $_SERVER ) )
        {
            throw new Exception( 'Missing X-Hub-Signature header. Did you configure secret token in hook settings?' );
        }
        
        return 'sha1=' . hash_hmac( 'sha1', $this->RawPayload, $SecretKey, false ) === $_SERVER[ 'HTTP_X_HUB_SIGNATURE' ];
    }
    
    /**
     * Returns event type
     * See https://developer.github.com/webhooks/#events
     *
     * @return string
     */
    public function GetEventType( )
    {
        return $this->EventType;
    }
    
    /**
     * Returns decoded payload
     *
     * @return array
     */
    public function GetPayload( )
    {
        return $this->Payload;
    }
    
    /**
     * Returns full name of the repository
     *
     * @return string
     */
    public function GetFullRepositoryName( )
    {
        if( isset( $this->Payload->repository->full_name ) )
        {
            return $this->Payload->repository->full_name;
        }
        
        return sprintf( '%s/%s', $this->Payload->repository->owner->name, $this->Payload->repository->name );
    }
    
    private function GetEventHeaderName( )
    {
        if( $this->Gogs )
        {
            return 'HTTP_X_GOGS_EVENT';
        }
        
        return 'HTTP_X_GITHUB_EVENT';
    }
}
/* End of GitHub_WebHook */


$webhook = new Dip_GitHub_WebHook(new Dip_GitHub_WebHook_Settings());
$webhook->ProcessRequest();
