<?php

/**
* @fileoverview Sprite - Loader for Gravity .spr file
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.3.2
*/

class Sprite
{
	/**
	 * Script constants
	 */
	const TYPE_INDEXED_RLE  =-1;
	const TYPE_INDEXED      = 0;
	const TYPE_RGBA         = 1;

	private $fp, $size, $version;
	private $indexed_count = 0, $rgba_count = 0;

	public $rgba_index = 0;
	public $frames     = array();
	public $palette;


	/**
	 * Constructor
	 * Open a file if specify
	 */
	public function __construct($filename=false)
	{
		if ( $filename ) {
			$this->open( $filename );
		}
	}


	/**
	 * Open a SPR file
	 * Do some check before.
	 */
	public function open($filename)
	{
		if ( substr( $filename, 0, 7) !== "data://" ) {
			if ( ! file_exists($filename) )
				throw new Exception("SPR::open() - Can't find file '{$filename}'.");

			if ( ! is_readable($filename) )
				throw new Exception("SPR::open() - Can't read file '{$filename}'.");

			$this->size = filesize($filename);
	
			if ( $this->size < 0x06 )
				throw new Exception("SPR::open() - Incorrect file size, shoulnot be a SPR file");
		}

		$this->fp = fopen($filename,'r');
		$this->load();
	}


	/**
	 * Parse a SPR file.
	 */
	private function load()
	{
		extract( unpack( "a2head/C2ver", fread($this->fp, 0x4 ) ) );

		// Check header
		if ( $head !== 'SP' ) {
			throw new Exception("SPR::load() - Incorrect sprite header, is '{$head}' - should be 'SP'");
		}

		// Get version
		$this->version = $ver1/10 + $ver2;
		$this->indexed_count = current( unpack( "v", fread($this->fp, 0x2) ) );


		if ( $this->version > 1.1 ) {
			$this->rgba_count = current( unpack( "v", fread($this->fp, 0x2) ) );
		}

		$this->rgba_index = $this->indexed_count;

	
		if ( $this->version < 2.1 ) {
			$this->readIndexedImage();
		}
		else {
			$this->readIndexedImageRLE();
		}

		$this->readRgbaImage();

		// Read palettes.
		if ( $this->version > 1.0 ) {
			$this->palette = fread( $this->fp, 1024 );
		}
	}


	/**
	 * Read indexed image (palette system)
	 */
	private function readIndexedImage()
	{
		for ( $i=0; $i < $this->indexed_count; ++$i ) {		
			$this->frames[$i] = (object) (
				unpack("vwidth/vheight", fread($this->fp, 0x04)) +
				array(
					"type"   => Sprite::TYPE_INDEXED,
					"offset" => ftell($this->fp),
					"data"   => ""
				)
			);
			fseek( $this->fp, $this->frames[$i]->width * $this->frames[$i]->height, SEEK_CUR);
		}
	}


	/**
	 * Parse SPR indexed images encoded with RLE
	 */
	private function readIndexedImageRLE()
	{
		for ( $i=0; $i < $this->indexed_count; ++$i ) {
			$this->frames[$i] = (object) (
				unpack("vwidth/vheight/vsize", fread($this->fp, 0x06)) +
				array(
					"type"   => Sprite::TYPE_INDEXED_RLE,
					"offset" => ftell($this->fp),
					"data"   => ""
				)
			);
			fseek( $this->fp, $this->frames[$i]->size, SEEK_CUR);
		}
	}


	/**
	 * Parse SPR rgba images
	 */
	private function readRgbaImage()
	{
		for ( $i=0, $f= $this->rgba_index; $i < $this->rgba_count; ++$i, $f++ ) {
			$this->frames[$f] = (object) (
				unpack("vwidth/vheight", fread($this->fp, 0x04)) +
				array(
					"type"   => Sprite::TYPE_RGBA,
					"offset" => ftell($this->fp),
					"data"   => ""
				)
			);
			fseek( $this->fp, $this->frames[$f]->width * $this->frames[$f]->height * 4, SEEK_CUR);
		}
	}


	/**
	 * Decode RLE
	 */
	private function RLE_decode( &$frame )
	{
		$frame->type = Sprite::TYPE_INDEXED;
		$index  = 0;
		$data   = $frame->data;
		$size   = $frame->size;
		$tmp    = "";

		while ( $index < $size ) {

			$c    = $data[ $index++ ];
			$tmp .= $c;

			if ( $c === "\x0" ) {
				$count = $data[$index++];
				if ( $count === "\x0" )
					$tmp .= $count;
				else
					$tmp .= str_repeat( $c, ord($count)-1 );
			}
		}

		$frame->data = $tmp;
	}


	/**
	 * Helper for rgba image (don't allocate more than needed)
	 */
	private function getColor( $img, $r, $g, $b, $a ) {
		$color = imagecolorexactalpha($img, $r, $g, $b, $a );
		if ( $color !== -1 )
			return $color;
		return imagecolorallocatealpha($img, $r, $g, $b, $a);
	}


	/**
	 * Return an image
	 */
	public function getImage( $index, $is_mirror = 0, $mult_color = array(1.0,1.0,1.0), $bg_color = array(255,255,255,127) )
	{
		$frame  = $this->frames[$index];
		$width  = $frame->width;
		$height = $frame->height;
		$type   = &$frame->type;
		$data   = &$frame->data;


		// Parse frame data
		if( !strlen($data) ) {
			fseek( $this->fp, $frame->offset, SEEK_SET );

			switch( $type ) {
				case Sprite::TYPE_INDEXED:
					$data = fread($this->fp, $width * $height );
					break;

				case Sprite::TYPE_INDEXED_RLE:
					$data = fread($this->fp, $frame->size );
					$this->RLE_decode( $frame );
					break;

				case Sprite::TYPE_RGBA:
					$data = fread($this->fp, $width * $height * 4 );
					break;
			}
		}


		// If palette.
		if ( $type === Sprite::TYPE_INDEXED ) {

			$img = imagecreate( $width, $height );

			// Allocate palette
			$palette = unpack('C1024', $this->palette);
			for ( $i=0, $j=1, $pal=array(); $j<1024; ++$i, $j+=4 ) {
				$pal[chr($i)] = imagecolorallocate(
					$img,
					// Apply color mult
					$palette[$j+0] * $mult_color[0],
					$palette[$j+1] * $mult_color[1],
					$palette[$j+2] * $mult_color[2]
				);
			}

			// Build image
			if ( $is_mirror ) {
				for ( $y=0, $i=0; $y<$height; $y++ ) {
					for( $x=$width-1; $x>-1; $x--, $i++ ) {
						if( $data[$i] !== "\x0" )
							imagesetpixel( $img, $x, $y, $pal[ $data[$i] ]);
					}
				}
			}
			else {
				for ( $y=0, $i=0; $y<$height; $y++ ) {
					for( $x=0; $x<$width; $x++, $i++ ) {
						if( $data[$i] !== "\x0" )
							imagesetpixel( $img, $x, $y, $pal[ $data[$i] ]);
					}
				}
			}
			// Replace white with a hacked white
			$index = imagecolorexact( $img, $bg_color[0], $bg_color[1], $bg_color[2] );
			if ( $index > -1 )
				imagecolorset( $img, $index, $bg_color[0], ( $bg_color[1] >= 255 ? $bg_color[1] - 1 : $bg_color[1] + 1 ), $bg_color[2] );

			// Set white as transparent
			imagecolorset( $img, 0 , $bg_color[0], $bg_color[1], $bg_color[2] );
			imagecolortransparent( $img, 0 );
		}

		// RGBA Images
		// TODO: Buggy RGBA image (lol PHP GD lol...)
		else {
			$img   = imagecreatetruecolor( $width, $height );
			$white = imagecolorallocatealpha( $img, $bg_color[0], $bg_color[1], $bg_color[2], $bg_color[3] );
			imagefill( $img, 0, 0, $white );
			imagecolortransparent( $img, $white );

			$pixels = unpack('C'. strlen($data), $data);
			if ( $is_mirror ) {
				for ( $y=$height-1, $i=1; $y>-1; $y-- ) {
					for( $x=$width-1; $x>-1; $x--, $i+=4 ) {
						if( $pixels[$i+0] > 0 ) {
							imagesetpixel( $img, $x, $y, $this->getColor(
								$img,
								$pixels[$i+3] * $mult_color[0],
								$pixels[$i+2] * $mult_color[1],
								$pixels[$i+1] * $mult_color[2],
								127 - $pixels[$i+0]/2
							));
						}
					}
				}
			}
			else {
				for ( $y=$height-1, $i=1; $y>-1; $y-- ) {
					for( $x=0; $x<$width; $x++, $i+=4 ) {
						if( $pixels[$i+0] > 0 ) {
							imagesetpixel( $img, $x, $y, $this->getColor(
								$img,
								$pixels[$i+3] * $mult_color[0],
								$pixels[$i+2] * $mult_color[1],
								$pixels[$i+1] * $mult_color[2],
								127 - $pixels[$i+0]/2
							));
						}
					}
				}
			}
		}


		// Return the result
		return $img;
	}
}

