<?php
 namespace royfee\tracking\gateway\trackingmore;

 use royfee\tracking\interfaces\TrackInterface;

 class TrackingmoreGateway implements TrackInterface{
	private $config;
	private $tmore = null;

	public function __construct($config = []){
		$this->config = $config;
		$this->tmore = new TMore($this->config);
	}

 	public function track($number){
		 //获取运输商
		$carrier = $this->getCarrier($number);

		if(empty($carrier)){
			return ['ret' => true,'msg'	=>	'Transporter cannot be empty'];
		}

		$result  = $this->tmore->getRealtimeTrackingResults($carrier,$number,array('lang'=>'cn'));

		if($result['meta']['type']!='Success'){
			if($result['meta']['code']==4031){
				//没有则自动订阅
				$this->subscribe([$track]);
				return ['ret'=>false,'msg'=>sprintf('%s No subscription',$number)];
			}
			return ['ret'=>false,'msg'=>$result['meta']['message']];
		}

		if(isset($result['data']['items'])){
			$item = $result['data']['items'][0];
		}else{
			$item = $result['data'];
		}

		$trackList = array();
		if(isset($item['origin_info']) && $item['origin_info']){
			$tList = $item['origin_info']['trackinfo'];
			if($tList){
				$len = count($tList)-1;
				for($i=$len;$i>=0;$i--){
					$arr = $tList[$i];
					$trackList[] = array(
						'desc'	=>	$arr['StatusDescription'],
						'loca'	=>	$arr['Details'],
						'time'	=>	$arr['Date'],
					);
				}
			}
		}

		if(isset($item['destination_info']) && $item['destination_info']){
			$tList = $item['destination_info']['trackinfo'];
			if($tList){
				$len = count($tList)-1;
				for($i=$len;$i>=0;$i--){
					$arr = $tList[$i];
					$trackList[] = array(
						'desc'	=>	$arr['StatusDescription'],
						'loca'	=>	$arr['Details'],
						'time'		=>	$arr['Date'],
					);
				}
			}
		}
		
		return [
			'ret'	=>	true,
			'list'	=>	$trackList
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
			case '77':
				return 'yunda';
			case '99':
			case '97':
			case 'BH':
			case 'BE':
			case '11':
				return 'china-post';
			default:
				return '';
		}
		return '';	
	}

	/**
		trackignmore 订阅
	*/
	private function subscribe(array $trackArray){
		$createArray = array();

		$total = 0;
		foreach($trackArray as $track){
			$carrier = $this->getCarrier($track);
			$createArray[] = array(
				'tracking_number' => $track,
				'carrier_code'    => $carrier,
				'title'          => '',
				'logistics_channel' => '',
				'customer_name'   => '',
				'customer_email'  => '',
				'order_id'      => '',
				'customer_phone'      => '',
				'order_create_time'      => '',
				'destination_code'      => '',
				'tracking_ship_date'      => time(),
				'tracking_postal_code'      => '',
				'lang'      => 'en'					
			);
			$total++;
		}

		$result = $this->tmore->createMultipleTracking($createArray);
		if($result['meta']['code'] == 200 || $result['meta']['code']==201){
			return [
				'ret'	=>	true,
				'msg'	=>	sprintf('submitted:%s , added:%s',$result['data']['submitted'],$result['data']['added']),
			];
		}

		return ['ret'=>false,'msg'=>$result['meta']['message']];
	}
 }