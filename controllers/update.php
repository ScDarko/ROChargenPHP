<?php

/**
* @fileoverview update.php, display an avatar with player informations
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.0.0
*/


// Avoid direct access
defined("__ROOT__") OR die();



class Update_Controller extends Controller {


	/**
	 * Process entry
	 */
	public function process($type)
	{
		header('Content-type:text/plain');

		switch( strtolower($type) ) {
			case 'hats':   $this->updateHats();     break;
			case 'mobs':   $this->updateMonsters(); break;
			case 'robes':  $this->updateRobes();    break;
			default:       exit('Invalid type specified');
		}

		exit('Update success');
	}


	/**
	 * Check if files exists
	 */
	private function needFiles()
	{
		$args = func_get_args();
		foreach( $args as $path ) {
			if( !file_exists( Client::$path . $path ) ) {
				exit('File "'. Client::$path . $path .'" not found.');
			}
		}
	}


	/**
	 * Updating DB files (hats)
	 */
	private function updateHats()
	{
		$error  = "";
		$buffer = $this->parse_easy(array(
			"keys" => array(
				"lua" => "lua files/datainfo/accessoryid.lua",
				"reg" => "/ACCESSORY_([a-zA-Z0-9_-]+)(\s+)?\=(\s+)?(\d+)(,)?/"
			),
			"vals" => array(
				"lua" => "lua files/datainfo/accname.lua",
				"reg" => "/\[ACCESSORY_IDs\.ACCESSORY_([a-zA-Z0-9_-]+)\](\s+)?=(\s+)?\"_(.*)\"(,)?/"
			)
		), $error );

		$this->Output(
			"Hats view id",
			$buffer,
			$error,
			DB::$path . "hats.php"
		);
	}


	/**
	 * Updating DB files (mobs)
	 */
	private function updateMonsters()
	{
		$error  = "";
		$buffer = $this->parse_easy(array(
			"keys" => array(
				"lua" => "lua files/datainfo/npcidentity.lua",
				"reg" => '/\["JT_([^"]+)"\](\s+)?\=(\s+)?(\d+)(,)?/'
			),
			"vals" => array(
				"lua" => "lua files/datainfo/jobname.lua",
				"reg" => '/\[jobtbl\.JT_([^]]+)\](\s+)?=(\s+)?"([^"]+)"(,)?/'
			)
		), $error );

		$this->Output(
			"Monsters view id",
			$buffer,
			$error,
			DB::$path . "mobs.php"
		);
	}


	/**
	 * Updating DB files (robes)
	 */
	private function updateRobes()
	{
		$this->needFiles(
			"lua files/skillinfoz/jobinheritlist.lua",
			"lua files/spreditinfo/2dlayerdir_f.lua",
			"lua files/spreditinfo/biglayerdir_female.lua",
			"lua files/spreditinfo/biglayerdir_male.lua",
			"lua files/spreditinfo/smalllayerdir_female.lua",
			"lua files/spreditinfo/smalllayerdir_male.lua",
			"lua files/spreditinfo/2dlayerdir_female.lua",
			"lua files/spreditinfo/2dlayerdir_male.lua"
		);

		$keys      = array();
		$keys_data = file_get_contents( Client::$path . "lua files/skillinfoz/jobinheritlist.lua");

		// JT_key = val,
		preg_match_all( "/JT_([^\s]+)(\s+)?\=(\s+)?(\d+)(,)?/", $keys_data, $matches );
		foreach( $matches[1] as $index => $name ) {
			$keys[ $name ] = $matches[4][$index];
		}


		// Inherit
		$extends = file_get_contents( Client::$path . "lua files/spreditinfo/2dlayerdir_f.lua");
		preg_match_all( "/\[JOBID\.JT_([^\]]+)\]\s=\sJOBID\.JT_([^\,]+)/", $extends, $matches );
		$buffer = "";
		$error  = "";

		foreach( $matches[1] as $index => $name ) {
			if( !isset($keys[ $name ]) ) {
				$error .= "// Fail to find '". $name . "'\n";
				continue;
			}
			$buffer .= "\t". $keys[ $name ] ." => ". $keys[ $matches[2][$index] ] .",\n";
		}

		$this->Output(
			"Robe inherit Job",
			$buffer,
			$error,
			DB::$path . 'inherit.robe.php'
		);


		$list = array(
			"big_F.robe.php"     => "lua files/spreditinfo/biglayerdir_female.lua",
			"big_M.robe.php"     => "lua files/spreditinfo/biglayerdir_male.lua",
			"small_F.robe.php"   => "lua files/spreditinfo/smalllayerdir_female.lua",
			"small_M.robe.php"   => "lua files/spreditinfo/smalllayerdir_male.lua",
			"2dlayer_F.robe.php" => "lua files/spreditinfo/2dlayerdir_female.lua",
			"2dlayer_M.robe.php" => "lua files/spreditinfo/2dlayerdir_male.lua"
		);

		foreach( $list as $php_file => $lua_file ) {
			$content = file_get_contents( Client::$path . $lua_file );
			$error   = "";
			$this->Output(
				"Robe zIndex feature",
				$this->parse_harder($keys, $content, $error),
				$error,
				DB::$path . $php_file
			);
		}
	}


	/**
	 * Lua easy file parser
	 */
	private function parse_easy($data, &$error )
	{
		$this->needFiles(
			$data['keys']['lua'],
			$data['vals']['lua']
		);

		$keys      = array();
		$vals      = array();

		// Parse keys
		$keys_data = file_get_contents( Client::$path . $data['keys']['lua']);
		preg_match_all( $data['keys']['reg'], $keys_data, $matches );
		foreach( $matches[1] as $index => $name ) {
			$keys[ $name ] = $matches[4][$index];
		}

		// Parse vals
		$vals_data = file_get_contents( Client::$path . $data['vals']['lua']);
		preg_match_all( $data['vals']['reg'], $vals_data, $matches );
		foreach( $matches[1] as $index => $name ) {
			if( !isset($keys[$name]) ) {
				$error .= "// Fail to find '". $name . "'\n";
				continue;
			}
			$vals[ $keys[$name] ] = $matches[4][$index];
		}

		// Sort and output
		ksort($vals);
		settype( $buffer, "string");
		foreach( $vals as $key => $val ) {
			$buffer .= "\t{$key} => '{$val}',\n";
		}

		return substr( $buffer, 0, -2 );
	}


	/**
	 * Convert lua to php (harder)
	 */
	private function parse_harder($keys, $data, &$error)
	{
		$data = strstr( $data, '[' );

		// [JOBID.JT_...] = { <data>
		preg_match_all( '/\[JOBID\.JT_([^]]+)\](\s)?\=(\s)?\{/', $data, $matches );

		// Remplace keys
		foreach( $matches[0] as $index => $array ) {
			if( !isset($keys[ $matches[1][$index] ]) ) {
				$error .= "// Fail to find '". $matches[1][$index] . "'\n";
				continue;
			}
			$data = str_replace( $array, $keys[ $matches[1][$index] ] . " => array(", $data );
		}

		// Remove:
		// - comment
		// - [d] = ...   -> d => ...
		// - some error on "}" and ","
		$data = preg_replace('/--([^\n]+)/', '', $data);
		$data = preg_replace('/\[(\d+)\](\s+)?=(\s+)?\{(.*)\}(,)?/', '$1 => array($4)$5', $data);
		$data = preg_replace('/(\,)?(\s+)?\n\t\}(\,)?/', "\n\t)$3", $data );

		return "\t" . substr($data,0,-1);
	}


	/**
	 * Output the content
	 */
	private function Output( $title, $content, $error, $path )
	{
		$buffer = <<<EOF
<?php

/**
 * @fileoverview {$title}
 * @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
 * @version 1.0.0
 */

{$error}
return array(
{$content}
);

EOF;

		file_put_contents( $path, $buffer );
	}

}
