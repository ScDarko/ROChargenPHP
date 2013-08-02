<?php

/**
* @fileoverview RORender - Helper to render a character fully (include body, hair, hats, weapon, shields)
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.4.6
*/


// Avoid direct access
defined("__ROOT__") OR die();


require_once(  __ROOT__ . 'loaders/class.Action.php' );
require_once(  __ROOT__ . 'loaders/class.Sprite.php' );


abstract class RORender
{

	/**
	 * Actions constants
	 */
	const ACTION_IDLE       = 0;
	const ACTION_WALK       = 0;
	const ACTION_SIT        = 0;
	const ACTION_PICKUP     = 0;
	const ACTION_READYFIGHT = 0;
	const ACTION_ATTACK     = 0;
	const ACTION_HURT       = 0;
	const ACTION_UNK1       = 0;
	const ACTION_DIE        = 0;
	const ACTION_UNK2       = 0;
	const ACTION_ATTACK2    = 0;
	const ACTION_ATTACK3    = 0;
	const ACTION_SKILL      = 0;


	/**
	 * Direction constantes
	 */ 
	const DIRECTION_SOUTH     =  0;
	const DIRECTION_SOUTHWEST =  1;
	const DIRECTION_WEST      =  2;
	const DIRECTION_NORTHWEST =  3;
	const DIRECTION_NORTH     =  4;
	const DIRECTION_NORTHEAST =  5;
	const DIRECTION_EAST      =  6;
	const DIRECTION_SOUTHEAST =  7;


	/**
	 * Public options
	 */
	public $action            = 0;
	public $direction         = 0;
	protected $body_animation = 0;


	/**
	 * Image options, size and dot reference
	 */
	public $image_size              = array(200,200);
	public $dot_reference           = array(100,180);
	static public $background_color = array(0xff, 0xff, 0xff, 0x7f); // RGBA


	/**
	 * Force user to define funcion
	 */
	abstract public function render();


	/**
	 * Setter hooking
	 */
	public function __set( $name, $value )
	{
		if ( isset($this->param[$name]) )
		{
			$this->param[$name] = $value;
		}
	}


	/**
	 * Creating Image
	 */
	protected function createImage( $width=0, $height=0 )
	{
		// Default value
		if( empty($width) && empty($height) ) {
			$width  = $this->image_size[0];
			$height = $this->image_size[1];
		}

		// Create Image
		$img = imagecreatetruecolor( $width, $height );
		imagealphablending( $img, false);
		imagesavealpha( $img, true);

		// Set on the background
		$transparent = imagecolorallocatealpha(
			$img,
			self::$background_color[0],
			self::$background_color[1],
			self::$background_color[2],
			self::$background_color[3]
		);

		imagefill( $img, 0, 0, $transparent );
		imagecolortransparent( $img, $transparent );

		return $img;
	}


	/**
	 * Render an image
	 */
	final protected function renderImage( &$img, $param )
	{
		$file_spr = false;
		$file_act = false;

		// Nothing to render...
		if( isset($param['scale']) ) {
			if( $param['scale'] == 0 ) {
				return;
			}
		}

		// Use the same path
		if ( !empty($param['path']) )
		{
			$file_spr = Client::getFile( $param['path'] . '.spr' );
			$file_act = Client::getFile( $param['path'] . '.act' );
		}

		// Use different act file (pet accessory for example)
		else if ( !empty($param['spr']) && !empty($param['act']) )
		{
			if( $param['act'] === '.act' || $param['spr'] === '.spr' )
				return;

			$file_spr = Client::getFile( $param['spr'] );
			$file_act = Client::getFile( $param['act'] );

			// Act file not found, search for the act file near .spr ?
			if ( $file_act === false )
				$file_act = Client::getFile( current( explode('.spr', $param['spr']) ) . '.act' );
		}

		// Don't render if there is nothing to render (file not found or not defined)
		if ( $file_spr === false || $file_act === false )
			return;

		// Always render on top (except for robe on some case)
		$render_onTop = true;

		// Calculate action and animation
		$action     = (
			max( 0, $this->action * 8) +
			max( 0, $this->direction ) % 8
		);

		// Doridori mod only with some conditions
		if (
				get_class($this) === 'CharacterHeadRender' ||       // Only head
			(
				get_class($this) === 'CharacterRender' &&           // Only for character
				!empty($param['head']) &&                           // Only for head
				( $this->action === CharacterRender::ACTION_IDLE || // Only when sit or stand
				  $this->action === CharacterRender::ACTION_SIT )
			)
		) {
			$doridori = $this->doridori % 3;
			$anim     = 0;
		}

		// Basique
		else
		{
			// All animations are link to the body animation
			// If we update it, we have to update others.
			if( !empty($param['body']) ) {
				$anim = &$this->body_animation;

				// Durty trick with doridori on body.
				if( get_class($this) === 'CharacterRender' &&
					($this->action === CharacterRender::ACTION_IDLE ||
				 	 $this->action === CharacterRender::ACTION_SIT )
				) {
					$this->doridori %= 3;
					$anim = $this->doridori;
				}
			}
			else {
				$anim =  $this->body_animation;
			}

			$doridori = -1;
		}


		// Load spr and act
		$spr       = new Sprite( $file_spr );
		$act       = new Action( $file_act );
		$animation = $act->getAnimation($action, $anim, $doridori);


		// If have palette, load it
		if ( !empty($param['pal']) )
		{
			if ( $file_pal = Client::getFile($param['pal']) )
				$spr->palette = file_get_contents($file_pal);
		}

		$pos        = $animation->pos[0];
		$reference  = isset($param['pos']) ? $param['pos'] : $pos;

		// Robe can be display behind the character some times
		if ( isset($param['robe']) )
		{
			$render_onTop = DB::robe_ontop( $this->param['class'], $this->param['sex'], $param['robe'], $action, $anim );
		}


		// Hardcod shield zIndex feature
		else if ( isset($param['shield']) )
		{
			$direction    = max( 0, $this->direction % 8 );
			$render_onTop = (
				$direction !== self::DIRECTION_NORTHWEST &&
				$direction !== self::DIRECTION_NORTHEAST &&
				$direction !== self::DIRECTION_NORTH
			);
		}

		// Robe zIndex
		if ( !$render_onTop )
		{
			$_img  = $img;
			$img   = $this->createImage();
		}


		// Draw all layers
		foreach( $animation->layers as $layer )
		{
			// Avoid rendering empty images
			if ( $layer->index > -1 )
			{
				// RGBA image is after pal image
				$index  = $layer->index;
				if ( $layer->spr_type === Sprite::TYPE_RGBA )
					$index += $spr->rgba_index;


				// Build sprite image
				$image  = $spr->getImage( $index, $layer->is_mirror, $layer->color, self::$background_color );

				$width  = imagesx($image);
				$height = imagesy($image);
				$scale  = $layer->scale;

				// Main scale feature
				if( isset($param['scale']) ) {
					$scale[0] *= $param['scale'];
					$scale[1] *= $param['scale'];
				}

				// Generate scale
				if ( $scale[0] !== 1.0 && $scale[1] !== 1.0 )
				{
					// New size
					$w   = $width  * $scale[0];
					$h   = $height * $scale[1];

					// Copy image to new layer (resize)
					$tmp = $this->createImage($w, $h);
					imagecopyresampled( $tmp, $image, 0, 0, 0, 0, $w, $h, $width, $height );
					imagedestroy( $image );

					$image  = $tmp;
					$width  = $w;
					$height = $h;
				}

				// Convert palette to true color
				else if ( !imageistruecolor($image) )
				{
					$tmp = $this->createImage($width, $height);
					imagecopy( $tmp, $image, 0, 0, 0, 0, $width, $height );
					imagedestroy( $image );

					$image = $tmp;
				}

				// Apply a rotation
				if ( !empty($layer->angle) )
				{
					$image  = imagerotate( $image, -$layer->angle, imagecolortransparent($image), 1 );
					$width  = imagesx($image);
					$height = imagesy($image);
				}

				// Calculate the position
				$x      = $this->dot_reference[0] - $width /2 + $layer->pos[0] + $reference->x - intval( $pos->x );
				$y      = $this->dot_reference[1] - $height/2 + $layer->pos[1] + $reference->y - intval( $pos->y );

				// Copy image to main layer
				imagecopymerge(
					$img,
					$image,
					$x, $y,
					0, 0,
					$width, $height,
					$layer->color[3] * 100
				);

				imagedestroy($image);
			}
		}


		// Robe/shield ontop
		if ( !$render_onTop )
		{
			imagecopy(
				$img,
				$_img, 0, 0, 0, 0,
				$this->image_size[0],
				$this->image_size[1]
			);
		}

		// Return its pos for reference
		return $pos;
	}
}

