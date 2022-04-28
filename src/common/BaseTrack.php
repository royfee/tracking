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

	protected function getStatus($tracklist,$sort = ''){
		if($sort == 'D'){
			$node = $tracklist[0];
		}else{
			$node = $tracklist[count($tracklist) - 1];
		}
		
		return [
			'status'	=>	$this->nodeStatus($node['desc']),
			'recent'	=>	sprintf('【%s】%s',$node['time'],$node['desc']),
		];
	}

	//相关字符是否在字符串中
	private function in_string($string,array $find){
		foreach($find as $k => $val){
			if(strpos($string,$val)!==false){
				return true;
			}
		}
		return false;
	}

	//分组格式化物流轨迹
	protected function nodeGroup($tracklist){
		$groupNode = [
			'1'	=>	[],//已揽件
			'0'	=>	[],//运输中
			'2'	=>	[],//派送中
			'3'	=>	[],//自提点
			'4'	=>	[],//已签收
		];

		foreach($tracklist as $k => $line){
			switch($this->nodeStatus($line['desc'])){
				case 1:
					$groupNode['1'][] = $line;
					break;
				case 2:
					$groupNode['2'][] = $line;
					break;
				case 3:
					$groupNode['3'][] = $line;
					break;
				case 4:
					$groupNode['4'][] = $line;
					break;
				default:
					$groupNode['0'][] = $line;
			}
		}

		return $groupNode;
	}

	//归类物流节点desc 物流节点描述
	protected function nodeStatus($desc){
		if($this->in_string($desc,array('已收件','已揽件','揽收人','已收寄','收寄人'))){
			return 1;
		}
		else if($this->in_string($desc,array('派件','派送','安排投递'))){
			return 2;
		}
		else if($this->in_string($desc,array('自提点','驿站','待取'))){
			return 3;
		}
		else if($this->in_string($desc,array('已签收','已投妥','投妥','代签','成功派递','签收人','代签收','已取走邮件','邮件已取走'))){
			return 4;
		}

		return 0;
	}

    protected function createGateway($gateway){
        $gateway = 'royfee\\tracking\\gateway\\' . ucfirst($gateway) . '\\' . ucfirst($gateway) . 'Gateway';
        return $this->build($gateway);
    }

    protected function build($gateway){
		return new $gateway($this->config[$this->driver]);
    }

	/**
	 * 轨迹节点排序
	 * nodelist 轨迹节点
	 */
	protected function sortNode($nodelist,$by = 'asc'){
		$sortFlag = array_map(function($arr){
			return $arr['time'];
		},$nodelist);
		array_multisort($sortFlag,$by=='asc'?SORT_ASC:SORT_DESC,$nodelist);
		return $nodelist;
	}
}