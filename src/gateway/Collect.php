<?php
namespace royfee\tracking\gateway;
/***
 * 轨迹采集
 */
class Collect{
        //支持的采集
    protected $allow = [
        'MY'  =>    'MCollection',
        'KK'  =>    'MCollection',
        'SG'  =>    'MCollection',
        'PC'  =>    'MCollection',
    ];

    public function tracking(array $trackList){
        $return = ['untrack'=>[],'tracked'=>[]];
        foreach($trackList as $key => $number){
            $pre = substr($number,0,2);
            if(isset($this->allow[$pre])){
                $collectGateway = '\royfee\tracking\collection\\'.$this->allow[$pre];
                $clt = new $collectGateway;

                //采集
                $result = $clt->track($number);
                if($result === false){
                    $return['untrack'][] = $number;
                }else{
                    $return['tracked'][] = $result;
                }
            }else{
                $return['untrack'][] = $number;
            }
        }
        return $return;
    }
}