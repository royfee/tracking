<?php
namespace royfee\tracking\common;

use royfee\tracking\exception\InvalidGatewayException;

/**
	基类接口
*/
abstract class BaseTrack{

	/**
		格式化物流信息
		sort 排序状态 A 升序，D 倒序
	*/
    protected $driver;
    protected $config;

    protected function createGateway($gateway){
        $gateway = 'royfee\\tracking\\gateway\\' . ucfirst($gateway) . '\\' . ucfirst($gateway) . 'Gateway';
        return $this->build($gateway);
    }

	/**
	 * 解析轨迹通知所属gateway
	 * @return  class|false
	 */
	protected function parseGateway($body){
		if(isset($body['event']) && $body['event']=='TRACKING_UPDATED'){
			return $this->createGateway('track17');
		}
		return false;
	}

    protected function build($gateway){
		return new $gateway($this->config[$this->driver]);
    }
}