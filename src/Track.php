<?php
namespace royfee\tracking;

use royfee\tracking\exception\InvalidArgumentException;
use royfee\tracking\exception\InvalidConfigException;
use royfee\tracking\exception\InvalidGatewayException;
use royfee\tracking\support\Config;
use royfee\tracking\common\BaseTrack;

/**
 * 物流追踪类
 *
 * @package addons\epay\library
 */
class Track extends BaseTrack{
	public function __construct($config = []){
		$this->config = $config;

		$this->driver = $this->config['default'];

		if(empty($this->driver)){
			throw new InvalidArgumentException("Driver is empty");
		}

		if(empty($this->config[$this->driver])){
			throw new InvalidArgumentException("Configuration [$this->driver] is empty");
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
			'recent' => '已签收',
			'status' => 4,
		]
	*/
	public function tracking($trackNumber,array $param = []){
		//获取对应的运输商
		//$this->driver = array_keys($this->config)[0];

		//调用对应第三方的查询轨迹
		$result = $this->createGateway($this->driver)->track($trackNumber);

		if($param['original']??false){
			return $result;
		}

		$trackList = [];
		if(isset($param['default_list'])){
			$trackList = array_merge($param['default_list'],$trackList);
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

		//合并轨迹记录
		if($result['ret']){
			$trackList = array_merge($result['list'],$trackList);
		}

		
		//对轨迹进行按照时间排序
		$sort = $param['sort'] ??'desc';

		$trackList = $this->sortNode($trackList,$sort);
		
		//状态处理
		//$state = $this->getStatus($trackList,$sort);

		//是否对轨迹进行分组
		if($param['isgroup']??false){
			$trackList = $this->nodeGroup($trackList);
		}
		
		if($trackList){
			return [
				'ret'	=>	true,
				'list'	=>	$trackList,
				'latest'	=>	$result['latest']??[
					'status'	=>	null,//状态
					'status_sub'=>	null,//子状态
					'desc'		=>	null,//最新轨迹
					'time'		=>	null,//最新时间
				],
			];
		}

		//轨迹为空的时候
		if($result['ret']){
			return $result;
		}

		return [
			'ret'	=>	 false,
			'msg'	=>	 $result['msg'],
		];
	}

	/**
	 * 订阅通知
	 * jsonBody 通知的报文
	 * param[
	 * 	sort	排序方式  asc|desc
	 * ]
	 */
	public function notify($jsonBody,$param = []){
		$gateway = $this->parseGateway($jsonBody);
		if($gateway ===  false){
			throw new InvalidGatewayException;
		}

		$result = $gateway->notify($jsonBody);

		$sort = $param['sort'] ??'asc';

		if($result){
			//对轨迹进行按照时间排序
			$result['list'] = $this->sortNode($result['list'],$sort);

			//状态处理
			$state = $this->getStatus($result['list'],$sort);

			return [
				'ret'	=>	true,
				'list'	=>	$result['list'],
				'status'=>	$state['status'],
				'recent'=>	$state['recent']
			];
		}

		return ['ret' => false,'msg' =>	$result['msg']];
	}
}