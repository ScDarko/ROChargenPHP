<?php

/**
* @fileoverview index.php, Dispatcher
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 2.1.0
*/


ob_start();



// Error manager
ini_set('display_errors', 0);
error_reporting(E_ALL);



define('__ROOT__', dirname(__FILE__) . '/'); 



// Loading CORE files
require_once( __ROOT__ . 'core/class.Debug.php');
require_once( __ROOT__ . 'core/class.Controller.php');
require_once( __ROOT__ . 'core/class.Cache.php');
require_once( __ROOT__ . 'core/class.Client.php');
require_once( __ROOT__ . 'core/class.DB.php');



// Set on the debug
//Debug::enable();



/// Configs ---------------------------------------------------------
//- Cache
Cache::$path          =     __ROOT__ . "cache/"  ;   // Cache directory
Cache::$time          =     15 * 60              ;   // cache for 15 mins (set to 0 if you want to disable cache).
//- Client
Client::$path         =     __ROOT__ . "client/" ;   // Define where your client path is (where you put your grfs, data, etc.)
Client::$data_ini     =     "DATA.INI"           ;   // The name of your DATA.INI (to locate your grfs, if not set: grfs will not be loaded)
Client::$AutoExtract  =     true                 ;   // If true, client will save extracted files from GRF into the data folder.
//- DB
DB::$path             =     __ROOT__ . "db/"     ;   // The db folder (where is located the lua likes files)
//- Sql
Controller::$hostname =     "127.0.0.1"          ;   // Mysql Host
Controller::$database =     "ragnarok"           ;   // Database Name
Controller::$username =     "ragnarok"           ;   // Database Username
Controller::$password =     "ragnarok"           ;   // Database Pass
/// -----------------------------------------------------------------



// No write access to directory ? disable cache.
if( Cache::$time && !is_writable(Cache::$path) ) {
	Cache::$time = 0;
	Debug::write('Disable Cache system, don\'t have write acess to "'. Cache::$path .'".', 'error');
}
if( Client::$AutoExtract && !is_writable(Client::$path . 'data/') ) {
	Client::$AutoExtract = false;
	Debug::write('Disable GRF auto-extract mode, don\'t have write access to "'. Client::$path  .'data/".', 'error');
}



// Don't cache images when debug mode is on
if( Debug::isEnable() ) {
	Cache::$time = 0;
}



// Url Rewriting
$routes = array();
$routes['/character/(.*)']           = 'Character';
$routes['/characterhead/(.*)']       = 'CharacterHead';
$routes['/avatar/(.*)']              = 'Avatar';
$routes['/signature/(.*)']           = 'Signature';
$routes['/monster/(\d+)']            = 'Monster';
$routes['/generate/body=(F|M)-(\d+)-(\d+)/hair=(\d+)-(\d+)-(\d)/hats=(\d+)-(\d+)-(\d+)/equip=(\d+)-(\d+)-(\d+)/option=(\d+)/actdir=([0-7])-(\d+)-(\d+)'] = 'Generator';
//$routes['/update/(hats|mobs|robes)'] = 'Update'; // Uncomment this line if you want to perform updates by updating lua files.



try {
	// Initialize client and process
	Client::init();
	Controller::run($routes);
}
catch(Exception $e)
{
	Debug::write( $e->getMessage(), 'error');
}


// Debug
Debug::output();