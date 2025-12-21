<?php
namespace DBprocessing;

class configFile {
	private $confFileContent;
	
	function __construct($confFileName) {
		if (!(isset($this->confFileContent))) {
            if(file_exists($confFileName)) {
                $this->confFileContent = file_get_contents($confFileName);
            }
            else $this->confFileContent = FALSE;
        }
    }

    public function getTagContent($identifiedTag) {
        [$tag, $identifier] = explode(' ', $identifiedTag);
        preg_match('/<'.$identifiedTag.'>(.*?)<\/'.$tag.'>/s', $this->confFileContent, $matches);
        return trim($matches[1]);
    }

    function configFileExists() {
        return ($this->confFileContent ? TRUE : FALSE);
    }

}
