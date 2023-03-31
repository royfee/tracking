<?php

namespace royfee\tracking\interfaces;

/**
	通用接口
*/
interface TrackInterface{
	/**
	 * 返回的格式
	 * [
	 * 	 ret   =>	true,
		 latest =>	[],
		 list	=>	
	 * ]
	 * group 是否分组
	 */
	public function track(array $number,$sort = 'desc',$group = false);
}