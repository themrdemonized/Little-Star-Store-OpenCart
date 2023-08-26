<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Language class
*/
class Language {
	
			
			public $default = 'en-gb';
			
			
	private $directory;
	public $data = array();
	
	/**
	 * Constructor
	 *
	 * @param	string	$file
	 *
 	*/
	public function __construct($directory = '') {
		$this->directory = $directory;
	}
	
	/**
     * 
     *
     * @param	string	$key
	 * 
	 * @return	string
     */
	public function get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : $key);
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	/**
     * 
     *
	 * @return	array
     */	
	public function all() {
		return $this->data;
	}
	
	/**
     * 
     *
     * @param	string	$filename
	 * @param	string	$key
	 * 
	 * @return	array
     */	
	
	
			public function load($filename, $key = '')
			{
				if (!$key) {
					$_ = array();

					$file = DIR_LANGUAGE.'english/'.$filename.'.php';
					$old_file = DIR_LANGUAGE.'english/'.str_replace('extension/', '', $filename).'.php';
		
					if (is_file($file)) require(modification($file));
					elseif (is_file($old_file)) require(modification($old_file));
			
					$file = DIR_LANGUAGE.$this->default.'/'.$filename.'.php';
					$old_file = DIR_LANGUAGE.$this->default.'/'.str_replace('extension/', '', $filename).'.php';
		
					if (is_file($file)) require(modification($file));
					elseif (is_file($old_file)) require(modification($old_file));
								
					$file = DIR_LANGUAGE.$this->directory.'/'.$filename.'.php';
					$old_file = DIR_LANGUAGE.$this->directory.'/'.str_replace('extension/', '', $filename).'.php';
		
					if (is_file($file)) require(modification($file));
					elseif (is_file($old_file)) require(modification($old_file));
								
					$this->data = array_merge($this->data, $_);
				} else {
					$this->data[$key] = new Language($this->directory);
					$this->data[$key]->load($filename);
				}
		
				return $this->data;
			}
				
			public function loadDefault($filename, $key = '') {
		if (!$key) {
			$_ = array();
	
			$file = DIR_LANGUAGE . $this->default . '/' . $filename . '.php';
	
			if (is_file($file)) {
				require(modification($file));
			}
	
			$file = DIR_LANGUAGE . $this->directory . '/' . $filename . '.php';
			
			if (is_file($file)) {
				require(modification($file));
			} 
	
			$this->data = array_merge($this->data, $_);
		} else {
			// Put the language into a sub key
			$this->data[$key] = new Language($this->directory);
			$this->data[$key]->load($filename);
		}
		
		return $this->data;
	}
}