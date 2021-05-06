<?php

/**
 * @fileoverview Item - Generator for Item Collection
 * @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
 * @editor Github: @sparkymod - Discord: Sparkmod#1935
 * @version 2.2
 */

defined("__ROOT__") OR die();

require_once(  __ROOT__ . 'core/BMP.php' );

class ItemCollection_Controller extends Controller {
    
    public $img_file;
    
    public function process($pseudo) {
        $raw_dir = __ROOT__ . 'client\lua files\iteminfo.lua';
        $ii_dir  = str_replace('\\', '/', $raw_dir);
        if(file_exists($ii_dir))
            $this->getfromlua($pseudo,$ii_dir);
        else
            die("Error! Cannot find " . $ii_dir);
        
        if(null != $this->img_file)
            $this->dupeImage();
        else
            die("Item#$pseudo does not exist!");
    }
    
    public function getfromtxt($pseudo) {
        $raw_file  = DB::get_id2resname_path();
        $file_path = Client::getFile($raw_file.'.txt');
        
        $contents = file_get_contents($file_path);
        
        if($contents)
        {
            $pattern = '/'.$pseudo.'#(.*?)#/s';
            preg_match($pattern, $contents, $match);
            $this->img_file = $match[1];
        } else {
            echo "File does not exist!";
        }
    }
    
    public function getfromlua($pseudo,$ii_dir) {
        $contents = file_get_contents($ii_dir);
        if($contents)
        {
            // GET ENTIRE INFO OF ITEM#$PSEUDO
            $pattern = '/\['.$pseudo.'\](.*?)[\}].*?(\}).*?(\})/s';
            preg_match($pattern, $contents, $match);
            $obj = $match[0];
            // GET IDENTIFIED DESCRIPTION OF ITEM#$PSEUDO
            $pattern = '/identifiedResourceName = "(?!.*?identifiedResourceName = ")(.*?)"/s';
            preg_match($pattern, $obj, $descObj);
            $this->img_file = $descObj[1];
        }
    }
    
    public function dupeImage() {
        $raw_file = DB::get_item_collection_path($this->img_file);
        $file_path = Client::getFile($raw_file.'.bmp');
        
        if(file_get_contents($file_path)) {
            header('Content-type: image/png');
            $im = imagecreatefrombmp($file_path);
            $purple = imagecolorallocate($im, 255, 0, 255);
            imagecolortransparent($im,$purple);
            imagepng($im);
            imagedestroy($im);
        } else {
            echo "File does not exist!";
        }
    }
    
    function imagebmp(&$img, $filename = false)
    {
        return BMP::imagebmp($img, $filename);
    }
    
    function imagecreatefrombmp($filename)
    {
        return BMP::imagecreatefrombmp($filename);
    }
}