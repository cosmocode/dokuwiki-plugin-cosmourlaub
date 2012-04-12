<?php
/**
 * DokuWiki Plugin cosmourlaub (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_cosmourlaub extends DokuWiki_Plugin {

    public $client;
    public $calService;

    private $authfile;
    public  $redirecturi;

    function __construct(){
        if(!$this->getConf('client_id') ||
           !$this->getConf('client_secret') ||
           !$this->getConf('devel_key')) {
            return;
        }

        $this->redirecturi = DOKU_URL.'lib/plugins/cosmourlaub/api.php';
        if($this->getConf('redirecthack')){
            $this->redirecturi = 'http://www.splitbrain.org/_static/redirect.php?r='.
                                 rawurlencode($this->redirecturi);
        }

        require_once(dirname(__FILE__).'/googleapi/apiClient.php');
        require_once(dirname(__FILE__).'/googleapi/contrib/apiCalendarService.php');

        // prepare API client
        $this->client = new apiClient();
        $this->client->setApplicationName('CosmoCode Urlaubsverwaltung');
        $this->client->setClientId($this->getConf('client_id'));
        $this->client->setClientSecret($this->getConf('client_secret'));
        $this->client->setRedirectUri($this->redirecturi);
        $this->client->setDeveloperKey($this->getConf('devel_key'));
        $this->calService = new apiCalendarService($this->client);

        // try to authenticate
        $this->authfile = getCacheName('cosmourlaub','.auth.cosmourlaub');
        if(file_exists($this->authfile)){
            $authdata = file_get_contents($this->authfile);
            $this->client->setAccessToken($authdata);
        }
    }

    /**
     * Premanently store auth information
     *
     * Should be called after each API call to make sure a current refresh
     * token is available
     */
    function store_auth(){
        $authdata = $this->client->getAccessToken();
        if($authdata) file_put_contents($this->authfile,$authdata);
    }

    /**
     * The actual API workhorse
     *
     * Fetches data from all accessible calendars and stores the
     * vacation durations in the data file
     *
     * @todo only fetch current year (and last year if still january)
     */
    function update_data($debug = false){
        $data = array();

        $now = new DateTime('now');
        $limit = array('timeMin' => $this->current_year().'-01-01T00:00:00+00:00');


        if($debug) echo '<ul>';
        $calList = $this->calService->calendarList->listCalendarList();
        foreach($calList['items'] as $calendar){
            $events = $this->calService->events->listEvents($calendar['id'],$limit);
            if(isset($events['items'])) foreach($events['items'] as $event){
                if(!preg_match($this->getConf('regex'),$event['summary'])) continue;

                #print_r($event); #debugging

                // analyze time frame
                if(isset($event['start']['date'])){
                    // full day event
                    $start = new DateTime($event['start']['date']);
                    $end   = new DateTime($event['end']['date']);
                    $diff  = $start->diff($end);
                    $days  = $diff->days;
                }else{
                    // time based event
                    $start = new DateTime($event['start']['dateTime']);
                    $end   = new DateTime($event['end']['dateTime']);
                    $diff  = $start->diff($end);

                    // we do only half days
                    $days  = $diff->d;
                    $hours = $diff->h;
                    if($hours > 3) $days += 0.5;
                }

                // store it in structure
                $year = $start->format('Y');
                if(!isset($data[$year][$calendar['id']])){
                    $data[$year][$calendar['id']] = array(
                        'name' => $calendar['summary'],
                        'past' => 0,
                        'future' => 0
                    );
                }
                if($now->diff($start)->invert){
                    $data[$year][$calendar['id']]['past'] += $days;
                }else{
                    $data[$year][$calendar['id']]['future'] += $days;
                }


                if($debug){
                    echo '<li><div class="li">';
                    echo '<b>'.$days.' days: </b> ';
                    echo '<a href="'.$event['htmlLink'].'">';
                    echo $start->format('Y-m-d').' ';
                    echo hsc($event['summary']);
                    echo '</a> ';
                    echo $calendar['id'];
                    echo '</li>';
                    tpl_flush();
                }
            }
        }
        if($debug) echo '</ul>';

        #print_r($data); #debugging
        foreach($data as $year => $info){
            file_put_contents($this->data_file($year),serialize($info));
        }
    }

    /**
     * Where is the data stored for the given year?
     */
    public function data_file($year){
        return getCacheName("cosmourlaub",".$year.cosmourlaub");
    }

    /**
     * Returns the current year, except when the current year is very young
     * then the last year is returned
     */
    public function current_year(){
        $year  = date('Y');
        $month = date('n');

        if($month < 2){
            return $year - 1;
        }else{
            return $year;
        }
    }

    /**
     * Return the stored data
     */
    public function get_data($year){
        $file = $this->data_file($year);
        if(file_exists($file)){
            return unserialize(file_get_contents($file));
        }
        return array();
    }

    /**
     * Should the auto update run?
     */
    public function needs_update(){
        global $conf;
        $year = $this->current_year();
        return (@filemtime($this->data_file($year)) < time() - $conf['cachetime']);
    }
}

// vim:ts=4:sw=4:et:
