<?php
//////////////////////////////////////////////////////////////////////////////////////////////////
//
// // DBprocessing Plugin
// displays sql select results as table enhanced with manipulation actions called by button
// can call bureaucracy plugin using DB content as input
//
// @license	GPL 3 (http://www.gnu.org/licenses/gpl.html)
// @author	 Peter H. Spaeth <phs.sw@web.de>  
// @copyright Copyright(c) 2025, Peter Spaeth
// @version 0.5 / Dec 20, 2025 
//
//////////////////////////////////////////////////////////////////////////////////////////////////

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

	// Handle the match
	// {{DBprocessing:file=<configuration filename>?form=<formname>}}
	// return result array file=><configuration filename, form=><form name (DokuWikie (bureaucracy) page)>
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

	// Create output according to $data[0].
	// ['loanDivingEquipment'] ==> switch case 'loanDivingEquipment'['loanDivingEquipment']
	// $data is the return of handle() function
	// $format, also sometimes $format: the output format, always XHTML in DokuWiki
	function render($format, Doku_Renderer $renderer, $data) {
		global $INFO;  // Dokuwiki global, slightly different to docu https://www.dokuwiki.org/devel:environment#global_variables
        global $INPUT;
		global $conf;
		global $lang;

        if ($format != 'xhtml') return FALSE;
		if(!array_key_exists('file', $data)) return FALSE;
		if(!array_key_exists('mode', $data)) $data['mode'] = NULL;	// multiple means use checkboxes 
		if(!array_key_exists('form', $data)) $data['form'] = NULL;
		$renderer->nocache();

		// at least read access to config file (AUTH_xxxx defined in inc/defines.php
		if(auth_aclcheck('dbprocessing:'.$data['file'], $INFO['userinfo']['user'], $INFO['userinfo']['grps']) >= AUTH_READ) {
			$TCSdb = new TCSdb();
			$configFile = new configFile($conf['datadir'].'/dbprocessing/'.$data['file'].'.txt');
			// process input
			if($INPUT->has('update')) {
				if($INPUT->has('selectedAssets')) $this->processInput($TCSdb, $INPUT->arr('selectedAssets'), $configFile);
				else $this->processInput($TCSdb, $INPUT->str('update'), $configFile);
			}
			if($INPUT->has('insert')) {
				if($INPUT->has('selectedAssets')) $this->processInput($TCSdb, $INPUT->arr('selectedAssets'), $configFile);
				else  $this->processInput($TCSdb, $INPUT->str('insert'), $configFile);
			}
			$sql = $configFile->getTagContent('code sql show');
			if($sql) {
				if(!array_key_exists('form', $data)) $data['form'] = NULL;
				$this->displayArrAsTable($renderer, $TCSdb->query($sql, NULL), $configFile, $data['mode'], $data['form']);
			} else {
				$renderer->cdata('ERROR: can\'t find valid SQL!');
				return FALSE;
			}
		} else {
			$renderer->cdata('[n/a]');
			$renderer->linebreak();
			return FALSE;
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
	function displayArrAsTable($R, $lines, $configFile, $mode=NULL, $form=NULL) {
		Global $conf;
		Global $ID;

		$SQLs = array(
			'insert' => $configFile->getTagContent('code sql insert'),
			'update' => $configFile->getTagContent('code sql update'),
		);
		$label = array(
			'insert' => $configFile->getButton('insert') ? $configFile->getButton('insert') : 'insert',
			'update' => $configFile->getButton('update') ? $configFile->getButton('update') : 'update',
		);

		// form
		$R->doc .= 
    	        '<form id="dbprocessing__form" method="post" action="?id='.
        	    $ID.
           		'" accept-charset="utf-8">';
		// table
		$R->table_open(NULL, NULL, NULL, 'dbprocessing');
		$R->tablethead_open();
		$R->tablerow_open();
		foreach($lines[0] as $lineKey => $lineValue) {
			$R->tableheader_open(1, 'center', 1, 'dbHeader');
			$R->cdata($lineKey);
			$R->tableheader_close();
		}
		// insert
		if($SQLs['insert']) {
			$R->tableheader_open(1, 'center', 1, 'dbHeader');
			if($mode == 'multiple') $R->doc .= '<button type="submit" class="TCSbtn" value="insert'.
										$variables.'" name="insert" title="insert">'.
										$label['insert'].'</button>';
			$R->tableheader_close();
		}
		// update
		if($SQLs['update']) {
			$R->tableheader_open(1, 'center', 1, 'dbHeader');
			if($mode == 'multiple') $R->doc .= '<button type="submit" class="TCSbtn" value="update'.
										$variables.'" name="update" title="update">'.
										$label['update'].'</button>';
			$R->tableheader_close();
		}
		if($form) {
			$R->tableheader_open(1,'center', 1, 'dbHeader');
			$R->cdata('Bearbeiten');
			$R->tableheader_close();
		}
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
				if(($year == '0000') || ($colValue == NULL)){
					$R->cdata('---');	// invalid year 
				}
				else {
					$variables .= '&@'.$colKey.'@='.$colValue;
					// valid date
					if(checkdate((int) $month, (int) $day, (int) $year)) {
						// date & time
						if(strlen($colValue)>10) $colValue = date('d.m.Y H:i', strtotime($colValue));
						else $colValue =  date('d.m.Y', strtotime($colValue));
					} 
					$R->cdata($colValue);
				}
				$R->tablecell_close();
			}
			if($form) {
				$R->tablecell_open(1, 'center', 1, 'DBprocessingLink');
				$R->internallink($form.'?'.$variables, 'Update');
				$R->tablecell_close();
			}
			// insert
			if($SQLs['insert']) {
				$R->tablecell_open(1, 'center', 1, 'DBprocessingInsert');
				if($mode == 'multiple') {
					$R->doc .= '<input type="checkbox" name="selectedAssets[]" id="'.
                									    $variables.
									                    '" value="insert'.$variables.
                    									'" /> ';
			} else $R->doc .= '<button type="submit" class="TCSbtn" value="insert'.
									$variables.'" name="insert" title="insert">'.
									$label['insert'].
									'</button> ';
				$R->tablecell_close();
			}
			// insert
			if($SQLs['update']) {
				$R->tablecell_open(1, 'center', 1, 'DBprocessingUpdate');
				if($mode == 'multiple') {
					$R->doc .= '<input type="checkbox" name="selectedAssets[]" id="'.
                    									$variables.
                    									'" value="update'.$variables.
                    									'" /> ';
				} else $R->doc .= '<button type="submit" class="TCSbtn" value="update'.
									$variables.'" name="update" title="update">'.
									$label['update'].
									'</button> ';
				$R->tablecell_close();
			}
			$R->tablerow_close();
		}
		$R->tabletbody_close();
		$R->table_close();
		$R->doc .= '</form>';
		return;
	}

	function processInput($TCSdb, $action, $configFile) {
		Global $INFO;
		$ret = FALSE;

		if(!is_array($action)) $action = array($action);
		foreach($action as $actionKey => $actionValue) {
			$actionArr = explode('&', $actionValue);
			$sql = $configFile->getTagContent('code sql '.$actionArr[0]);
			if($sql) {
				foreach($actionArr as $key => $param) {
					$paramArr = explode('=', $param);
					if($paramArr[1]) {
						$params[str_replace('@', '', ':'.$paramArr[0])] = $paramArr[1];
					}
				}
				$params[':user'] = $INFO['userinfo']['user'];
				$sql = str_replace(array_keys($params), $params, $sql);
				$TCSdb->query($sql, NULL);
				$ret = TRUE;
			}
		}
		return $ret;
	}
}	
