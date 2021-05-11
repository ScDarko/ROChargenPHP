<?php

/**
* @fileoverview monster.php, display a monster
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.0.0
*/


// Avoid direct access
defined("__ROOT__") OR die();


// Include Render class
require_once(  __ROOT__ . 'render/class.MonsterRender.php' );



class Monster_Controller extends Controller {

	/**
	 * Load database, specify where to cache things
	 */
	public function __construct()
	{
		Cache::setNamespace('monster');
	}


	/**
	 * Process entry
	 */
	public function process($mobid)
	{
		header('Content-type:image/png');
		header('Cache-Control: max-age='. Cache::$time .', public');

		Cache::setFilename($mobid . ".png");
		$content    = "";

		// Load the cache file ?
		if( Cache::get($content) ) {
			die( $content );
		}

		// Render and cache
		$this->render($mobid);
		Cache::save();
	}


	/**
	 * Render avatar
	 */
	private function render($mobid)
	{
		// What you want
		$gen                 = new MonsterRender();
		$gen->action         = MonsterRender::ACTION_IDLE;         // See constantes in
		$gen->direction      = MonsterRender::DIRECTION_SOUTHEAST; // class.RORender.php
		$gen->body_animation = 0;
		$gen->class          = $mobid;
		//$gen->accessory      = 10013; // Pet accessory

		// Generate image
		$img = $gen->render();

		// Display image
		imagepng( $img );
	}

}
