<?php
 namespace royfee\tracking\gateway\track17;

 use royfee\tracking\interfaces\TrackInterface;

 class Track17Gateway implements TrackInterface{
	private $host = 'https://api.17track.net/track/v1/';
	private $config;

	public function __construct($config = []){
		$this->config = $config;		
	}

 	public function track($number){
		//$this->changecarrier($number);exit;
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

	public function notify($message){
		if(!$this->verify($message)){
			throw new \Exception('Signature error');
		}

		$list = [];
		foreach($message['data']['track_info']['tracking']['providers'][0]['events'] as $k => $line){
			$list[] = [
				'desc'	=>	$line['description'],
				'loca'	=>	$line['location'],
				'time'	=>	date('Y-m-d H:i:s',strtotime($line['time_utc'])),
			];
		}

		return ['ret'=>true,'list'=>$list];
	}

	private function verify($message){
		/**暂不验证 */
		return true;
	}

	private function subscribe($number){
		$param = 	is_array($number) ? $number : [
						'number' 		=> $number,
						'auto_detection'=> true,
						'carrier'		=> ''//默认不设置，让17自己去侦测运输商
					];

		//订阅
		$result = $this->post([$param],'register');

		if($result['data']['accepted']){
			return [
				'ret'	=>	true,
				'msg'	=>	'Success'
			];
		}

		if($result['data']['rejected']){
			return [
				'ret'	=>	false,
				'msg'	=>	$result['data']['rejected'][0]['error']['message']
			];			
		}
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