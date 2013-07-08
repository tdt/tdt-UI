<?php
/**
 * Loads settings.json and makes its contents available
 *
 * @copyright (C) 2013 by OKFN Belgium
 * @license AGPLv3
 * @author Leen De Baets
 * @author Jeppe Knockaert
 * @author Nicolas Dierck
 */

class ConfigLoader{	

    function __construct() {
       	$this->settings = json_decode(self::stripComments(file_get_contents(__DIR__.'/config/settings.json')));
   	}

   	/**
   	 * Returns the value of the specified key, as found in settings.json.
   	 * @param $key the key of the required setting
   	 * @return the value of the requested setting
   	 */
   	public function getSettings($key){
   		return $this->settings->$key;
   	}

    /**
     * Removes the commented lines from the json file (because the comments can't be parsed as json)
     * @param $content the contents of the json file
     */
    public static function stripComments($content){
        $ret = preg_replace('/^(\s|\t)*\/\/.*$/m', "", $content);
        return trim($ret);
    }
}

?>
