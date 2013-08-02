<?php

/**
* @fileoverview generator.php, display what the fuck you want !
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.0.0
*/


// Avoid direct access
defined("__ROOT__") OR die();


// Include Render class
require_once(  __ROOT__ . 'render/class.CharacterRender.php' );



class Generator_Controller extends Controller {

	/**
	 * Process entry
	 */
	public function process($sex, $jobid, $clothes_color, $hair, $hair_color, $doridori, $head_top, $head_mid, $head_bottom, $weapon, $shield, $robe, $option, $direction, $action, $animation )
	{
		header('Content-type:image/png');
		header('Cache-Control: max-age=30000, public');

		// Load Class and set parameters
		// intval() is needed because parameters are received as
		// string but compared with "===" to int (which result to false:  "5" === 5 -> false).
		$chargen                 = new CharacterRender();
		$chargen->action         = intval($action);
		$chargen->direction      = intval($direction);
		$chargen->body_animation = intval($animation);

		$chargen->sex            = $sex;
		$chargen->class          = intval($jobid);
		$chargen->clothes_color  = intval($clothes_color);

		$chargen->hair           = intval($hair);
		$chargen->hair_color     = intval($hair_color);
		$chargen->doridori       = intval($doridori);

		$chargen->head_top       = intval($head_top);
		$chargen->head_mid       = intval($head_mid);
		$chargen->head_bottom    = intval($head_bottom);

		$chargen->weapon         = intval($weapon);
		$chargen->shield         = intval($shield);
		$chargen->robe           = intval($robe);

		$chargen->option         = intval($option);

		// Generate Image
		$img  = $chargen->render();

		imagepng($img);
	}
}
