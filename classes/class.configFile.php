<?php
namespace DBprocessing;

define ('TAG_CLASS', 'code class');
define ('TAG_BUTTON', 'code button');

class configFile {
	private $confFileContent;
    private $classes;
    private $buttons;
	
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

    private function getSettings($identifierTag) {
        $config = $this->getTagContent($identifierTag);
        $params = str_replace(array('\\\\'.PHP_EOL, '\\\\ '.PHP_EOL, PHP_EOL), array('\\\\ ', '\\\\ ', '\\\\ '), $config);
        $params = explode('\\\\ ', $params);
        foreach($params as $key => $value) {
            [$subKey, $subValue] = explode('=', $value, 2);
            if(substr($subValue, -2) == '\\\\') $subValue = substr($subValue, 0, -2);
            $ret[$subKey] = $subValue;
        }
        return $ret;
    }

    public function getClass($key) {
        if(!(isset($this->classes))) $this->classes = $this->getSettings(TAG_CLASS);
        if(array_key_exists($key, $this->classes)) return $this->classes[$key];
        else return '';
    }

    public function getButton($button) {
        if(!(isset($this->buttons))) $this->buttons = $this->getSettings(TAG_BUTTON);
        if(array_key_exists($button, $this->buttons)) return $this->buttons[$button];
        else return FALSE;
    }
}
