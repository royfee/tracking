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
	 */
	public function track($number);
}