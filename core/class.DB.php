<?php

/**
* @fileoverview DB - Manage files path
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @editor Github: @sparkymod - Discord: Sparkmod#1935
* @version 2.2
*/

final class DB
{
	static private $hair, $hats;
	static private $body, $pals;
	static private $weapon, $shield, $robes = array();
	static private $mobs, $pets;
	static private $shadow;
	static private $dorams = "도람족";
	static private $humans = "인간족";
	static private $isDoram = false;

	static private $ascii_sex = array(
		"M" => "남",
		"F" => "여"
	);

	static public $path = "db/";

	// Return shadow factor
	static public function get_shadow_factor($id) {
		if( empty(self::$shadow) )
			self::$shadow = require_once( self::$path . 'shadow.php');

		return isset(self::$shadow[$id]) ? self::$shadow[$id] : 1;
	}

	// Return Entity path
	static public function get_entity_path($id) {
		     if( $id < 45 )   return false; // character, don't render
		else if( $id < 46 )   return false; // warp
		else if( $id < 1000 ) return self::get_npc_path($id);
		else if( $id < 4000 ) return self::get_monster_path($id);
		else if( $id < 6000 ) return false; // character
		else if( $id < 7000 ) return self::get_homunculus_path($id);
		else if( $id > 10000) return self::get_monster_path($id);

		return false;
	}

	// Return npc path
	static public function get_npc_path($id)
	{
		// Load only if used
		if( empty(self::$mobs) ) {
			self::$mobs    = require_once( self::$path . 'mobs.php');
		}

		return "data/sprite/npc/" . strtolower( ( isset(self::$mobs[$id]) ? self::$mobs[$id] : "1_ETC_01") );
	}

	// Return homunculus path
	static public function get_homunculus_path($id)
	{
		// Load only if used
		if( empty(self::$mobs) ) {
			self::$mobs    = require_once( self::$path . 'mobs.php');
		}

		return "data/sprite/homun/" . strtolower(self::$mobs[ isset(self::$mobs[$id]) ? $id : 1002 ]);
	}

	// Return mob path
	static public function get_monster_path($id)
	{
		// Load only if used
		if( empty(self::$mobs) ) {
			self::$mobs    = require_once( self::$path . 'mobs.php');
		}
		
		return self::validate_path($id);
		
	}

	// Return pet accessory path
	static public function get_pet_accessory($id)
	{
		// Load only if used
		if( empty(self::$pets) ) {
			self::$pets    = require_once( self::$path . 'pets.php');
		}

		return isset(self::$pets[$id]) ? "data/sprite/몬스터/" . self::$pets[$id] : false;
	}

	// Return body path
	static public function get_body_path($id,$sex)
	{		
		$sex = self::$ascii_sex[$sex];
		
		// Load only if used
		if( empty(self::$body) ) {
			self::$body    = require_once( self::$path . 'body.php');
		}
		
		self::checkIfIsDoram($id);

		// Return either Dorams or Humans
		$body_path = self::$isDoram ? "data/sprite/".self::$dorams."/몸통/{$sex}/". self::$body[ isset(self::$body[$id]) ? $id : 0 ] ."_{$sex}"
		                            : "data/sprite/".self::$humans."/몸통/{$sex}/". self::$body[ isset(self::$body[$id]) ? $id : 0 ] ."_{$sex}";
		
		return $body_path;
		
	}

	// Return body pal path
	static public function get_body_pal_path($id,$sex,$pal)
	{
		$sex = self::$ascii_sex[$sex];

		if( empty(self::$pals) ) {
			self::$pals    = require_once( self::$path . 'pals.php');
		}
		if( empty(self::$robes['inherit']) ) {
			self::$robes['inherit'] = require_once( self::$path . 'inherit.robe.php');
		}
		if( !empty(self::$robes['inherit'][$id]) ){
			$id = self::$robes['inherit'][$id];
		}
		if ( $pal && isset(self::$pals[$id]) ) {
		    $body_pal_path = self::$isDoram ? "data/palette/".self::$dorams."/body/". self::$pals[$id] ."_{$sex}_{$pal}.pal"
		                                    : "data/palette/몸/". self::$pals[$id] ."_{$sex}_{$pal}.pal"; 
		    $validatePalPath = Client::getFile($body_pal_path);
		    
		    if (!$validatePalPath){
		        self::get_custome_body_pal_path($id,$sex,$pal);
		    }
		    
		    return $body_pal_path;
		}

		return false;
	}

	// Return head path
	static public function get_head_path($id,$sex)  
	{
		$_sex = self::$ascii_sex[$sex];
		
		// Return either Dorams or Humans
		$head_path = self::$isDoram ? "data/sprite/".self::$dorams."/머리통/{$_sex}/{$id}_{$_sex}"
		                            : "data/sprite/".self::$humans."/머리통/{$_sex}/{$id}_{$_sex}";
		return $head_path;
	}

	// Return head pal path
	static public function get_head_pal_path($id,$sex,$pal)
	{
		$sex = self::$ascii_sex[$sex];
		
		// Return either Dorams or Humans
		$head_pal_path = self::$isDoram ? "data/palette/".self::$dorams."/머리/머리{$id}_{$sex}_{$pal}.pal"
		                                : "data/palette/머리/머리{$id}_{$sex}_{$pal}.pal" ;
		
		return $pal ? $head_pal_path : false;
	}

	// Return hat path
	static public function get_hat_path($id,$sex)
	{
		$sex = self::$ascii_sex[$sex];

		if( empty(self::$hats) ) {
			self::$hats    = require_once( self::$path . 'hats.php');
		}
		
		$hat_path = self::$isDoram ? "data/sprite/악세사리/{$sex}_doram/{$sex}_" . self::$hats[$id]
		                           : "data/sprite/악세사리/{$sex}/{$sex}_" . self::$hats[$id] ;

		return isset(self::$hats[$id]) ? $hat_path : false;
	}

	// Return weapon path
	static public function get_weapon_path($job_id, $sex, $weapon_id )
	{
		$sex = self::$ascii_sex[$sex];

		if( empty(self::$weapon) ) self::$weapon  = require_once( self::$path . 'weapon.php');
		if( empty(self::$body) )   self::$body    = require_once( self::$path . 'body.php');

		$weapon_id = isset(self::$weapon[$weapon_id]) ? self::$weapon[$weapon_id] : $weapon_id;
		
		$weapon_path = self::$isDoram ? "data/sprite/".self::$dorams."/". self::$body[$job_id] ."/". self::$body[$job_id] ."_{$sex}_{$weapon_id}"
		                              : "data/sprite/인간족/". self::$body[$job_id] ."/". self::$body[$job_id] ."_{$sex}_{$weapon_id}" ;
		
		return isset(self::$body[$job_id]) ? $weapon_path : false;
	}

	// Return shield path
	static public function get_shield_path($job_id, $sex, $shield_id )
	{
		$sex = self::$ascii_sex[$sex];

		if( empty(self::$shield))  self::$shield  = require_once( self::$path . 'shield.php');
		if( empty(self::$body) )   self::$body    = require_once( self::$path . 'body.php');
		if( empty(self::$robes['inherit']) ) {
			self::$robes['inherit'] = require_once( self::$path . 'inherit.robe.php');
		}
		if( !empty(self::$robes['inherit'][$job_id]) )
			$job_id = self::$robes['inherit'][$job_id];

		$shield_id = isset(self::$shield[ $shield_id ]) ? self::$shield[ $shield_id ] : $shield_id;
		
		return isset(self::$body[$job_id]) ? "data/sprite/방패/". self::$body[$job_id] ."/". self::$body[$job_id] ."_{$sex}_{$shield_id}" : false;
	}

	// Return robe path
	static public function get_robe_path( $job_id, $sex, $robe_id )
	{
		$sex  = self::$ascii_sex[$sex];

		if( empty(self::$robes['list']) ) {
			self::$robes['list']    = require_once( self::$path . 'robe.php');
			self::$robes['inherit'] = require_once( self::$path . 'inherit.robe.php');
		}

		if ( empty(self::$robes['list'][$robe_id]) || !isset(self::$body[$job_id]) ) {
			return false;
		}

		return "data/sprite/로브/". self::$robes['list'][$robe_id]['name'] ."/{$sex}/". self::$body[$job_id] ."_{$sex}";
	}

	// idnum2itemresnametable path
	static public function get_id2resname_path() {
	    return "data/idnum2itemresnametable";
	}
	
	// idnum2itemdesctable path
	static public function get_id2desc_path() {
	    return "data/idnum2itemdesctable";
	}
	
	// Item path
	static public function get_item_path($file_name)
	{
	    return "data/texture/유저인터페이스/item/{$file_name}";
	}
	
	// Item Collection path
	static public function get_item_collection_path($file_name)
	{
	    return "data/texture/유저인터페이스/collection/{$file_name}";
	}

	// Return robe zIndex
	static public function robe_ontop( $job_id, $_sex, $robe_id, $action, $animation )
	{
		$sex  = self::$ascii_sex[$_sex];
		$size = '2dlayer';

		if ( $robe_id !== false ) {
			if ( empty(self::$robes['list'][$robe_id]) )
				return false;

			$size = self::$robes['list'][$robe_id]['size'];
		}

		if( !isset(self::$robes[$size]) ) {
			self::$robes[$size] = array();
		}

		if( empty(self::$robes[$size][$sex]) ) {
			self::$robes[$size][$sex] = require_once( self::$path . $size . '_'. $_sex .'.robe.php');
		}

		$table  = self::$robes[$size][$sex];

		if( !empty(self::$robes['inherit'][$job_id]) )
			$job_id = self::$robes['inherit'][$job_id];

		if ( empty($table[$job_id]) || empty($table[$job_id][$action]) ) return true;
		if ( in_array( $animation, $table[$job_id][$action] ) ) return false;

		return true;
	}

	// Check if is Doram
	static public function checkIfIsDoram($id)
	{
	    
	    self::$isDoram = $id >= 4218 && $id <= 4221 ? true : false ;
	}

	// Get customs Palette 
	static public function get_custome_body_pal_path($id, $sex,$pal){
	    for ($i = 1; $i < 5; $i++) {
	        $body_pal_path = "data/palette/몸/costume_".$i."/". self::$pals[$id] ."_{$sex}_{$pal}_".$i.".pal";
	        $validatePalPath = Client::getFile($body_pal_path);
	        if ($validatePalPath){
	            return $body_pal_path;
	        }
	    }
	}
	
	// Validate Upper and lower case for monster path
	static public function validate_path($id){
	    $monster_path = "data/sprite/몬스터/" . strtolower(self::$mobs[ isset(self::$mobs[$id]) ? $id : 1002 ]);
	    $validate_path = Client::getFile($monster_path.".spr");
	    
	    if($validate_path){
	        return $monster_path;
	    }
	    else{
	        $monster_path = "data/sprite/몬스터/" . self::$mobs[ isset(self::$mobs[$id]) ? $id : 1002 ];
	        $validate_path = Client::getFile($monster_path.".spr");
	        
	        if($validate_path){
	            return $monster_path;
	        }
	        else{
	            $monster_path = "data/sprite/몬스터/" . strtoupper(self::$mobs[ isset(self::$mobs[$id]) ? $id : 1002 ]);
	            $validate_path = Client::getFile($monster_path.".spr");
	            if($validate_path){
	                return $monster_path;
	            }
	            else{
	                return false;
	            }
	        }
	    }
	}
}
