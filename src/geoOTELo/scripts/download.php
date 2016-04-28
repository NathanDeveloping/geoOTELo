<?php
/**
 * Created by PhpStorm.
 * User: ressources21
 * Date: 28/04/2016
 * Time: 14:09
 */

if(isset($_GET['file_name'])) {
    $config = parse_ini_file("../../config/config.ini");
    if(isset($config['file_directory'])) {
        if(is_dir($config['file_directory'])) {
            $path = searchPath($config['file_directory'], $_GET['file_name']);
            if(!empty($path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'. basename($path) .'";');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($path));
                readfile($path);
            }
        }
    }
}

function searchPath($dir, $file) {
    $res = "";
    $scDir = scandir($dir);
    foreach($scDir as $k => $v) {
        if(!in_array($v, array(".", ".."))) {
            $dirValue = $dir . DIRECTORY_SEPARATOR . $v;
            if(is_dir($dirValue)) {
                $res.=  searchPath($dirValue, $file);
            } else {
                if(strcmp($file . ".xlsx", $v) == 0) {
                    $res.= $dirValue;
                    break;
                }
            }
        }
    }
    return $res;
}