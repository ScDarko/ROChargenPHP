<?php

/**
* @fileoverview Client - File Manager
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.5.1
*/


// Avoid direct access
defined("__ROOT__") OR die();


require_once(  __ROOT__ . 'loaders/class.Grf.php' );


final class Client
{
	/**
	 * Define the client dir
	 */
	static public $path        = "";
	static public $data_ini    = "";
	static private $grfs       = array();
	static public $AutoExtract = false;



	/**
	 * Load on init
	 */
	static public function init()
	{
		Debug::write('Client init...', 'title');

		// Load GRFs from DATA.INI
		if ( !empty(self::$data_ini) && file_exists(self::$path . self::$data_ini) && is_readable(self::$path . self::$data_ini) ) {
			Debug::write('Loading "'. self::$data_ini .'" file...', 'info');

			// Setup GRF context
			$data_ini = parse_ini_file( self::$path . self::$data_ini, true );
			$grfs     = array();

			foreach( $data_ini['Data'] as $index => $grf_filename ) {
				self::$grfs[$index] = new Grf(self::$path . $grf_filename);
				self::$grfs[$index]->filename = $grf_filename;
				$grfs[] = $grf_filename;
			}

			Debug::write('GRFs found in "'. self::$data_ini .'": '. implode(', ', $grfs), 'success' );
			return;
		}

		Debug::write('File "'. self::$data_ini .'" isn\'t load : not set, not found, or not readable in "'. self::$path .'".', 'error');
	}



	/**
	 * Get a file from client, search it on data dir first, and on grfs.
	 */
	static public function getFile($path)
	{
		Debug::write('Trying to find file "'. $path .'"...', 'title');

		$local_path  = self::$path;
		$local_path .= str_replace('\\', '/', $path );
		$grf_path    = str_replace('/', '\\', $path );

		// Read data first
		if ( file_exists($local_path) && !is_dir($local_path) && is_readable($local_path) ) {
			Debug::write('Find at "'. $local_path .'"', 'success');
			return $local_path;
		}

		// Search in GRFS
		Debug::write('File not found in data folder.');
		if( count(self::$grfs) ) {
			Debug::write('Searching in GRFs...');
		}

		foreach( self::$grfs as $grf ) {

			// Load GRF just if needed
			if( !$grf->loaded ) {
				Debug::write('Loading GRF file "'. $grf->filename .'"...', 'info');
				$grf->load();
			}

			// If file is found
			if( $grf->getFile($grf_path, $content) ) {

				Debug::write('Search in GRF "'. $grf->filename .'", found.', 'success');

				// Auto Extract GRF files ?
				if( self::$AutoExtract ) {

					Debug::write('Saving file to data folder...', 'info');

					$current_path = self::$path;
					$directories  = explode('/', $path);
					array_pop($directories);

					// Creating directories
					foreach( $directories as $dir ) {
						$current_path .= $dir . DIRECTORY_SEPARATOR;

						if( !file_exists($current_path) ) {
							mkdir( $current_path );
						}
					}

					// Saving file
					file_put_contents( $local_path, $content);
					return $local_path;
				}

				return "data://application/octet-stream;base64," .  base64_encode($content);
			}

			Debug::write('Search in GRF "'. $grf->filename .'", fail.');
		}

		Debug::write('File not found...', 'error');
		return false;
	}


	/**
	 * Search files (only work in GRF)
	 */
	static public function search($filter) {
		$out = array();

		foreach( self::$grfs as $grf ) {

			// Load GRF only if needed
			if( !$grf->loaded ) {
				$grf->load();
			}

			// Search
			$list = $grf->search($filter);

			// Merge
			$out  = array_unique( array_merge($out, $list ) );
		}

		//sort($out);
		return $out;
	}
}
