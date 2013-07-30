<?php

/**
* @fileoverview Cache Manager
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.5.1
*/

final class Cache
{
	static public $path       = "";
	static public $time       =  0;
	static private $directory = "";
	static private $filename  = "";


	/**
	 * Find a file in cache
	 */
	static public function get(&$content)
	{
		$path = self::$path . DIRECTORY_SEPARATOR . self::$directory . DIRECTORY_SEPARATOR . self::$filename;

		if( file_exists($path) && is_readable($path) ) {

			if( filemtime($path) + self::$time > time() ) {
				$content = file_get_contents($path);
				return true;
			}

			unlink($path);
		}

		return false;
	}


	/**
	 * Set a directory where to save files
	 */
	static public function setNamespace($name)
	{
		self::$directory = $name;
	}


	/**
	 * Set a filename
	 */
	static public function setFilename($name)
	{
		self::$filename = $name;
	}


	/**
	 * Store a file in cache
	 */
	static public function save()
	{
		// Cache not disable
		if( self::$time > 0 ) {
			$path         = self::$directory . DIRECTORY_SEPARATOR . self::$filename;
			$current_path = self::$path;
			$directories  = explode('/', $path);
			array_pop($directories); // remove filename

			// Creating directories
			foreach( $directories as $dir ) {
				$current_path .= $dir . DIRECTORY_SEPARATOR;
				if( !file_exists($current_path) ) {
					mkdir( $current_path );
				}
			}

			// Saving content
			file_put_contents( self::$path . $path, ob_get_contents() );
		}
	}
}
