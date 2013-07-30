<?php

/**
* @fileoverview characterhead.php, display character's head (only)
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.0.0
*/


// Avoid direct access
defined("__ROOT__") OR die();


// Include Render class
require_once(  __ROOT__ . 'render/class.CharacterHeadRender.php' );



class CharacterHead_Controller extends Controller {

	/**
	 * Load database, specify where to cache things
	 */
	public function __construct()
	{
		parent::loadDatabase();
		Cache::setNamespace('characterhead');
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

		// Cache
		Cache::save();
	}


	/**
	 * Get player data from SQL
	 */
	private function getPlayerData($pseudo)
	{
		$data = $this->query("
			SELECT
				char.hair, char.hair_color,
				char.head_top, char.head_mid, char.head_bottom,
				login.sex
			FROM `char`
			LEFT JOIN `login` ON login.account_id = char.account_id
			WHERE char.name = ?
			LIMIT 1",
			array($pseudo)
		);

		// No character found ? Load a default character ?
		if( empty($data) ) {

			// Store file, not needed to recalculate it each time
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
				"sex"           => "M"
			);
		}

		return $data[0];
	}


	/**
	 * Render avatar
	 */
	private function render($data)
	{
		// Load Sprites and set parameters
		$chargen                 =  new CharacterHeadRender();
		$chargen->direction      =  CharacterHeadRender::DIRECTION_SOUTH;
		$chargen->doridori       =  2;

		// Generate Image
		$chargen->loadFromSqlData($data);
		$img  = $chargen->render();

		imagepng($img);
	}

}
