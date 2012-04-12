<?php
/**
 * DokuWiki Plugin cosmourlaub (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_cosmourlaub extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler &$controller) {
        $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'handle_indexer_tasks_run');
    }

    public function handle_indexer_tasks_run(Doku_Event &$event, $param) {
        $hlp = plugin_load('helper','cosmourlaub');
        if(!$hlp->client){
            echo 'cosmourlaub: not configured'.DOKU_LF;
            return;
        }
        if(!$hlp->client->getAccessToken()){
            echo 'cosmourlaub: not authenticated'.DOKU_LF;
            return;
        }

        echo 'cosmourlaub: started'.DOKU_LF;
        $hlp->update_data();
        echo 'cosmourlaub: finished'.DOKU_LF;
    }

}

// vim:ts=4:sw=4:et:
