<?php
    require('./vendor/autoload.php');

    use \royfee\tracking\Track;

    $track = new Track();
    $res = $track->config([
        /*
        'trackingmore' => [
            'apikey' => 'b3ab41cd-9d60-4cc2-991e-dc1763abe6f1'//c336bfbc-2c46-4e14-ba21-aee8669d843e
        ]
       */
        'track17'   =>  [
            'token' =>  '44F4F4002BA24A8FFC9A18033AC3A085'
        ]
        
    ])->tracking('1186545870767');//,['original'=>true]EK485132442HKEK485132456HK

file_put_contents('file.txt',var_export($res,true));
var_dump($res);