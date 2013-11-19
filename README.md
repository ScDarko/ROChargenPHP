ROChargenPHP
========


> *ROChargenPHP* is a PHP tool made for Ragnarok Online private server to help them  to
> solved a big problem : how to display game characters on  the website ?
> So this tool comes at the rescue, parsing game data to render as an image the
> character/monster of your choice, base on your database or direct informations.


It required PHP5 with the PDO driver installed.
All the settings are set in index.php file.

Features
---------

- Generator
    * Support .spr, .act, .pal, .grf files
    * A character is fully render (class, head, hats, palettes, weapon, shield, robes, mount) and you
    can set the action, direction, animation easily.
	* Generator for Character, Character head only, Monsters, Homunculus, Avatar and Signature.
	* Cache system if needed
	* Emblem loader

- Client
	* DATA.ini files to set your own GRF to read files from.
	* Support GRF (0x200 version only without DES encryption) the data fodler is always read first.
	* Auto-Extract files from GRF if needed.
	* Updater script available to convert some lua files to PHP.

How to use
---------

```
	http://www.example.com/ROChargenPHP/controller/data
```
Replace *controller* by the one you want (currently: *character*, *characterhead*, *avatar*, *signature*).
Replace *data* with the info you want to send to the controller (by default the name of the player.
Example:
```
	http://www.example.com/ROChargenPHP/avatar/KeyWorld
```

Will display KeyWorld's avatar.
If you don't have url-rewriting in your host the link you will have to use:
```
	http://www.example.com/ROChargenPHP/index.php/avatar/KeyWorld
```

You can change the default link by modify the array *$routes* in the *index.php* file:
```php
$routes['/avatar/(.*)']              = 'Avatar';
$routes['/character/(.*)']           = 'Character';
$routes['/characterhead/(.*)']       = 'CharacterHead';
$routes['/monster/(\d+)']            = 'Monster';
$routes['/signature/(.*)']           = 'Signature';
```

Custom display
---------
At least, the tool is really easy to use, here an example on how to display a static character:
```php
$chargen                 =  new CharacterRender();
$chargen->action         =  CharacterRender::ACTION_READYFIGHT;
$chargen->direction      =  CharacterRender::DIRECTION_SOUTHEAST;
$chargen->body_animation =  0;
$chargen->doridori       =  0;

// Custom data:
$chargen->sex            =  "M";
$chargen->class          = 4002;
$chargen->clothes_color  =    0;
$chargen->hair           =    5;
$chargen->hair_color     =   12;
// ... head_top, head_mid, head_bottom, robe, weapon, shield, sex, ...


// Generate Image
$img  = $chargen->render();
imagepng($img);
```

-------
Avatar and signature
-------
![AvatarType1](http://upload.robrowser.com/chargen/avatar1.png) ![AvatarType2](http://upload.robrowser.com/chargen/avatar2.png) ![Signature](http://upload.robrowser.com/chargen/signature.png)

-------
Full character, or just the head
-------
![head](http://upload.robrowser.com/chargen/head-keyworld.png) ![body](http://upload.robrowser.com/chargen/body-keyworld.png)

-------
Monsters, without or with accessory
-------
![Eddga](http://upload.robrowser.com/chargen/1115.png) ![Poring](http://upload.robrowser.com/chargen/1002.png)

-------
License
---------
http://creativecommons.org/licenses/by/3.0/

![License](http://i.creativecommons.org/l/by/3.0/88x31.png) 
