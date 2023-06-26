<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* DB class
*/
class DB {
	private $adaptor;
	private $credentials;

	/**
	 * Constructor
	 *
	 * @param	string	$adaptor
	 * @param	string	$hostname
	 * @param	string	$username
     * @param	string	$password
	 * @param	string	$database
	 * @param	int		$port
	 *
 	*/
	public function __construct($adaptor, $hostname, $username, $password, $database, $port = NULL) {
		$this->connect($adaptor, $hostname, $username, $password, $database, $port);
	}

	private function connect($adaptor, $hostname, $username, $password, $database, $port) {
		$class = 'DB\\' . $adaptor;

		if (class_exists($class)) {
			$this->adaptor = new $class($hostname, $username, $password, $database, $port);
		} else {
			throw new \Exception('Error: Could not load database adaptor ' . $adaptor . '!');
		}

		$this->credentials = array(
			'adaptor' => $adaptor,
			'hostname' => $hostname,
			'username' => $username,
			'password' => $password,
			'database' => $database,
			'port' => $port
		);
	}

	/**
     * 
     *
     * @param	string	$sql
	 * 
	 * @return	array
     */
	public function query($sql) {
		if (!$this->connected()) {
			$this->connect($this->credentials['adaptor'], $this->credentials['hostname'], $this->credentials['username'], $this->credentials['password'], $this->credentials['database'], $this->credentials['port']);
		}
		return $this->adaptor->query($sql);
	}

	public function autocommit($value = true) {
		return $this->adaptor->autocommit((bool) $value);
	}

	public function begin() {
		return $this->adaptor->begin();
	}

	public function commit() {
		return $this->adaptor->commit();
	}

	public function rollback() {
		return $this->adaptor->rollback();
	}

	/**
     * 
     *
     * @param	string	$value
	 * 
	 * @return	string
     */
	public function escape($value) {
		return $this->adaptor->escape($value);
	}

	/**
     * 
	 * 
	 * @return	int
     */
	public function countAffected() {
		return $this->adaptor->countAffected();
	}

	/**
     * 
	 * 
	 * @return	int
     */
	public function getLastId() {
		return $this->adaptor->getLastId();
	}
	
	/**
     * 
	 * 
	 * @return	bool
     */	
	public function connected() {
		return $this->adaptor->connected();
	}
}