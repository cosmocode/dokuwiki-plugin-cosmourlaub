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
    private $datafile;

    function __construct(){
        if(!$this->getConf('client_id') ||
           !$this->getConf('client_secret') ||
           !$this->getConf('devel_key')) {
            return;
        }

        require_once(dirname(__FILE__).'/googleapi/apiClient.php');
        require_once(dirname(__FILE__).'/googleapi/contrib/apiCalendarService.php');

        // prepare API client
        $this->client = new apiClient();
        $this->client->setApplicationName('CosmoCode Urlaubsverwaltung');
        $this->client->setClientId($this->getConf('client_id'));
        $this->client->setClientSecret($this->getConf('client_secret'));
        $this->client->setRedirectUri(DOKU_URL.'lib/plugins/cosmourlaub/api.php');
        $this->client->setDeveloperKey($this->getConf('devel_key'));
        $this->calService = new apiCalendarService($this->client);

        $this->datafile = getCacheName('cosmourlaub','.data.cosmourlaub');

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
    function update_data(){
        $data = array();

        $now = new DateTime('now');

        $calList = $this->calService->calendarList->listCalendarList();
        foreach($calList['items'] as $calendar){
            $events = $this->calService->events->listEvents($calendar['id']);
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

                echo $calendar['id'].' '.$start->format('Y-m-d').' '.$days." days\n";
            }
        }

        #print_r($data); #debugging

        file_put_contents($this->datafile,serialize($data));
    }

    /**
     * Return the stored data
     */
    public function get_data(){
        if(file_exists($this->datafile)){
            return unserialize(file_get_contents($this->datafile));
        }
        return array();
    }

    /**
     * Should the auto update run?
     */
    public function needs_update(){
        global $conf;
        return (@filemtime($this->datafile) < time() - $conf['cachetime']);
    }
}

// vim:ts=4:sw=4:et:
