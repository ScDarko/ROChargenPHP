<?php

/**
* @fileoverview Action - Loader for the Gravity .act file
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 2.3.2
*/


class Action
{

	private $fp, $version;
	public $actions = array(), $sounds  = array();


	/**
	 * Loader on construct
	 */
	public function __construct($filename=false)
	{
		if ( $filename )
			$this->open($filename);
	}


	/**
	 * Open a Action file
	 * Do some check before.
	 */
	public function open($filename)
	{
		if ( substr( $filename, 0, 7 ) !== "data://" )
		{
			if ( ! file_exists($filename) )
				throw new Exception("ACT::open() - Can't find file '{$filename}'.");
	
			if ( ! is_readable($filename) )
				throw new Exception("ACT::open() - Can't read file '{$filename}'.");

			$this->size = filesize($filename);
	
			if ( $this->size < 0x08 )
				throw new Exception("ACT::open() - Incorrect file size, shoulnot be a ACT file");
		}

		$this->fp = fopen($filename,'r');

		extract( unpack( "a2head/C2ver", fread($this->fp, 0x4 ) ) );

		if ( $head !== 'AC' )
			throw new Exception("ACT::load() - Incorrect act header, is '{$head}' - should be 'AC'");

		$this->version = $ver1/10 + $ver2;
		//$this->load();
	}


	/**
	 * Adding exclusively for the chargen to spee up the process.
	 */
	public function getAnimation($action, &$animation, $doridori=-1) {
		// Skip header
		fseek( $this->fp, 0x4, SEEK_SET);

		// Pre-calculate layer size
		     if( $this->version <   2.0 ) $layer_size   = 16;
		else if( $this->version <   2.4 ) $layer_size   = 32;
		else if( $this->version === 2.4 ) $layer_size   = 36;
		else                              $layer_size   = 44;


		// read actions
		extract( unpack("vaction_count/x10", fread( $this->fp, 12 ) ) );

		$action %= $action_count;

		for ( $i=0; $i<$action_count; ++$i ) {

			// read animation
			extract( unpack("Vanimation_count", fread( $this->fp, 0x04 ) ) );

			if( $i === $action ) {

				// doridori feature
				if( $doridori > -1 ) {
					$animation = floor( $animation_count / 3 ) * $doridori;
				}

				// Cap
				$animation   = min( $animation, $animation_count - 1 ); 
				$animation   = max( $animation, 0 );
			}

			for ( $j=0; $j<$animation_count; ++$j ) {
				fseek( $this->fp, 32, SEEK_CUR );

				// End.
				if( $action == $i && $animation == $j )
					return $this->readLayers();

				// read layers
				extract( unpack("Vlayer_count", fread( $this->fp, 0x04 ) ) );
				fseek( $this->fp, $layer_size * $layer_count, SEEK_CUR);

				if ( $this->version >= 2.0 )
					fseek( $this->fp, 0x04, SEEK_CUR);

				if ( $this->version >= 2.3 ) {
					extract( unpack("Vpos_count", fread($this->fp,0x04) ) );
					fseek( $this->fp, $pos_count * 16, SEEK_CUR);
				}
			}
		}

		 throw new Exception('Action not found ?');
	}


	/**
	 * Load an Action file
	 */
	public function load()
	{
		$this->readActions();

		if ( $this->version >= 2.1 ) {

			// Sound
			extract( unpack("Vcount", fread( $this->fp, 0x04 ) ) );
			for ( $i=0; $i<$count; ++$i )
				$this->sounds[$i] = current( unpack('a40', fread( $this->fp, 40 ) ) );

			// Delay
			if ( $this->version >= 2.2 )
				foreach( $this->actions as &$action )
					$action->delay = current( unpack("f", fread($this->fp, 0x04) ) ) * 25;
		}
	}


	/**
	 * Load ACT actions
	 */
	private function readActions()
	{
		extract( unpack("vcount/x10", fread( $this->fp, 12 ) ) );
		for ( $i=0; $i<$count; ++$i ) {
			$this->actions[$i] = (object) array(
				"animations" => $this->readAnimations(),
				"delay"      => 150
			);
		}
	}


	/**
	 * Load ACT animations
	 */
	private function readAnimations()
	{
		extract( unpack("Vcount", fread( $this->fp, 0x04 ) ) );
		$animations = array();
	
		for ( $i=0; $i<$count; ++$i ) {
			fseek( $this->fp, 32, SEEK_CUR );
			$animations[$i] = $this->readLayers();
		}
	
		return $animations;
	}


	/**
	 * Load ACT layers
	 */
	private function readLayers()
	{
		extract( unpack("Vcount", fread( $this->fp, 0x04 ) ) );
		$layers = array();


		if ( $this->version < 2.0 ) {
			$size   = 0;
			$struct = "";
		}

		else if ( $this->version < 2.4 ) {
			$size   = 16;
			$struct = "C4color/f1scale/Vangle/Vspr_type";
		}

		else if ( $this->version === 2.4 ) {
			$size   = 20;
			$struct = "C4color/f2scale/Vangle/Vspr_type";
		}

		else {
			$size   = 28;
			$struct = "C4color/f2scale/Vangle/Vspr_type/Vwidth/Vheight";
		}


		for ( $i=0; $i<$count; ++$i ) {
			$param      = unpack( "Vx/Vy/lindex/Vis_mirror/" . $struct, fread($this->fp, $size + 16) );
			$layers[$i] = (object) array(
				"pos"       => array( $param['x'], $param['y'] ),
				"index"     => $param['index'],
				"is_mirror" => $param['is_mirror'],
				"scale"     => array( 1.0, 1.0 ),
				"color"     => array( 255, 255, 255, 255 ),
				"angle"     => 0,
				"spr_type"  => 0,
				"width"     => 0,
				"height"    => 0
			);
			$layer      = &$layers[$i];

			$layer->color[0] = $param['color1']/255;
			$layer->color[1] = $param['color2']/255;
			$layer->color[2] = $param['color3']/255;
			$layer->color[3] = $param['color4']/255;

			$layer->scale[0] = isset($param['scale1']) ? $param['scale1'] : 1.0;
			$layer->scale[1] = isset($param['scale2']) ? $param['scale2'] : $layer->scale[0];

			if ( isset($param['angle']) )    $layer->angle    = $param['angle'];
			if ( isset($param['spr_type']) ) $layer->spr_type = $param['spr_type'];
			if ( isset($param['width']) )    $layer->width    = $param['width'];
			if ( isset($param['height']) )   $layer->height   = $param['height'];
		}


		$sound = -1;
		if ( $this->version >= 2.0 )
			extract( unpack("Vsound", fread($this->fp,0x04) ) );


		$pos = array( (object) array('x' => 0, 'y' => 0) );
		if ( $this->version >= 2.3 ) {
			extract( unpack("Vcount", fread($this->fp,0x04) ) );
			for ( $i=0; $i<$count; ++$i ) {
				$pos[$i] = (object) unpack( "Vunk/Vx/Vy/Vattr", fread($this->fp,16) );
			}
		}

		return (object) array(
			"layers"    => $layers,
			"sound"     => $sound,
			"pos"       => $pos
		);
	}



	/**
	 * Compile an ACT file
	 */
	public function compile()
	{

		// Header
		$result  = pack('a2C2', 'AC', $this->version * 10 % 10, floor($this->version * 10)/10 );

		// Action count
		$result .= pack( 'vx10', count($this->actions) );

		// Compile each actions
		foreach( $this->actions as $action )
		{

			// Number of animations
			$result .= pack( 'V', count($action->animations) );

			// Compile animations
			foreach( $action->animations as $animation )
			{

				// 32 uknown offset + layers count
				$result .= pack( 'x32V', count($animation->layers) );

				// Compile layers
				foreach( $animation->layers as $layer )
				{
					$result .= pack('V4', $layer->pos[0], $layer->pos[1], $layer->index, $layer->is_mirror);

					if ( $this->version < 2.0 )
						continue;

					else if ( $this->version < 2.4 )
						$result .= pack(
							'C4fV2',
							$layer->color[0]*255,
							$layer->color[1]*255,
							$layer->color[2]*255,
							$layer->color[3]*255,
							$layer->scale[0],
							$layer->angle,
							$layer->spr_type
						);

					else if ( $this->version === 2.4 )
						$result .= pack(
							'C4f2V2',
							$layer->color[0]*255,
							$layer->color[1]*255,
							$layer->color[2]*255,
							$layer->color[3]*255,
							$layer->scale[0],
							$layer->scale[1],
							$layer->angle,
							$layer->spr_type
						);

					else 
						$result .= pack(
							'C4f2V4',
							$layer->color[0]*255, 
							$layer->color[1]*255,
							$layer->color[2]*255,
							$layer->color[3]*255,
							$layer->scale[0],
							$layer->scale[1],
							$layer->angle,
							$layer->spr_type,
							$layer->width,
							$layer->height
						);
				}


				// Animation sound
				if ( $this->version >= 2.0 )
				{
					$result .= pack('V', $animation->sound );
				}

				// Animation imf (head pos)
				if ( $this->version >= 2.3 )
				{
					$result .= pack('V', count($animation->pos) );
					foreach( $animation->pos as $pos )
					{
						$result .=  pack( 'V4', $pos->unk, $pos->x, $pos->y, $pos->attr );
					}
				}
			}
		}

		
		if ( $this->version >= 2.1 )
		{
			// Comple sounds
			$result .= pack( 'V', count($this->sounds) );
			foreach( $this->sounds as $sound )
			{
				$result .= pack( 'a40', $sound );
			}

			// Comple delay
			if ( $this->version >= 2.2 )
			{
				foreach( $this->actions as $action )
				{
					$result .= pack( 'f', $action->delay / 25 );
				}
			}
		}

		return $result;
	}
}

