<?php
/**
 * 马来西亚渠道的采集轨迹
 */
namespace royfee\tracking\collection;

use royfee\tracking\interfaces\TrackInterface;
use royfee\tracking\common\BaseGateway;

class MCollection extends BaseGateway { // implements TrackInterface
    private $url = 'http://47.52.146.142/podtrack/Details.aspx?ID=';

	public function track(String $number,String $no){
		$result = $this->fetch($number);

		if($result === false){
			return false;
		}

		//排序
		$list = $this->sortNode($result);

		return [
			'code'	=>	0,
			'number'=>	$number,
			'list'	=>	$list,
			'latest'=>  $this->latest($list[0])
		];
	}

	private function latest($curNode){
		$status = $this->nodeStatus($curNode['desc']);
		$return = [
			'desc'	=>	$curNode['desc'],
			'time'	=>	$curNode['time'],
			'status'=>	$status,
			'sub_status'	=>	0,
			'status_desc'	=>	\royfee\tracking\support\Status::getDesc($status,0)
		];

		return $return;
	}

	protected function nodeStatus($desc){
		if($this->in_string($desc,array('Arrival'))){
			return 100;
		}
		else if($this->in_string($desc,array('Processing'))){
			return 110;
		}
		else if($this->in_string($desc,array('Arrived'))){
			return 120;
		}
		else if($this->in_string($desc,array('Destination'))){
			return 130;
		}
		else if($this->in_string($desc,array('Scanned'))){
			return 140;
		}
		else if($this->in_string($desc,array('Shipment Out'))){
			return 150;
		}
		else if($this->in_string($desc,array('Shipment Delivered'))){
			return 160;
		}
		return 130;
	}

	private function fetch($number){
		$index = 0;
		do{
			$html = file_get_contents($this->url.$number);
			if($index++>2 || $html){
				break;
			}
		}while(true);

		if(!$html)return false;

		$dom = new \DOMDocument();
		$dom->loadHTML($html);
		$node = $dom->getElementById('GridView1');

		if(!$node){
			return false;
		}
		
		$rows = $node->getElementsByTagName('tr');
		
		$trackList = array();
		foreach($rows as $row){
			$cells = $row->getElementsByTagName('td');
			$arr = array();
			foreach($cells as $k => $cell){
				$val = $cell->nodeValue;
				if($val)$arr[] = trim($val);
			}

			if($arr){
				$trackList[] = array(
					'time'	=>	date('Y-m-d H:i:s',strtotime($arr[0].' '.$arr[1])),
					'loca'	=>	$arr[2],
					'desc'	=>	$arr[3],
				);
			}
		}
		return $trackList;
	}
}