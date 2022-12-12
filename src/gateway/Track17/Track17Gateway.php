<?php
 namespace royfee\tracking\gateway\track17;

 use royfee\tracking\interfaces\TrackInterface;
 use royfee\tracking\common\BaseGateway;

 class Track17Gateway extends BaseGateway implements TrackInterface{
	private $host = 'https://api.17track.net/track/v2/';
	private $config;

	public function __construct($config = []){
		$this->config = $config;		
	}

	/**
	 * 支持多单号查询
	 */
	public function track(array $number,$sort = 'desc'){
		$postArray = array_map(function($val){
			return ['number' => $val];
		},$number);

		//追踪轨迹
		$result = $this->post($postArray);

		if(empty($result)){
			return ['ret'=>false,'msg'=>'17Track 调用异常'];
		}

		if(isset($result['data']['errors'])){
			return [
				'ret'	=> false,
				'msg'	=> $result['data']['errors'][0]['message'],
			];
		}

		//返回成功
		if($result['data']['rejected']){
			$rejected = $this->handleRejected($result['data']['rejected']);
		}

		if($result['data']['accepted']){
			$accepted = $this->handleAccepted($result['data']['accepted'],$sort);
		}
		//file_put_contents('17track.tracka.txt',var_export($result,true));

		return [
			'ret'	=>	true,
			'data'	=>	array_merge($rejected??[],$accepted??[])
		];
	}

	private function handleRejected(array $rejected){
		//处理错误的
		$result = [];

		$register = [];
		foreach($rejected as $line){
			//未订阅直接订阅
			if($line['error']['code'] == -18019902){
				$carrier = $this->getCarrier($line['number']);
				$register[] = [
					'number' 		=> $line['number'],
					'carrier'		=> $carrier,//如果只有一个运输商就传这个参数
					'final_carrier'	=> $carrier,//第二运输商要传这个参数
					'auto_detection'=> $carrier?false:true,
				];
			}else{
				$result[] = [
					'code'	=>	1,
					'number'=>	$line['number'],
					'msg'	=>	$line['error']['message'],
				];	
			}
		}

		if($register){
			$ret = $this->subscribe($register);
			//file_put_contents('17track.register.txt',var_export($ret,true));

			if($ret['ret']){
				foreach($ret['data'] as $line){
					$result[] = [
						'code'	=>	1,
						'number'=>	$line['number'],
						'msg'	=>	'AutoReg:'.$line['msg'],
					];
				}
			}
		}

		return $result;
	}

	private function handleAccepted(array $accepted,$sort){
		$result = [];
		foreach($accepted as $order){
			$trackList = [];

			$trackInfo = $order['track_info'];

			//获取轨迹列表
			$trackNode = $trackInfo['tracking']['providers'][0]['events'];

			//处理轨迹节点
			foreach($trackNode as $k => $node){
				$trackList[] = [
					'desc'	=>	$node['description'],
					'loca'	=>	$node['location'],
					'time'	=>	date('Y-m-d H:i:s',strtotime($node['time_utc'])),
				];
			}

			$latest = $this->status($trackInfo['latest_status']['status'],$trackInfo['latest_status']['sub_status']);
			
			$result[] = [
				'code'	=>	0,
				'number'=>	$order['number'],
				'list'	=>	$this->sortNode($trackList,$sort),
				'latest'=>	[
					'status'	=>	$latest['status'],
					'status_sub'	=>	$latest['sub_status'],
					'desc'	=>	'[ '.$trackInfo['latest_event']['location'].' ] '.$trackInfo['latest_event']['description'],
					'time'	=>	date('Y-m-d H:i:s',strtotime($trackInfo['latest_event']['time_utc'])),
				]
			];
		}
		return $result;
	}

	/*V1版本的
 	public function track($number){
		$result = $this->post([
			['number' => $number]
		]);

		if(isset($result['data']['errors'])){
			return [
				'ret'	=> false,
				'msg'	=> $result['data']['errors'][0]['message'],
			];
		}

		//返回成功
		if($result['data']['rejected']){
			//未注册
			if($result['data']['rejected'][0]['error']['code'] == -18019902){
				//直接注册
				$carrier = $this->getCarrier($number);
				$this->subscribe([
					'number' 		=> $number,
					'carrier'		=> $carrier,//如果只有一个运输商就传这个参数
					'final_carrier'	=> $carrier,//第二运输商要传这个参数
					'auto_detection'=> $carrier?false:true,
				]);
			}
			return [
				'ret'	=> false,
				'msg'	=> $result['data']['rejected'][0]['error']['message'],
			];
		}

		if($result['data']['accepted']){
			$trackList = [];

			$trackNode = $result['data']['accepted'][0]['track'];
			//处理Z9表示如果是联合运输商的话，头程部分会在Z9中存放
			if(isset($trackNode['z9']) && $trackNode['z9']){
				//处理轨迹节点
				foreach($trackNode['z9'] as $k => $node){
					$trackList[] = [
						'desc'	=>	$node['z'],
						'loca'	=>	$node['c'],
						'time'	=>	$node['a'],
					];
				}
			}

			//处理轨迹节点Z1第一运输商
			if($trackNode['z1']){
				foreach($trackNode['z1'] as $k => $node){
					$trackList[] = [
						'desc'	=>	$node['z'],
						'loca'	=>	$node['c'],
						'time'	=>	$node['a'],
					];
				}
			}

			//处理轨迹节点Z2第二运输商
			if($trackNode['z2']){
				foreach($trackNode['z2'] as $k => $node){
					$trackList[] = [
						'desc'	=>	$node['z'],
						'loca'	=>	$node['c'],
						'time'	=>	$node['a'],
					];
				}
			}

			return [
				'ret'	=>	true,
				'list'	=>	$trackList
			];	
		}

		return [
			'ret'	=>	false,
			'msg'	=>	'Track error'
		];
	}
	*/

	public function notify($message){
		if(!$this->verify($message)){
			throw new \Exception('Signature error');
		}

		$trackInfo = $message['data']['track_info'];
		$latest = $this->status($trackInfo['latest_status']['status'],$trackInfo['latest_status']['sub_status']);

		$list = [];
		foreach($trackInfo['tracking']['providers'][0]['events'] as $k => $line){
			$list[] = [
				'desc'	=>	$line['description'],
				'loca'	=>	$line['location'],
				'time'	=>	date('Y-m-d H:i:s',strtotime($line['time_utc'])),
			];
		}

		return [
			'ret'	=>	true,
			'list'	=>	$list,
			'number'=>	$message['data']['number'],
			'latest'=>	[
				'status'	=>	$latest['status'],
				'status_sub'	=>	$latest['sub_status'],
				'desc'	=>	$trackInfo['latest_event']['location'].'->'.$trackInfo['latest_event']['description'],
				'time'	=>	date('Y-m-d H:i:s',strtotime($trackInfo['latest_event']['time_utc'])),
			]
		];
	}

	private function status($status,$sub_status=''){
		$return = ['status'=>0,'sub_status'=>0];
		$track17 = [
			'NotFound'              =>  10,
			'InfoReceived'          =>  20,
			'InTransit'             =>  30,
			'Expired'               =>  40,
			'AvailableForPickup'    =>  50,
			'OutForDelivery'        =>  60,
			'DeliveryFailure'       =>  70,
			'Delivered'             =>  80,
			'Exception'             =>  90,
		];

		$track17_sub = [
			'NotFound_Other'					=>	'1001',//	运输商没有返回信息。
			'NotFound_InvalidCode'				=>	'1002',//	物流单号无效，无法进行查询。
			'InfoReceived'						=>	'2001',//	收到信息
			'InTransit_PickedUp'				=>	'3001',//	已揽收。
			'InTransit_Other'					=>	'3002',//	其它情况。
			'InTransit_Departure'				=>	'3003',//	已离港。
			'InTransit_Arrival'					=>	'3004',//	已到港。
			'Expired_Other'						=>	'4001',//	其它原因
			'AvailableForPickup_Other'			=>	'5001',//	其它原因
			'OutForDelivery_Other'				=>	'6001',//	其它原因
			'DeliveryFailure_Other'				=>	'7001',//	其它原因。
			'DeliveryFailure_NoBody'			=>	'7002',//	找不到收件人。
			'DeliveryFailure_Security'			=>	'7003',//	安全原因。
			'DeliveryFailure_Rejected'			=>	'7004',//	拒收包裹。
			'DeliveryFailure_InvalidAddress'	=>	'7005',//	收件地址错误。
			'Delivered_Other'					=>	'8001',//	其它原因
			'Exception_Other'					=>	'9001',//	其它原因。
			'Exception_Returning'				=>	'9002',//	退件处理中。
			'Exception_Returned'				=>	'9003',//	退件已签收。
			'Exception_NoBody'					=>	'9004',//	没人签收。
			'Exception_Security'				=>	'9005',//	安全原因。
			'Exception_Damage'					=>	'9006',//	货品损坏了。
			'Exception_Rejected'				=>	'9007',//	被拒收了。
			'Exception_Delayed'					=>	'9008',//	因各种延迟情况导致的异常。
			'Exception_Lost'					=>	'9009',//	包裹丢失了。
			'Exception_Destroyed'				=>	'9010',//	包裹被销毁了。
			'Exception_Cancel'					=>	'9011',//	物流订单被取消了。			
		];

		if(isset($track17[$status])){
			$return['status'] = $track17[$status];
		}
		if($sub_status && isset($track17_sub[$sub_status])){
			$return['sub_status'] = $track17_sub[$sub_status];
		}
		return $return;
	}

	private function verify($message){
		/**暂不验证 */
		return true;
	}

	private function subscribe($number){
		$param = 	is_array($number) ? $number : [[
						'number' 		=> $number,
						'auto_detection'=> true,
						'carrier'		=> ''//默认不设置，让17自己去侦测运输商
					]];

		//订阅
		$result = $this->post($param,'register');
		
		$return = [];
		//file_put_contents('subscribe.txt',var_export($param,true)."\r\n\r\n\r\n".var_export($result,true));
		
		if(isset($result['data']['errors'])){
			return [
				'ret'	=>	false,
				'msg'	=>	$result['data']['errors'][0]['message']
			];
		}

		foreach($result['data']['accepted'] as $line){
			$return[] = [
				'code'	=>	0,
				'number'=>	$line['number'],
				'carrier'=>	$line['carrier']
			];
		}
		
		foreach($result['data']['rejected'] as $line){
			$return[] = [
				'code'	=>	1,
				'number'=>	$line['number'],
				'msg'	=>	$line['error']['message']
			];
		}

		return [
			'ret'	=>	true,
			'data'	=>	$return
		];
	}

	//修改运输商
	private function changecarrier($number){
		$param = 	is_array($number) ? $number : [
			'number' 			=> $number,
			'final_carrier_new'	=> 3011,
		];

		//订阅
		$result = $this->post([$param],'changecarrier');
		var_dump($result);
	}

	private function post(array $param,$action = 'gettrackinfo'){
		$json = json_encode($param);

		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL 				=> $this->host.$action,
			CURLOPT_RETURNTRANSFER 	=> true,
			CURLOPT_ENCODING 			=> '',
			CURLOPT_MAXREDIRS 		=> 10,
			CURLOPT_TIMEOUT 			=> 0,
			CURLOPT_FOLLOWLOCATION 	=> true,
			CURLOPT_HTTP_VERSION		=> CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST 	=> 'POST',
			CURLOPT_POSTFIELDS 		=> $json,
			CURLOPT_HTTPHEADER 		=> [
				'17token:'.$this->config['token'],
				'Content-Type:application/json'
			]
		]);
		$response = curl_exec($curl);
		curl_close($curl);
		return json_decode($response,true);
	}

	/**
	 * 根据单号轨迹判断运输商
	 */
	private function getCarrier($number){
		//根据单号前两位来判断
		switch(substr($number,0,2)){
			case 'EK':
			case 'BH':
				$carrier = 3011;
				break;
			default:
				$carrier = '';
		}
		return $carrier;
	}
 }