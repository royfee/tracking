<?php
namespace royfee\tracking\common;

/**
	网关基类
*/
abstract class BaseGateway{
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
	protected function in_string($string,array $find){
		foreach($find as $k => $val){
			if(strpos($string,$val)!==false){
				return true;
			}
		}
		return false;
	}

	/**
	 * 
	 */
	protected function nodeGroup($tracklist){
		$groupNode = [];
		foreach($tracklist as $k => $line){
			switch($this->nodeStatus($line['desc'])){
				case 1://已揽件
					$groupNode['已揽件'][] = $line;
					break;
				case 2://派送中
					$groupNode['派送中'][] = $line;
					break;
				case 3://自提点
					$groupNode['自提点'][] = $line;
					break;
				case 4://已签收
					$groupNode['已签收'][] = $line;
					break;
				default://运输中
					$groupNode['运输中'][] = $line;
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
}