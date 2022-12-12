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
}