<?php
/**
 * DBprocessing Plugin
 * calls bureaucracy plugin using DB content as input
 *
 * @license	GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @author	 Peter H. Spaeth <phs.sw@web.de>  
 * @copyright Copyright(c) 2025, Peter Spaeth
 * @version 0.5 / Dec 20, 2025 
 */

// must run within DokuWiki
defined('DOKU_INC') or die('DokuWiki plugin needs DokuWiki!');

require_once(DOKU_INC.'lib/plugins/dbprocessing/classes/class.configFile.php');
require_once(DOKU_INC.'TCSenhancement/classes/classes.TCSdb.php');

use DBprocessing\configFile;

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 * very similar to the basic syntax plugin - substitution example https://www.dokuwiki.org/devel:syntax_plugin_skeleton
 */
class syntax_plugin_dbprocessing extends DokuWiki_Syntax_Plugin {
	/**
	* return some info
	*/
	function getInfo(){
		return array(
			'app'   => 'DBprocessing Dokuwiki Plugin',
			'description'   => 'calls bureaucracy plugin using DB content as input',
			'author' => 'Peter H. Spaeth',
			'email'  => 'phs.sw@web.de',
			'date'   => '2025/12/20',
		);
	}

	function getType(){ return 'substition';}  // token is simply replaced. Typo is necessary.
	function getPType(){ return 'block';}  // paragraph type: plugin output is whole 'block', closes previous paragraph
	function getSort(){ return 173; }   // not important for substition plugins

	/**
	 * Connect pattern to lexer
	 * when a DokuWiki page contains {{DBprocessing:...}} the plugin will be called.
	 */
	function connectTo($mode)
	{
		$this->Lexer->addSpecialPattern('{{DBprocessing:.+?}}', $mode, 'plugin_dbprocessing'); // '?' makes it non-greedy
		// addSpecialPattern is typical for substition plugins. Entry/Exit in one step. Other plugin surround unmatched text.
	}

	/**
	* Handle the match
	 * {{TCSloan:loanDivingEquipment}} ==> ['loanDivingEquipment']
	 * return array is (typically cached and) passed to render() function as $data
	 * state is probably always: DOKU_LEXER_SPECIAL - from addSpecialPattern and not used
	*/
	function handle($match, $state, $pos, Doku_Handler $handler){
		date_default_timezone_set('UTC');
//		error_reporting(E_ALL);
//		ini_set('display_errors', '1');     
//		ini_set('memory_limit', '-1');     
//		ini_set('max_execution_time', 0); 
//		set_time_limit(3000);
		$parameter = parse_str(substr($match, 15, -2), $result);  // strip markup: {{dbprocessing:loanDivingEquipment}} ==> loanDivingEquipment
		return $result;
	}

	/**
	* Create output according to $data[0].
	 * ['loanDivingEquipment'] ==> switch case 'loanDivingEquipment'['loanDivingEquipment']
	 * $data is the return of handle() function
	 * $mode, also sometimes $format: the output format, always XHTML in DokuWiki
	 *
	*/
	function render($format, Doku_Renderer $renderer, $data) {
		global $ID;
		global $INFO;  // Dokuwiki global, slightly different to docu https://www.dokuwiki.org/devel:environment#global_variables
        global $INPUT;
		global $conf;
		global $lang;

        if ($format != 'xhtml') {
            return false;
        }
		$renderer->nocache();
        $configFile = new configFile($conf['datadir'].'/dbprocessing/'.$data['file'].'.txt');
        // config file availabe
        if(!($configFile->configFileExists())) {
            $renderer->cdata('[n/a]');
            $renderer->linebreak();
            return FALSE;
        } else {
			$sql = $configFile->getTagContent('code sql select');
			if($sql) {
				$TCSdb = new TCSdb();
				$this->displayArrAsTable($renderer, $TCSdb->query($sql, NULL), $data['form'], $configFile);
				$renderer->linebreak();
			} else {
				$renderer->cdata('ERROR: can\'t find valid SQL!');
				return FALSE;
			}
		}

		return TRUE; 
	}
	
	// display plugin information
	function displayInfo($R) {
		$pluginInfos = $this->getInfo();
		foreach($pluginInfos as $key => $pluginInfo) {
			$R->cdata( $key.': '.$pluginInfo);
			$R->linebreak();
		}
		return;
	}

	// display array as table
	function displayArrAsTable($R, $lines, $form, $configFile = NULL) {
		Global $conf;

		$R->table_open(NULL, NULL, NULL, 'dbprocessing');
		$R->tablethead_open();
		$R->tablerow_open();
		foreach($lines[0] as $lineKey => $lineValue) {
			$R->tableheader_open(1, 'center', 1, 'dbHeader');
			$R->cdata($lineKey);
			$R->tableheader_close();
		}
		$R->tableheader_open(1,'center', 1, 'dbHeader');
		$R->cdata('Action');
		$R->tableheader_close();
		$R->tablerow_close();
		$R->tablethead_close();
		$R->tabletbody_open();
		foreach($lines as $lineKey=> $lineValue) {
			$variables = '';
			$R->tablerow_open();
			foreach($lineValue as $colKey => $colValue) {
				$colKey = trim($colKey);
				$colValue = trim($colValue);
				$R->tablecell_open(1, NULL, 1, $configFile->getClass($colKey));
				[$year, $month, $day] = explode('-', $colValue);
				if($year == '0000') {
					$R->cdata('---');	// invalid year 
				}
				else {
					$colValue = checkdate((int) $month, (int) $day, (int) $year) ? date('d.m.Y', strtotime($colValue)) : $colValue;
					$R->cdata($colValue);
					$variables .= '&@'.$colKey.'@='.$colValue;
				}
				$R->tablecell_close();
			}
			$R->tablecell_open(1, 'center', 1, 'DBprocessingLink');
			$R->internallink($form.'?'.$variables, 'Update');
			$R->tablecell_close();
			$R->tablerow_close();
		}
		$R->tabletbody_close();
		$R->table_close();
		return;
	}

}	
