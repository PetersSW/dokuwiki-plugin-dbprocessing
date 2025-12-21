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
			$sql = $configFile->getTagContent('code select');
			$TCSdb = new TCSdb();
			$this->displayArrAsTable($renderer, $TCSdb->query($sql, NULL));
			$renderer->linebreak();
		}

//		$this->displayInfo($renderer);
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
	function displayArrAsTable($R, $lines) {
		Global $conf;
		$R->table_open(NULL, NULL, NULL, 'dbprocessing');
		$R->tablethead_open();
		$R->tablerow_open();
		foreach($lines[0] as $lineKey => $lineValue) {
			$R->tableheader_open(1, 'center', 1, 'dbHeader');
			$R->cdata($lineKey);
			$R->tableheader_close();
		}
		$R->tablerow_close();
		$R->tablethead_close();
		$R->tabletbody_open();
		foreach($lines as $lineKey=> $lineValue) {
			$R->tablerow_open();
			foreach($lineValue as $colKey => $colValue) {
				$R->tablecell_open(1, 'center', 1, 'dbCell');
				[$year, $month, $day] = explode('-', $colValue);
				if($year == '0000') $colValue = '-';	// invalid year 
				else $colValue = checkdate((int) $month, (int) $day, (int) $year) ? date('d.m.Y', strtotime($colValue)) : $colValue;
				$R->cdata($colValue);
				$R->tablecell_close();
			}
			$R->tablerow_close();
		}
		$R->tabletbody_close();
		$R->table_close();
		return;
	}

	# dummy function to display something to show that plugin is working
	function displayDummy($renderer) {
		$renderer->doc .= 'dummy case</br></br></br>';
		return;
	}

	//	second dummy function
	function displayDummy2($renderer) {
//		get php version
		$phpversion = phpversion();
		$renderer->doc .= "dummy case 2 hihi</br></br></br>This is a dummy heading from plugin syntax.php
							<h2>Dummy heading</h2>
							test
							a heading:
							==== a doku wiki heading - not working, probably not intended from a plugin ====
							something else
							another change
							and on more: $phpversion</br></br></br>
							parser modes:
		
							";

		return;
	}

	function printAllVariables($renderer, $uid, $user, $email, $data, $mode) {
		// print all available variables
		$renderer->doc .= 'uid: '.$uid.'</br>user: '.$user.'</br>email: '.$email.'</br>';
		// print arguments
		$renderer->doc .= 'data: '.print_r($data, true).'</br>';
		// and mode
		$renderer->doc .= 'mode: '.$mode.'</br>';
	}
	
	
	// get db table as array
	// Eqyipment | Kategorie = Tauchen (named parameter), im Besitz, nicht verliehen
	// date added by displayEquipment function
	// fixed query: != ANY -> NOT IN
	function loanDivingEquipment() {
		$TCSdb = new TCSdb();
		return $TCSdb->query('
			SELECT id, Beschreibung
			FROM `tcs22inventar`
			WHERE `Kategorie`=:kategorie
			  AND `Zugang`<NOW()
			  AND (`Abgang`>NOW() OR `Abgang` = "0000-00-00")
			  AND `id` NOT IN (
			  	SELECT id
			  	FROM `tcs22inventarloan`
			  	WHERE `Rueckgabe`
			  		IS NULL
			  		OR `Rueckgabe` > NOW());',
			array(':kategorie'=>'Tauchen'));
	}


	function returnDivingEquipment() {
		$TCSdb = new TCSdb();
		return $TCSdb->query('
			SELECT `tcs22inventarloan`.`id`, `tcs22inventar`.`Beschreibung`, `tcs22inventarloan`.`Ausgabe` 
			FROM `tcs22inventar`, `tcs22inventarloan` 
			WHERE `Kategorie`=:kategorie 
			  AND `tcs22inventar`.`id` = `tcs22inventarloan`.`id`   
			  AND `tcs22inventarloan`.`Ausgabe`<NOW() 
			  AND `tcs22inventarloan`.`Rueckgabe` IS NULL;',
			array(':kategorie'=>'Tauchen'));
	}
	
	function getDiving() {
		$TCSdb = new TCSdb();
		return $TCSdb->query('
			SELECT * 
			FROM `tcs22inventar` 
			WHERE `Kategorie`=:kategorie 
			  AND `Zugang`<NOW() 
			  AND (`Abgang`>NOW() 
			   	OR `Abgang` = "0000-00-00");',
			array(':kategorie'=>'Tauchen'));
	}

	// like getDiving, but Kategorie == Notfall
	function getEmergency() {
		$TCSdb = new TCSdb();
		return $TCSdb->query('
			SELECT * 
			FROM `tcs22inventar` 
			WHERE `Kategorie`=:kategorie 
			  AND `Zugang`<NOW() 
			  AND (`Abgang`>NOW() 
			           OR `Abgang` = "0000-00-00");',
			array(':kategorie'=>'Notfall'));
	}
	
	function getTraining() {
		$TCSdb = new TCSdb();
		return $TCSdb->query('
			SELECT * 
			FROM `tcs22inventar` 
			WHERE `Kategorie`=:kategorie 
			  AND `Zugang`<NOW() 
			  AND (`Abgang`>NOW() 
				   OR `Abgang` = "0000-00-00");',
			array(':kategorie'=>'Lehrmaterial'));
	}

	function getUWR() {
		$TCSdb = new TCSdb();
		return $TCSdb->query('
			SELECT * 
			FROM `tcs22inventar` 
			WHERE `Kategorie`=:kategorie 
			  AND `Zugang`<NOW() 
			  AND (`Abgang`>NOW() 
				OR `Abgang` = "0000-00-00");',
			array(':kategorie'=>'UWR'));
	}

	// show whole tcs22inventar table
	function showInventar() {
		$TCSdb = new TCSdb();
		return $TCSdb->query('SELECT * FROM `tcs22inventar`');
	}

	// show whole tcs22inventarloan table
	function showInventarLoan() {
		$TCSdb = new TCSdb();
		return $TCSdb->query('SELECT * FROM `tcs22inventarloan`');
	}
	
	// display array as table, adds a column for loan date and a button "buchen"
	// given default loan date appendet at end of table if not NULL
	// $R: renderer, where to append the table
	// $rows: SQL query result
	// table rendering from here: https://github.com/cosmocode/dokuwiki-plugin-dbquery/blob/master/syntax/query.php
	function displayEquipment($R, $rows, $loanDate) {
		// refresh button: process input
				if($loanDate) $R->doc .= '<button type="submit" class="TCSbtn" value="chgBookings" name="refresh" title="aktualisieren">buchen</button>';
		// table
		$R->table_open(NULL, NULL, NULL, NULL);
		// header
		$R->tablethead_open();
		$R->tablerow_open();
		foreach($rows[0] as $key => $value) {
			$R->tableheader_open(1, 'center', 1, 'trainingHeader');
			$R->doc .= $key;
			$R->tableheader_close();
		}
		if($loanDate) {
			$R->tableheader_open(1, 'center', 1, 'trainingHeader');
			$R->doc .= 'Aus-/Rückgabe';
			$R->tableheader_close();
		}
		$R->tablerow_close();
		$R->tablethead_close();
		// body
		// column alignment
		$col_align = array('right', 'left', 'center', 'center', 'center', 'center');
		foreach($rows as &$row) {
			$col = 0;
			$colInput = count($row);
			$R->tablerow_open();
			foreach($row as &$cell) {
				$R->tablecell_open(1, $col_align[$col]);
				$R->doc .= $cell;
				$R->tablecell_close();
				$col++;
			}
			if($loanDate) {
				$R->tablecell_open(1, $col_align[$col]);
				// date only adjust database field - YYYY-MM-DD notation expected
//				$R->doc .= '<input type="date" value="'.$loanDate.'">';
				// date and time - YYYY-MM-DD HH:MM notation expected
				$R->doc .= '<input type="datetime-local" value="'.$loanDate.'">';
				$R->tablecell_close();
			}
		}
		
		$R->table_close();
		return;
	}

	// New function: display equipment with checkboxes in a form
	// very similar to displayEquipment
	function displayEquipmentWithCheckboxes($R, $rows, $loanDate) {
		// Start the form (method can be POST)
		$R->doc .= '<form method="post">';
		// Add a submit button (it triggers processing in render())
		$R->doc .= '<button type="submit" name="submitLoanEquipment" value="1" class="TCSbtn" title="Ausgewähltes Tauchequipment ausleihen">Ausgewähltes Tauchequipment ausleihen</button>';

		// Open the table
		$R->table_open();
		// Header row: add an extra header for checkboxes
		$R->tablethead_open();
		$R->tablerow_open();
		// Checkbox column header
		$R->tableheader_open(1, 'center');
		$R->doc .= '';
		$R->tableheader_close();
		// Loop over first row keys to create the rest of the header
		if (!empty($rows)) {
			foreach(array_keys($rows[0]) as $key) {
				$R->tableheader_open(1, 'center');
				$R->doc .= $key;
				$R->tableheader_close();
			}
		}
		// Additional header for loan date
		$R->tableheader_open(1, 'center');
		$R->doc .= 'Ausleihdatum';
		$R->tableheader_close();
		$R->tablerow_close();
		$R->tablethead_close();

		// Table body: output each row
		foreach($rows as $row) {
			$R->tablerow_open();
			// First cell: checkbox – using the equipment id as the value
			$R->tablecell_open();
			$R->doc .= '<input type="checkbox" name="equipment_ids[]" value="'.htmlspecialchars($row['id']).'">';
			$R->tablecell_close();
			// Then output the data cells from the row
			foreach($row as $cell) {
				$R->tablecell_open();
				$R->doc .= $cell;
				$R->tablecell_close();
			}
			// Last cell: a datetime-local input for loan date.
			$R->tablecell_open();
			$R->doc .= '<input type="datetime-local" name="loan_date['.htmlspecialchars($row['id']).']" value="'.$loanDate.'">';
			$R->tablecell_close();
			$R->tablerow_close();
		}
		$R->table_close();
		// End the form
		// Add a second submit button at the bottom
		$R->doc .= '<button type="submit" name="submitLoanEquipment" value="1" class="TCSbtn" title="Ausgewähltes Tauchequipment ausleihen">Ausgewähltes Tauchequipment ausleihen</button>';
		$R->doc .= '</form>';
	}

	function displayReturnEquipmentWithCheckboxes($R, $rows, $loanDate) {
		// Start the form (method can be POST)
		$R->doc .= '<form method="post">';
		// Add a submit button (it triggers processing in render())
		$R->doc .= '<button type="submit" name="submitReturnEquipment" value="1" class="TCSbtn" title="Ausgewähltes Tauchequipment zurückgeben">Ausgewähltes Tauchequipment zurückgeben</button>';

		// Open the table
		$R->table_open();
		// Header row: add an extra header for checkboxes
		$R->tablethead_open();
		$R->tablerow_open();
		// Checkbox column header
		$R->tableheader_open(1, 'center');
		$R->doc .= '';
		$R->tableheader_close();
		// Loop over first row keys to create the rest of the header
		if (!empty($rows)) {
			foreach(array_keys($rows[0]) as $key) {
				$header = ($key === 'loanDate') ? 'Ausleihdatum' : $key; // db loanDate -> Ausleihdatum
				$R->tableheader_open(1, 'center');
				$R->doc .= $header;
				$R->tableheader_close();
			}
		}
		// Additional header for loan date
		$R->tableheader_open(1, 'center');
		$R->doc .= 'Rückgabedatum';
		$R->tableheader_close();
		$R->tablerow_close();
		$R->tablethead_close();

		// Table body: output each row
		foreach($rows as $row) {
			$R->tablerow_open();
			// First cell: checkbox – using the equipment id as the value
			$R->tablecell_open();
			$R->doc .= '<input type="checkbox" name="equipment_ids[]" value="'.htmlspecialchars($row['id']).'">';
			$R->tablecell_close();
			// Then output the data cells from the row
			foreach($row as $cell) {
				$R->tablecell_open();
				$R->doc .= $cell;
				$R->tablecell_close();
			}
			// Last cell: a datetime-local input for loan date.
			$R->tablecell_open();
			$R->doc .= '<input type="datetime-local" name="return_date['.htmlspecialchars($row['id']).']" value="'.$loanDate.'">';
			$R->tablecell_close();
			$R->tablerow_close();
		}
		$R->table_close();
		// End the form
		// Add a second submit button at the bottom
		$R->doc .= '<button type="submit" name="submitReturnEquipment" value="1" class="TCSbtn" title="Ausgewähltes Tauchequipment zurückgeben">Ausgewähltes Tauchequipment zurückgeben</button>';
		$R->doc .= '</form>';
	}


	// New function: process the submitted checkbox form
	function processLoanedEquipment() {
		$loanedItems = array();
		if(isset($_REQUEST['equipment_ids']) && is_array($_REQUEST['equipment_ids'])) {
			$selected_ids = $_REQUEST['equipment_ids'];
			$TCSdb = new TCSdb();
			foreach($selected_ids as $id) {
				$loan_date = isset($_REQUEST['loan_date'][$id]) ? $_REQUEST['loan_date'][$id] : date("Y-m-d H:i:s");
				$TCSdb->query('INSERT INTO `tcs22inventarloan` (`id`, `Ausgabe`) VALUES (:id, :loan_date)',
					array(':id' => $id, ':loan_date' => $loan_date)
				);
				// Retrieve equipment description for a friendly output
				$result = $TCSdb->query('SELECT Beschreibung FROM `tcs22inventar` WHERE id=:id', array(':id' => $id));
				$description = (!empty($result)) ? $result[0]['Beschreibung'] : 'Unknown equipment';
				$loanedItems[] = array('id' => $id, 'Beschreibung' => $description, 'Ausgabe' => $loan_date);
			}
		}
		return $loanedItems;
	}

	// return equipment, very similar to processLoanedEquipment
	function processReturnedEquipment() {
		$returnedItems = array();
		if(isset($_REQUEST['equipment_ids']) && is_array($_REQUEST['equipment_ids'])) {
			$selected_ids = $_REQUEST['equipment_ids'];
			$TCSdb = new TCSdb();
			foreach($selected_ids as $id) {
				$return_date = isset($_REQUEST['return_date'][$id]) ? $_REQUEST['return_date'][$id] : date("Y-m-d H:i:s");
				// Update the record to mark the item as returned (only if not already returned)
				$TCSdb->query('UPDATE `tcs22inventarloan` 
                               SET `Rueckgabe` = :return_date 
                               WHERE `id` = :id AND `Rueckgabe` IS NULL',
					array(':id' => $id, ':return_date' => $return_date));
				// Retrieve equipment description for friendly output
				$result = $TCSdb->query('SELECT Beschreibung FROM `tcs22inventar` WHERE id=:id', array(':id' => $id));
				$description = (!empty($result)) ? $result[0]['Beschreibung'] : 'Equipment ohne Beschreibung';
				$returnedItems[] = array('id' => $id, 'Beschreibung' => $description, 'Rueckgabe' => $return_date);
			}
		}
		return $returnedItems;
	}

	function getLoanedEquipment() {
		$TCSdb = new TCSdb();
		return $TCSdb->query('
        SELECT inv.id, inv.Beschreibung, loan.Ausgabe AS loanDate
        FROM `tcs22inventar` AS inv
        JOIN `tcs22inventarloan` AS loan ON inv.id = loan.id
        WHERE loan.Rueckgabe IS NULL OR loan.Rueckgabe > NOW()
    ');
	}

	function displayLoanedEquipment($R, $rows) {
//		$R->doc .= '<h3>Currently Loaned Equipment</h3>';
		$R->table_open();

		// Table header
		$R->tablethead_open();
		$R->tablerow_open();
		$headers = array('ID', 'Beschreibung', 'Ausleihdatum');
		foreach ($headers as $header) {
			$R->tableheader_open();
			$R->doc .= $header;
			$R->tableheader_close();
		}
		$R->tablerow_close();
		$R->tablethead_close();

		// Table body
		foreach ($rows as $row) {
			$R->tablerow_open();

			$R->tablecell_open();
			$R->doc .= htmlspecialchars($row['id']);
			$R->tablecell_close();

			$R->tablecell_open();
			$R->doc .= htmlspecialchars($row['Beschreibung']);
			$R->tablecell_close();

			$R->tablecell_open();
			$R->doc .= htmlspecialchars($row['loanDate']);
			$R->tablecell_close();

			$R->tablerow_close();
		}

		$R->table_close();
	}





}	
?>