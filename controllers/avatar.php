<?php

/**
* @fileoverview avatar.php, display an avatar with player informations
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.0.0
*/


// Avoid direct access
defined("__ROOT__") OR die();


// Include Render class
require_once(  __ROOT__ . 'render/class.CharacterRender.php' );
require_once(  __ROOT__ . 'loaders/Bmp.php');



class Avatar_Controller extends Controller {

	/**
	 * Load database, specify where to cache things
	 */
	public function __construct()
	{
		parent::loadDatabase();
		Cache::setNamespace('avatar');
	}


	/**
	 * Process entry
	 */
	public function process($pseudo)
	{
		header('Content-type:image/png');
		header('Cache-Control: max-age='. Cache::$time .', public');

		Cache::setFilename($pseudo . ".png");
		$content    = "";

		// Load the cache file ?
		if( Cache::get($content) ) {
			die( $content );
		}

		// Find and render
		$data = $this->getPlayerData($pseudo);
		$this->render($data);

		// Save
		Cache::save();
	}


	/**
	 * Get player data from SQL
	 */
	private function getPlayerData($pseudo)
	{
		$data = $this->query("
			SELECT
				char.name,
				char.class, char.clothes_color,
				char.hair, char.hair_color,
				char.head_top, char.head_mid, char.head_bottom,
				char.robe, char.weapon, char.shield,
				char.online, char.base_level, char.job_level,
				login.sex,
				guild.emblem_data
			FROM `char`
			LEFT JOIN `login` ON login.account_id = char.account_id
			LEFT JOIN `guild` ON guild.guild_id = char.guild_id
			WHERE char.name = ?
			LIMIT 1",
			array($pseudo)
		);

		// No player found ?
		// No character found ? Load a default character ?
		if( empty($data) )
		{
			Cache::setFilename("[notfound].png");
			$content    = "";

			if( Cache::get($content) ) {
				die($content);
			}

			return array(
				"class"         =>  0,
				"clothes_color" =>  0,
				"hair"          =>  2,
				"hair_color"    =>  0,
				"head_top"      =>  0,
				"head_mid"      =>  0,
				"head_bottom"   =>  0,
				"robe"          =>  0,
				"weapon"        =>  0,
				"shield"        =>  0,
				"sex"           => "M",
				"online"        =>  0,
				"base_level"    =>  0,
				"job_level"     =>  0,
				"name"          => "Unknown"
			);
		}

		return $data[0];
	}


	/**
	 * Render avatar
	 */
	private function render($data)
	{
		// Load Class and set parameters
		$chargen                 =  new CharacterRender();
		$chargen->action         =  CharacterRender::ACTION_IDLE;
		$chargen->direction      =  CharacterRender::DIRECTION_SOUTH;
		$chargen->body_animation =  0;
		$chargen->doridori       =  0;
		$chargen->loadFromSqlData($data);

		// Load images
		$player       =   $chargen->render();
		$border       =   imagecreatefrompng(  Cache::$path . "avatar/data/border.png" );
		$background   =   imagecreatefromjpeg( Cache::$path . "avatar/data/background01.jpg" );
		$output       =   imagecreatetruecolor( 128, 128 );

		// Build image
		imagecopy( $output, $background, 7, 7, 0, 0, 114, 114 );
		imagecopy( $output, $player, 7, 7, 35+7, 65+7, imagesx($player)-14, imagesx($player)-14 );
		imagecopy( $output, $border, 0, 0, 0, 0, 128, 128 );

		// Add emblem
		if( !empty($data['emblem_data']) ) {
			$binary = @gzuncompress(pack('H*', $data['emblem_data']));

			if( $binary && ($emblem = imagecreatefrombmpstring($binary)) ) {
				imagecopy( $output, $emblem, 128-10-24, 128-10-24, 0, 0, 24, 24 );
			}
		}

		// Set color for text
		$name_color   = imagecolorallocate($output, 122, 122, 122);
		$lvl_color    = imagecolorallocate($output, 185, 109, 179 );
		$status_color = $data['online'] ? imagecolorallocate($output, 59,  129,  44 ) : imagecolorallocate($output, 188,  98,  98 );

		// Draw text
		imagestring( $output, 1, 12, 12, strtoupper($data['name']), $name_color );
		imagestring( $output, 1, 12, 25, $data['base_level'] . "/" . $data['job_level'], $lvl_color );
		imagestring( $output, 1, 81, 12, $data['online'] ? "ONLINE" : "OFFLINE", $status_color );

		imagepng($output);
	}
}
