<?php
/**
 * DokuWiki Plugin cosmourlaub (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_cosmourlaub extends DokuWiki_Syntax_Plugin {
    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 250;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<cosmourlaub \d\d\d\d>\n.*?\n</cosmourlaub>',$mode,'plugin_cosmourlaub');
    }

    public function handle($match, $state, $pos, &$handler){
        $lines = explode("\n",$match);
        $year  = (int) substr(array_shift($lines),-5,4); // year is in first line
        array_pop($lines); // last line is closing tag
        $data = array(
            'year'  => $year,
            'users' => array()
        );

        // parse input lines
        foreach($lines as $line){
            $line = preg_replace('/#.*$/','',$line);
            $line = trim($line);
            if($line == '') continue;

            $user = '';
            $rest = 0;
            $days = 0;
            $parts = explode(' ',$line);
            foreach($parts as $part){
                $part = trim($part);
                if(substr($part,0,1) == '+'){
                    $rest = (float) $part;
                }elseif(is_numeric($part)){
                    $days = (float) $part;
                }else{
                    $user = $part;
                }
            }

            if($user){
                $data['users'][$user] = array(
                    'days' => $days,
                    'rest' => $rest,
                );
            }
        }

        return $data;
    }

    public function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;
        $hlp     = plugin_load('helper','cosmourlaub');
        $caldata = $hlp->get_data();
        $year    = $data['year'];

        $R->table_open();
        $R->tablerow_open();
        $R->tableheader_open();
        $R->cdata($this->getLang('name'));
        $R->tableheader_close();
        $R->tableheader_open();
        $R->cdata($this->getLang('days'));
        $R->tableheader_close();
        $R->tableheader_open();
        $R->cdata($this->getLang('rest'));
        $R->tableheader_close();
        $R->tableheader_open();
        $R->cdata($this->getLang('past'));
        $R->tableheader_close();
        $R->tableheader_open();
        $R->cdata($this->getLang('future'));
        $R->tableheader_close();
        $R->tableheader_open();
        $R->cdata($this->getLang('available'));
        $R->tableheader_close();
        $R->tablerow_close();

        foreach($data['users'] as $id => $user){
            if(isset($caldata[$year][$id])){
                extract($caldata[$year][$id]);
            }else{
                $name   = $id;
                $past   = 0;
                $future = 0;
            }

            $R->tablerow_open();
            $R->tablecell_open();
            $R->cdata($name);
            $R->tablecell_close();
            $R->tablecell_open(1,'center');
            $R->cdata($user['days']);
            $R->tablecell_close();
            $R->tablecell_open(1,'center');
            $R->cdata($user['rest']);
            $R->tablecell_close();
            $R->tablecell_open(1,'center');
            $R->cdata($past);
            $R->tablecell_close();
            $R->tablecell_open(1,'center');
            $R->cdata($future);
            $R->tablecell_close();
            $R->tablecell_open(1,'center');
            $R->cdata($user['days']+$user['rest']-$past-$future);
            $R->tablecell_close();
            $R->tablerow_close();

        }

        $R->table_close();

        return true;
    }
}

// vim:ts=4:sw=4:et:
