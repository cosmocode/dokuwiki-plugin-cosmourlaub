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
    echo 'The plugin is now authenticated and will do it\'s magic';
    dbg($hlp->client->getAccessToken());
}else{
    $authUrl = $hlp->client->createAuthUrl();
    send_redirect($authUrl);
}

/*

$calList = $cal->calendarList->listCalendarList();

foreach($calList['items'] as $calendar){
    if($calendar['id'] != 'agoh@cosmocode.de') continue;

    $events = $cal->events->listEvents($calendar['id']);

    dbg($events);
}

#    print "<h1>Calendar List</h1><pre>" . print_r($calList, true) . "</pre>";
#listEvents($calendarId
*/
