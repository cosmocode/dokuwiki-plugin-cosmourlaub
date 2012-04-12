<?php
/**
 * DokuWiki Plugin cosmourlaub (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'admin.php';

class admin_plugin_cosmourlaub extends DokuWiki_Admin_Plugin {

    public function getMenuSort() {
        return 500;
    }

    public function forAdminOnly() {
        return false;
    }

    public function handle() {
    }

    public function html() {
        global $ID;
        $hlp = plugin_load('helper','cosmourlaub');

        if($hlp->client && $hlp->client->getAccessToken()){
            echo $this->locale_xhtml('intro');

            echo '<p>';
            echo '<a href="'.wl($ID,array('do'=>'admin','page'=>'cosmourlaub','update'=>1)).'" class="button">';
            echo $this->getLang('update');
            echo '</a>';
            echo '</p>';
            tpl_flush();

            if($_REQUEST['update']) $hlp->update_data(true);
        }else{
            echo $this->locale_xhtml('auth');
            $redir = $hlp->redirecturi;
            echo '<p>Redirect-URI for authentication: <a href="'.$redir.'">'.$redir.'</a></p>';
        }
    }
}

// vim:ts=4:sw=4:et:
