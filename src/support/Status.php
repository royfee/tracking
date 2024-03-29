<?php
namespace royfee\tracking\support;

/**物流状态 */
class Status{
    static $state = [
        10  =>  '查询不到',//查询不到，进行查询操作但没有得到结果，原因请参看子状态。
        20  =>  '收到信息',//收到信息，运输商收到下单信息，等待上门取件。
        30  =>  '运输途中',//运输途中，包裹正在运输途中，具体情况请参看子状态。
        40  =>  '运输过久',//运输过久，包裹已经运输了很长时间而仍未投递成功。
        50  =>  '到达待取',//到达待取，包裹已经到达目的地的投递点，需要收件人自取。
        60  =>  '派送途中',//派送途中，包裹正在投递过程中。
        70  =>  '投递失败',//投递失败，包裹尝试派送但未能成功交付，原因请参看子状态。原因可能是：派送时收件人不在家、投递延误重新安排派送、收件人要求延迟派送、地址不详无法派送、因偏远地区不提供派送服务等。
        80  =>  '成功签收',//成功签收，包裹已妥投。
        90  =>  '可能异常',//可能异常，包裹可能被退回，原因请参看子状态。原因可能是：收件人地址错误或不详、收件人拒收、包裹无人认领超过保留期等。包裹可能被海关扣留，常见扣关原因是：包含敏感违禁、限制进出口的物品、未交税款等。包裹可能在运输途中遭受损坏、丢失、延误投递等特殊情况。

        //境外轨迹
        100 =>  '航班到达',
        110 =>  '海关处理',
        120 =>  '到达HUB',
        130 =>  '运输目的地',
        140 =>  '已扫描入站',
        150 =>  '派送中',
        160 =>  '已妥投',
    ];

    static $state_sub = [
        10 => [
            1001    =>  '运输商没有返回信息',//运输商没有返回信息NotFound_Othe
            1002    =>  '物流单号无效',//物流单号无效，无法进行查询。NotFound_InvalidCode
        ],
        20 => [
            2001    =>  '收到信息',//收到信息InfoReceived
        ],
        30  => [
            3001    =>  '已揽收',//InTransit_PickedUp	已揽收。
            3002    =>  '其它情况',//InTransit_Other	其它情况。
            3003    =>  '已离港',//InTransit_Departure	已离港
            3004    =>  '已到港',//InTransit_Arrival	已到港
            3005    =>  '包裹正在运输途中',
            3006    =>  '包裹到达分拣中心',
            3007    =>  '包裹到达目的网点',
            3008    =>  '包裹达到目的国家',
            3009    =>  '包裹清关完成',
            3010    =>  '包裹已经封装好，即将送出',
            3011    =>  '包裹已经交给航空公司',
        ],
        40 => [
            4001    =>  '其它原因'
        ],
        50 => [
            5001    =>  '其它原因'
        ],
        60 => [
            6001    =>  '其它原因',
            6002    =>  '包裹正在派送中或即将派送',
            6003    =>  '包裹到达代收点等待自取',
            6004    =>  '派送中，物流商已经与收件人联系过至少一次',
        ],
        70 => [
            7001    =>  '其它原因',//DeliveryFailure_Other	其它原因。
            7002    =>  '找不到收件人',//DeliveryFailure_NoBody	找不到收件人。
            7003    =>  '安全原因',//DeliveryFailure_Security	安全原因。
            7004    =>  '拒收包裹',//DeliveryFailure_Rejected	拒收包裹。
            7005    =>  '收件地址错误',//DeliveryFailure_InvalidAddress	收件地址错误。
            7006    =>  '派送时无人在家',//DeliveryFailure_InvalidAddress	收件地址错误。
        ],
        80 => [
            8001    =>  '其它原因',
            8002    =>  '包裹投递成功',
            8003    =>  '客户自提成功',
            8004    =>  '包裹由客户签字签收',
            8005    =>  '包裹放至快递柜，或由物业、门卫代收',
        ],
        90 => [
            9001    =>  '其它原因',//Exception_Other	其它原因。
            9002    =>  '退件处理中',//Exception_Returning	退件处理中。
            9003    =>  '退件已签收',//Exception_Returned	退件已签收
            9004    =>  '没人签收',//Exception_NoBody	没人签收
            9005    =>  '安全原因',//Exception_Security	安全原因。
            9006    =>  '货品损坏了',//Exception_Damage	货品损坏了。
            9007    =>  '被拒收了',//Exception_Rejected	被拒收了。
            9008    =>  '因各种延迟情况导致的异常',//Exception_Delayed	因各种延迟情况导致的异常。
            9009    =>  '包裹丢失了',//Exception_Lost	包裹丢失了。
            9010    =>  '包裹被销毁了',//Exception_Destroyed	包裹被销毁了。
            9011    =>  '物流订单被取消了',//Exception_Cancel	物流订单被取消了
            9012    =>  '包裹被海关扣留',//Exception_Cancel	物流订单被取消了
        ]
    ];

    public static function getDesc($status,$sub_status){
        $arr = [];
        if(isset(self::$state[$status])){
            $arr[] = self::$state[$status];

            if(isset(self::$state_sub[$status][$sub_status])){
                $arr[] = self::$state_sub[$status][$sub_status];
            }
        }
        return $arr?implode(' | ',$arr):'';
    }

    public static function getStatus(){
        return self::$state;
    }
}