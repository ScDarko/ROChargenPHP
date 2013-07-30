<?php

/**
* @fileoverview Debugging
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.0.0
*/

final class Debug
{
	static private $messages = array();
	static private $actived  = false;


	/**
	 * Add a message to the list
	 */
	static public function write($message, $class='')
	{
		self::$messages[] = '<div class="'.$class.'">'. $message . '</div>';
	}

	/**
	 * Enable
	 */
	static public function enable()
	{
		self::$actived = true;
		
	}

	/**
	 * Disable debug mode
	 */
	static public function disable()
	{
		self::$actived = false;
	}

	/**
	 * Is in debug mode ?
	 */
	static public function isEnable()
	{
		return self::$actived;
	}

	/**
	 * Output the log
	 */
	static public function output() {
		if( self::$actived ) {
			$img = ob_get_contents();
			ob_end_clean();
	
			header('Content-type:text/html');

			echo '<style type="text/css">';
			echo '.info { color:#c50; }';
			echo '.error { color:#f00; font-weight:bold; }';
			echo '.success { color:#080; }';
			echo '.title { margin-top:20px; font-weight:bold; color: #05A; }';
			echo '</style>';

			echo '<h1>Trace output</h1>';
			echo implode('', self::$messages);

			if( !empty($img) ) {
				echo '<h1>Result</h1>';
				echo '<img src="data:image/png;base64,'. base64_encode($img) .'" alt="not able to render..." border="1"/>';
			}
		}
	}
}
