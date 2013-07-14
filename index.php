<?php

/**
* @fileoverview index.php, Dispatcher
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 2.0.0
*/


ob_start();



// Error manager
ini_set('display_errors', 0);
error_reporting(0);
//ini_set('display_errors', 1);
//error_reporting(E_ALL);



define('__ROOT__', dirname(__FILE__) . '/'); 



// Loading CORE files
require_once( __ROOT__ . 'core/class.Controller.php');
require_once( __ROOT__ . 'core/class.Cache.php');
require_once( __ROOT__ . 'core/class.Client.php');
require_once( __ROOT__ . 'core/class.DB.php');




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



// Url Rewriting
$routes = array();
$routes['/avatar/(.*)']              = 'Avatar';
$routes['/character/(.*)']           = 'Character';
$routes['/characterhead/(.*)']       = 'CharacterHead';
$routes['/monster/(\d+)']            = 'Monster';
$routes['/signature/(.*)']           = 'Signature';
//$routes['/update/(hats|mobs|robes)'] = 'Update'; // Uncomment this line if you want to perform updates by updating lua files.


// Initialize client
Client::init();


// Run controller
Controller::run($routes);


?>