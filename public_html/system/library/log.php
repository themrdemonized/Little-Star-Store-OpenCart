<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Log class
*/
class Log {
	private $handle;
	private $filename;

	public function trimLog($file) {
		if(!is_file($file)) {
			return false;
		}
		$file_contents = file($file);
		$out_contents = array_slice($file_contents, -sizeof($file_contents) / 10);
		file_put_contents($file, implode('', $out_contents));
	}
	
	/**
	 * Constructor
	 *
	 * @param	string	$filename
 	*/

	public function __construct($filename) {
		//$logs = scandir(DIR_LOGS);
		//$trimmed_files = array();

		$this->filename = DIR_LOGS . $filename;
		$size = @filesize($this->filename);
		if ($size > 50000000) {
			$this->handle = fopen($this->filename, 'w');
		} else {
			if ($size > 5000000) {
				$this->trimLog($this->filename);
				//$trimmed_files[] = DIR_LOGS . $file;
			}
			$this->handle = fopen($this->filename, 'a');
		}
	}
	
	/**
     * 
     *
     * @param	string	$message
     */
	public function write($message) {
		fwrite($this->handle, date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n");
	}
	
	/**
     * 
     *
     */
	public function __destruct() {
		fclose($this->handle);
	}
}