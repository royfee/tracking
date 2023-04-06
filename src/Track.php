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
	private $gateway = null;

	public function __construct($config = []){
		$this->config = $config;

		$this->driver = $this->config['default'];

		if(empty($this->driver)){
			throw new InvalidArgumentException("Driver is empty");
		}

		if(empty($this->config[$this->driver])){
			throw new InvalidArgumentException("Configuration [$this->driver] is empty");
		}

		$this->gateway = $this->createGateway($this->driver);
	}

	/**
		追踪物流轨迹信息
		@number  string|array 追踪单号
		@sort string 排序顺序
		@group 轨迹是否分组	
	*/
	public function tracking($number,$sort = 'desc',$group = false){
		//调用对应第三方的查询轨迹
		$trackArr = is_array($number)?$number:explode(',',$number);

		if(empty($trackArr)){
			return ['ret'=>false,'msg'=>'number empty'];
		}

		$result = $this->gateway->track($trackArr,$sort,$group);
		
		//file_put_contents('tracking.txt',var_export($result,true));
		return $result;
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

		$sort = $param['sort'] ??'desc';

		if($result){
			//对轨迹进行按照时间排序
			$result['list'] = $this->sortNode($result['list'],$sort);

			return $result;
		}
		return ['ret' => false,'msg' =>	$result['msg']];
	}

	/**
	 * 对轨迹进行排序
	 */
	public function sortNode($nodelist,$by = 'desc'){
		return $this->gateway->sortNode($nodelist,$by);
	}

	public function nodeGroup($nodelist){
		return $this->gateway->nodeGroup($nodelist);
	}
}