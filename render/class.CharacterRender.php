<?php

/**
* @fileoverview CharacterRender - Helper to render a character fully (include body, hair, hats, weapon, shields)
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.4.5
*/


// Avoid direct access
defined("__ROOT__") OR die();


require_once(  __ROOT__ . 'render/class.RORender.php' );


class CharacterRender extends RORender
{

	/**
	 * Player actions constants
	 */
	const ACTION_IDLE       = 0;
	const ACTION_WALK       = 1;
	const ACTION_SIT        = 2;
	const ACTION_PICKUP     = 3;
	const ACTION_READYFIGHT = 4;
	const ACTION_ATTACK     = 5;
	const ACTION_HURT       = 6;
	const ACTION_UNK1       = 7;
	const ACTION_DIE        = 8;
	const ACTION_UNK2       = 9;
	const ACTION_ATTACK2    = 10;
	const ACTION_ATTACK3    = 11;
	const ACTION_SKILL      = 12;

	
	/**
	 * Character options
	 */
	protected $param = array(
		"sex"           => "M",
		"class"         =>  0 ,
		"clothes_color" =>  0 ,
		"hair"          =>  2 ,
		"hair_color"    =>  0 ,
		"head_top"      =>  0 ,
		"head_mid"      =>  0 ,
		"head_bottom"   =>  0 ,
		"shield"        =>  0 ,
		"weapon"        =>  0 ,
		"robe"          =>  0 ,
		"option"        =>  0
	);

	/**
	 * Public access
	 */
	public $body_animation = 0;
	public $doridori       = 0;


	/**
	 * Bind parameter with some SQL datas
	 */
	public function loadFromSqlData($args)
	{
		foreach( $this->param as $type => &$view )
		{
			if ( isset($args[$type]) )
				$view = $args[$type];
		}
	}


	/**
	 * Render, return an image
	 */
	public function render()
	{
		// Add mount, in future add falcon/cart
		$this->checkoption();

		// Initialised the image
		$img  = $this->createImage();
		$view = $this->param;

		// Secure doridori clamp( $this->doridori, 0, 2 );
		$this->doridori = min( max( $this->doridori, 0 ), 2 );

		// Draw shadow (shadow isn't render when player is sitting or dead).
		if ( $this->action !== self::ACTION_SIT && $this->action !== self::ACTION_DIE )
			$this->renderImage( $img, array(
				"path"  => "data/sprite/shadow",
				"scale" => DB::get_shadow_factor($view['class'])
			));

		/*
		// Falcon test
		$this->renderImage( $img, array(
			"path" => "data/sprite/ÀÌÆÑÆ®/¸Å2", 
			"pos"  => (object)array( 'x' => -10, 'y' => -140)
		));

		// Cart test
		$this->renderImage( $img, array(
			"path" => "data/sprite/ÀÌÆÑÆ®/¼Õ¼ö·¹3",
			"pos"  => (object)array( 'x' => -35, 'y' => 0)
		));
		*/

		// Draw body, get head position
		$pos = $this->renderImage( $img, array(
			"path" => DB::get_body_path( $view['class'], $view['sex'], $view['option'] ), 
			"pal"  => DB::get_body_pal_path( $view['class'], $view['sex'], $view['clothes_color'] ),
			"body" => true
		));

		// Draw Robe
		if ( !empty($view['robe']) )
			 $this->renderImage( $img, array(
			 	"path"  => DB::get_robe_path( $view['class'], $view['sex'], $view['robe'] ),
				"pos"   => $pos,
				"robe"  => $view['robe']
			));

		// Draw head
		$this->renderImage( $img, array(
			"path" => DB::get_head_path( $view['hair'], $view['sex'] ),
			"pal"  => DB::get_head_pal_path( $view['hair'], $view['sex'], $view['hair_color'] ),
			"pos"  => $pos,
			"head" => true
		));

		// Draw head top
		if ( !empty($view['head_top']) )
			 $this->renderImage( $img, array(
			 	"path"  => DB::get_hat_path( $view['head_top'], $view['sex'] ),
				"pos"   => $pos,
				"head"  => true
			));

		// Draw head mid
		if ( !empty($view['head_mid']) && $view['head_mid'] !== $view['head_top'] ) // Don't render the same sprite twice
			$this->renderImage( $img, array(
			 	"path" => DB::get_hat_path( $view['head_mid'], $view['sex'] ),
				"pos"  => $pos,
				"head" => true
			));

		// Draw head bot
		if ( !empty($view['head_bottom']) && $view['head_bottom'] !== $view['head_mid'] ) // Don't render the same sprite twice
			$this->renderImage( $img, array(
			 	"path"  => DB::get_hat_path( $view['head_bottom'], $view['sex'] ),
				"pos"   => $pos,
				"head"  => true
			));
			
		// Draw Weapon
		if ( !empty($view['weapon']) )
			$this->renderImage( $img, array(
			 	"path" => DB::get_weapon_path( $view['class'], $view['sex'], $view['weapon'] )
			));

		// Draw Shield
		if ( !empty($view['shield']) )
			$this->renderImage( $img, array(
			 	"path"   => DB::get_shield_path( $view['class'], $view['sex'], $view['shield'] ),
				"shield" => true
			));

		// Return image
		return $img;
	}
	

	/**
	 * Modify class base on option
	 */
	private function checkoption()
	{
		$option =  $this->param['option'];
		$id     = &$this->param['class'];

		$OPTION_SUMMER    =  0x00040000;
		$OPTION_WEDDING   =  0x00001000;
		$OPTION_XMAS      =  0x00010000;
		$OPTION_RIDING    =  0x00000020;
		$OPTION_DRAGON1   =  0x00080000;
		$OPTION_DRAGON2   =  0x00800000;
		$OPTION_DRAGON3   =  0x01000000;
		$OPTION_DRAGON4   =  0x02000000;
		$OPTION_DRAGON5   =  0x04000000;
		$OPTION_DRAGON    =  $OPTION_DRAGON1|$OPTION_DRAGON2|$OPTION_DRAGON3|$OPTION_DRAGON4|$OPTION_DRAGON5;
		$OPTION_WUGRIDER  =  0x00200000;
		$OPTION_MADOGEAR  =  0x00400000;
		$OPTION_MOUNTING  =  0x08000000;

		// Bonus job
		if ( $option & $OPTION_WEDDING ) { $id = 22; return; }
		if ( $option & $OPTION_XMAS )    { $id = 26; return; }
		if ( $option & $OPTION_SUMMER )  { $id = 27; return; }


		if ( $option & $OPTION_RIDING )
		{
			switch( $id )
			{
				case 7:    $id = 13;   return;   // Knight
				case 14:   $id = 21;   return;   // crusader
				case 4008: $id = 4014; return;   // LK
				case 4015: $id = 4022; return;   // Paladin
				case 4030: $id = 4036; return;   // Baby knight
				case 4037: $id = 4044; return;   // Baby crusader
				case 4054: $id = 4080; return;   // Rune Knight
				case 4060: $id = 4081; return;   // Rune Knight T
				case 4066: $id = 4082; return;   // Royal Guard
				case 4073; $id = 4083; return;   // Royal Guard T
				case 4096: $id = 4109; return;   // Baby Rune
				case 4102: $id = 4110; return;   // Baby Guard
			}
		}

		// Dragon color ?
		if ( $option & $OPTION_DRAGON )
		{
			if ( $id === 4054 ) { $id = 4080; return; } // Rune
			if ( $id === 4060 ) { $id = 4081; return; } // Rune T
			if ( $id === 4096 ) { $id = 4109; return; } // Baby Rune
		}

		// Ranger
		if ( $option & $OPTION_WUGRIDER )
		{
			if ( $id === 4056 ) { $id = 4084; return; } // Ranger
			if ( $id === 4062 ) { $id = 4085; return; } // Ranger T
			if ( $id === 4098 ) { $id = 4111; return; } // Baby Ranger
		}

		// Mechanic
		if ( $option & $OPTION_MADOGEAR )
		{
			if ( $id === 4058 ) { $id = 4086; return; } // Mechanic
			if ( $id === 4064 ) { $id = 4087; return; } // Mechanic T
			if ( $id === 4100 ) { $id = 4112; return; } // Baby Mechanic
		}

		// Mount 1st job system
		if ( $option & $OPTION_MOUNTING )
		{
			// TODO: 1st job mount don't change job id...
			// Add a way to check it in DB.get_body_path() ?
		}
	}
}

