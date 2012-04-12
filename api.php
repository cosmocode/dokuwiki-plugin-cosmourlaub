<?php

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
define('DOKU_DISABLE_GZIP_OUTPUT', 1);
require_once(DOKU_INC.'inc/init.php');
session_write_close();


$hlp = plugin_load('helper','cosmourlaub');

if(!$hlp->client){
    echo 'You haven\'t configured the plugin yet. Please create the needed API keys in Google\'s API Console';
    exit;
}

if (isset($_GET['code'])) {
    $hlp->client->authenticate();
    $hlp->store_auth();
    if(!$hlp->client->getAccessToken()){
        die('Something went wrong :-(');
    }
    send_redirect(wl('',array('do'=>'admin','page'=>'cosmourlaub'),true,'&'));
}else{
    $authUrl = $hlp->client->createAuthUrl();
    send_redirect($authUrl);
}

