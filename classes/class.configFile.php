<?php
namespace DBprocessing;

define ('TAG_CLASS', 'code class');

class configFile {
	private $confFileContent;
    private $classes;
	
	public function __construct($confFileName) {
        $classes=FALSE;
		if (!(isset($this->confFileContent))) {
            if(file_exists($confFileName)) {
                $this->confFileContent = file_get_contents($confFileName);
            }
            else $this->confFileContent = FALSE;
        }
    }

    public function getTagContent($identifiedTag) {
        [$tag, $identifier] = explode(' ', $identifiedTag, 2);
        if(preg_match('/<'.$identifiedTag.'>(.*?)<\/'.$tag.'>/s', $this->confFileContent, $matches)) return trim($matches[1]);
        else return FALSE;
    }

    public function configFileExists() {
        return ($this->confFileContent ? TRUE : FALSE);
    }

    private function setClasses($identifiedTag=NULL) {
        $identifiedTag = $identifiedTag ? $identifiedTag : TAG_CLASS;
        $classConfig = $this->getTagContent($identifiedTag);
        $classParams = str_replace(array('\\\\'.PHP_EOL, '\\\\ '.PHP_EOL, PHP_EOL), array('\\\\ ', '\\\\ ', '\\\\\ '), $classConfig);
        $classParams = explode('\\\\ ', $classParams);
        foreach($classParams as $key => $classParam) {
            [$classKey, $classValue] = explode('=', $classParam, 2);
            if(substr($classValue, -2) == '\\\\') $classValue = substr($classValue, 0, -2);
            $this->classes[$classKey] = $classValue;
        }
        return $this->classes;
    }

    public function getClass($key) {
        if(!$this->classes) $this->setClasses();
        if(array_key_exists($key, $this->classes)) return $this->classes[$key];
        else return 'x';
    }
}
