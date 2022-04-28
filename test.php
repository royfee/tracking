<?php
    require('./vendor/autoload.php');

    use \royfee\tracking\Track;

    $track = new Track();
    $res = $track->config([
        /*
        'trackingmore' => [
            'apikey' => '
        ]
       */
        'track17'   =>  [
            'token' =>  ''
        ]
        
    ])->tracking('1186545870767');//,['original'=>true]EK485132442HKEK485132456HK

file_put_contents('file.txt',var_export($res,true));
var_dump($res);