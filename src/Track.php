<?php

namespace royfee\tracking;

use royfee\tracking\exception\InvalidArgumentException;
use royfee\tracking\exception\InvalidConfigException;
use royfee\tracking\support\Config;
use royfee\tracking\common\BaseTrack;
/**
 * 物流追踪类
 *
 * @package addons\epay\library
 */
class Track extends BaseTrack
{
    private $driver;
    private $config;

	public function __construct(){
		//放在extra/tracking.php
		$this->config = config('tracking');

		if(empty($this->config)){
			throw new InvalidConfigException('Configuration file does not exist');
		}
	}

	/**
		追踪物流轨迹信息
		@para[
			'original': boolean 原生返回，返回格式如return，默认进行格式处理
			'default_list':array 默认加进去的物流轨迹，加入自定义物流轨迹，默认返回为真
			'sort':轨迹排序方式 A 升序  D 降序 默认 D 降序
			'isgroup':物流轨迹分组 默认 false
		]

		@return [
			'ret'	=>	true,
			'list'	=>	[
				[
					"desc" => "已完成处理，准备离开",
					"loca" => "香港",
					"time" => "2020-10-17 16:48:00",
				]
		]
	*/
	public function tracking($track,array $param = []){
		if(strpos($track,'.') === false){
			$this->driver = $this->config['default'];
		}else{
			$info = explode('.',$track);
			$this->driver = $info[0];
			$track = $info[1];
		}

		if(!isset($this->config[$this->driver])){
			throw new InvalidArgumentException("Configuration [$this->driver] is empty");
		}

		$gateway = $this->createGateway($this->driver);
		$result = $gateway->track($track);

		if(isset($param['original']) && $param['original']){
			return $result;
		}

		$return =  ['ret'=>$result['ret']];
		$trackList = [];
		if(isset($param['default_list']) && $param['default_list']){
			$trackList = array_merge($param['default_list'],$trackList);

			//如果设置了默认物流轨迹，则总是返回true
			$return['ret'] = true;

			if($result['ret'] === false){
				$trackList = array_merge($trackList,[
					[
						'desc'	=>	$result['msg'],
						'loca'	=>	'',
						'time'	=>	date('Y-m-d H:i:s'),
					]
				]);
			}
		}

		//轨迹排序方式
		$sort = isset($param['sort'])&&$param['sort']=='A'?'A':'D';
		if($return['ret']){
			$return['list'] = $trackList;

			if($result['ret']){
				$return['list'] = array_merge($return['list'],$result['list']);
			}

			//排序
			$sortFlag = array_map(function($arr){
				return $arr['time'];
			},$return['list']);

			array_multisort($sortFlag,$sort=='A'?SORT_ASC:SORT_DESC,$return['list']);

			//格式化
			$status = $this->getStatus($return['list'],$sort);
			$return['status'] = $status['status'];
			$return['recent'] = $status['recent'];

			if(isset($param['isgroup']) && $param['isgroup']){
				$return['list'] = $this->nodeGroup($return['list']);
			}
		}else{
			$return['msg'] = $result['msg'];
		}
		return $return;
	}

    protected function createGateway($gateway)
    {
        if (!file_exists(__DIR__ . '/gateway/' . ucfirst($gateway) . '/' . ucfirst($gateway) . 'Gateway.php')) {
            throw new InvalidArgumentException("Gateway [$gateway] is not supported.");
        }

        $gateway = __NAMESPACE__ . '\\gateway\\' . ucfirst($gateway) . '\\' . ucfirst($gateway) . 'Gateway';
        return $this->build($gateway);
    }

    protected function build($gateway)
    {
		return new $gateway($this->config[$this->driver]);
    }
}