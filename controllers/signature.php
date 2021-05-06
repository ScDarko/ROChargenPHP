<?php

/**
* @fileoverview signature.php, display a signature with player informations
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.0.0
*/


// Avoid direct access
defined("__ROOT__") OR die();


// Include Render class
require_once(  __ROOT__ . 'render/class.CharacterRender.php' );
require_once(  __ROOT__ . 'loaders/Bmp.php');



class Signature_Controller extends Controller {

	/**
	 * Load database, specify where to cache things
	 */
	public function __construct()
	{
		parent::loadDatabase();
		Cache::setNamespace('signature');
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
			$content = "";

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
		$chargen->action         =  CharacterRender::ACTION_SIT;
		$chargen->direction      =  CharacterRender::DIRECTION_SOUTHEAST;
		$chargen->body_animation =  0;
		$chargen->doridori       =  0;
		$chargen->loadFromSqlData($data);


		// Build background
		$player      =   $chargen->render();
		$output      =   imagecreatetruecolor( 194, 110 );
		$background  =   imagecreatefromjpeg( Cache::$path . "signature/data/background01.jpg");
		imagecopy( $output, $background, 2, 2, 0, 0, 190, 106 );
		imagecopy( $output, $player, 2, 2, 55, 80, imagesx($player)-55-4, imagesx($player)-90-4 );

		// Add emblem
		if( !empty($data['emblem_data']) ) {
			$binary = @gzuncompress(pack('H*', $data['emblem_data']));

			if( $binary && ($emblem = imagecreatefrombmpstring($binary)) ) {
				imagecopy( $output, $emblem, 194-6-24, 6, 0, 0, 24, 24 );
			}
		}

		// Set color for text
		$name_color   = imagecolorallocate($output, 254, 242, 183);
		$lvl_color    = imagecolorallocate($output, 240, 143, 89);
		$status_color = $data['online'] ? imagecolorallocate($output, 125,  196,  126 ) : imagecolorallocate($output, 190,  83,  83 );

		// Text align right func
		function imagestringright( &$img, $size, $x, $y, $str, $color ) {
			imagestring( $img, $size, $x - strlen($str) * imagefontwidth($size), $y, $str, $color );
		}

		// Draw text
		imagestringright( $output, 2, 187, 69, $data['name'], $name_color );
		imagestringright( $output, 2, 187, 82, $data['base_level'] . "/" . $data['job_level'], $lvl_color );
		imagestringright( $output, 2, 187, 93, $data['online'] ? "Online" : "Offline", $status_color );

		// Cache the image
		imagepng($output);
	}
}
