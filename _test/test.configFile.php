<?php

use DBprocessing\configFile;

require_once('../classes/class.configFile.php');
echo "configFile.php successfull required!".PHP_EOL;

$configFile = new configFile('./configFile.txt');
echo $configFile->getTagContent('code sql select');
echo PHP_EOL;

var_dump($configFile->getClass('Inventarnr.'));
echo PHP_EOL;
var_dump($configFile->getClass('Beschreibung'));
echo PHP_EOL;
var_dump($configFile->getClass('Kategorie'));
echo PHP_EOL;
var_dump($configFile->getClass('Zugang'));
echo PHP_EOL;
var_dump($configFile->getClass('Abgang'));
echo PHP_EOL;
var_dump($configFile->getClass('test'));
echo PHP_EOL;

?>