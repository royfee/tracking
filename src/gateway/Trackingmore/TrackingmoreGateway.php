<?php
 namespace royfee\tracking\gateway\trackingmore;

 use royfee\tracking\interfaces\TrackInterface;
 use royfee\tracking\common\BaseGateway;
 use royfee\tracking\gateway\trackingmore\more\Api;

 class TrackingmoreGateway extends BaseGateway implements TrackInterface{
	private $tmore = null;

	public function __construct($config = []){
		$this->tmore = new Api($config['apikey']);
	}

 	public function track(array $number,$sort = 'desc',$group = false){

		//调用查询
		$response = $this->call('get',[
			'tracking_numbers' => implode(',',$number)
		]);

		if(empty($response)){
			return ['ret'=>false,'msg'=>'51track 调用异常'];
		}

		//手动创建单号
		if($response['code'] === 204){
			$this->create($number);
		}
		
		$result = [];
		if($response['code'] === 200){
			foreach($response['data'] as $order){
				$trackList = [];

				//处理轨迹节点
				foreach($order['origin_info']['trackinfo'] as $k => $node){
					$trackList[] = [
						'desc'	=>	$node['tracking_detail'],
						'loca'	=>	$node['location'],
						'time'	=>	$node['checkpoint_date'],
					];
				}

				$trackList = $this->sortNode($trackList,$sort);

				if($group){
					$trackList = $this->nodeGroup($trackList);
				}

				$result[] = [
					'code'	=>	0,
					'number'=>	$order['tracking_number'],
					'list'	=>	$trackList,
					'latest'=>	array_merge(
						$this->status($order['delivery_status'],$order['substatus']),
						[
							'desc'	=>	$order['latest_event']??'',
							'time'	=>	str_replace('T',' ',substr($order['lastest_checkpoint_time'],0,19))
						]
					)					
				];	
			}

			return [
				'ret'	=>	true,
				'data'	=>	$result
			];	
		}
		
		return [
			'ret'	=>	false,
			'msg'	=>	$response['message']
		];
	}

	/**
		获取对应的运输商编码，根据单号前缀来区别
	*/
	private function getCarrier($track){
		switch(substr($track,0,2)){
			case 'EL':
				return 'hong-kong-post';
			case 'EK':
				return 'china-post';
			case '55':
				return 'sto';
			case '73':
				return 'zto';	
			case '77':
				return 'yunda';
			case '99':
			case '97':
			case 'BH':
			case 'BE':
			case '11':
				return 'china-post';
			case 'MY':
			case 'KK':
				return 'gdex';
			default:
				return '';
		}
		return '';	
	}

	/**
		trackignmore 创建单号
	*/
	private function create(array $trackArray){
		$createArray = array();

		$total = 0;
		foreach($trackArray as $track){
			$carrier = $this->getCarrier($track);

			$createArray[] = [
				'tracking_number' => $track,
				'courier_code'    => $carrier,
				//'lang'      => 'en'
			];
			$total++;
		}
		
		$response = $this->call('create',$createArray);
		
		if($response['code'] === 200){
			return [
				'ret'	=>	true,
				'msg'	=>	sprintf('total:%s , success:%s , error:%s',$total,count($response['data']['success']),count($response['data']['error'])),
			];
		}
		return ['ret'=>false,'msg'=>$response['data']['error']];
	}

	private function call($act,$data){
		return json_decode($this->tmore->$act($data),true);
	}

	private function status($status,$sub_status=''){
		$return = ['status'=>0,'sub_status'=>0];
		$track51 = [
			'notfound'              =>  10,
			'InfoReceived'          =>  20,
			'transit'             	=>  30,
			'expired'               =>  40,
			'pickup'        		=>  60,
			'undelivered'      		=>  70,
			'delivered'             =>  80,
			'exception'             =>  90,
			'pending'             	=>  20,
		];

		$track51_sub = [
			'notfound001'						=>	'2001',
			'notfound002'						=>	'1001',
			'transit001'						=>	'3005',
			'transit002'						=>	'3006',
			'transit003'						=>	'3007',
			'transit004'						=>	'3008',
			'transit005'						=>	'3009',
			'transit006'						=>	'3010',
			'transit007'						=>	'3011',
			'pickup001'							=>	'6002',
			'pickup002'							=>	'6003',
			'pickup003'							=>	'6004',
			'delivered001'						=>	'8002',
			'delivered002'						=>	'8003',
			'delivered003'						=>	'8004',
			'delivered004'						=>	'8005',
			'undelivered001'					=>	'7005',
			'undelivered002'					=>	'7006',
			'undelivered003'					=>	'7002',
			'undelivered004'					=>	'7001',
			'exception004'				=>	'9004',
			'exception005'				=>	'9001',
			'exception006'				=>	'9012',
			'exception007'				=>	'9006',
			'exception008'				=>	'9011',
			'exception009'				=>	'9007',
			'exception010'				=>	'9002',
			'exception011'				=>	'9002',
		];

		if(isset($track51[$status])){
			$return['status'] = $track51[$status];
		}
		if($sub_status && isset($track51_sub[$sub_status])){
			$return['sub_status'] = $track51_sub[$sub_status];
		}

		//状态描述
		$return['status_desc'] = \royfee\tracking\support\Status::getDesc($return['status'],$return['sub_status']);
		return $return;
	}
}