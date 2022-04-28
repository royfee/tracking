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
	public function __construct($config = []){
		if($config){
			$this->config = $config;
		}
	}

	/**
	 * 配置文件
	 */
	public function config($config){
		$this->config = $config;
		return $this;
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
		$this->driver = array_keys($this->config)[0];

		if(empty($this->config[$this->driver])){
			throw new InvalidArgumentException("Configuration [$this->driver] is empty");
		}

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
		$sort = $param['sort'] ??'asc';
		$trackList = $this->sortNode($trackList,$sort);

		//状态处理
		$state = $this->getStatus($trackList,$sort);

		//是否对轨迹进行分组
		if($param['isgroup']??false){
			$trackList = $this->nodeGroup($return['list']);
		}
	
		if($trackList){
			return [
				'ret'	=>	true,
				'list'	=>	$trackList,
				'status'=>	$state['status'],
				'recent'=>	$state['recent']
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
}