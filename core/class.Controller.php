<?php

/**
* @fileoverview Controler - Control Manager class
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.0.0
*/


// Avoid direct access
defined("__ROOT__") OR die();


class Controller
{
	static public $hostname    = "";
	static public $database    = "";
	static public $username    = "";
	static public $password    = "";


	private $db;


	/**
	 * Fetch routes to dispatch the controller
	 */
	static public function run($routes)
	{
		if( empty($_SERVER['QUERY_STRING']) ) {
			if( empty($_SERVER['PATH_INFO']) ) {
				Debug::write('No route found.', 'error');
				return false;
			}
			$query = $_SERVER['PATH_INFO'];
		}
		else {
			$query = $_SERVER['QUERY_STRING'];
		}

		Debug::write('Searching route "'. $query .'".', 'title');

		foreach( $routes as $path => $controller ) {
		
			if( preg_match("/^" . str_replace("/", "\/", $path) . "$/", $query, $matches) ) {

				$fileName  = __ROOT__ . "controllers/" . strtolower($controller) . ".php";
				$className = $controller . "_Controller";
	
				if( file_exists($fileName) ) {

					Debug::write('Running controller "'. $controller .'".', 'info');

					require_once($fileName);
					call_user_func_array(
						array( new $className, "process"),
						array_slice( $matches, 1)
					);

					return true;
				}
			}
		}

		Debug::write('Route not found.', 'error');
		return false;
	}


	/**
	 * Initialize database
	 */
	public function loadDatabase()
	{
		Debug::write('Connecting to DB....', 'title');

		try {
			$this->db   = new PDO(
				"mysql:host=". self::$hostname .";dbname=". self::$database,
				self::$username, self::$password
			);
		} catch (PDOException $e) {
			Debug::write('Connection to DB fail.' . $e->getMessage(), 'error');
			Debug::output();
			exit;
		}

		Debug::write('Connection success.', 'success');
	}


	/**
	 * SQL Query helper
	 */
	public function query($query, $args=array()) {
		$req = $this->db->prepare($query);
		$req->execute($args);
		return $req->fetchAll();
	}
}
