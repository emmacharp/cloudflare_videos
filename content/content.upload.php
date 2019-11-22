<?php

if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

require_once(TOOLKIT . '/class.jsonpage.php');

class contentExtensionCloudflare_videosUpload extends JSONPage {
    
    public function view() {
        var_dump('hello world');
        die();
        
        $this->_Result = [];
    }
}
