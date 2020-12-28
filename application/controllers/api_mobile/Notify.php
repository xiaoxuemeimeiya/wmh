<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Notify extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        /**
         * 载入数据库类
         */
        $this->load->model('loop_model');
        $this->load->helpers('wechat_helper');
        $this->load->database();
    }

    public function index(){
        $postStr = file_get_contents("php://input");
        $orderData = isset($postStr)? $postStr : '';
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($orderData,'simpleXMLElement',LIBXML_NOCDATA)),true);
        lyLog(var_export($data,true) , "paynotify" , true);

        if($data['return_code']=="SUCCESS"){
            //$UpdataWhere['openid']              = $data['openid'];
            $UpdataWhere['order_no']      = $data['out_trade_no'];
            $UpdataWhere['status']      = 1;//生成订单
            //$checkRes = Db::table('order')->where($UpdataWhere)->find();//生成订单数据
            $checkRes = $this->loop_model->get_where('order',$UpdataWhere);
            if($checkRes){
                //$openid = $checkRes['openid'];
                $updateData['payment_id']           = 3;//微信支付
                $updateData['payment_status']       = 1;//已经支付
                $updateData['payment_no']           = $data['transaction_id'];
                $updateData['paytime']              = time();
                $updateData['status']               = 2;
                $this->db->trans_start();
                $res = $this->loop_model->update_where('order',$updateData,$UpdataWhere);
                if($res < 1 ){
                    $this->db->trans_rollback();
                }
                  //插入收款单
                $collection_data = array(
                    'order_id'   => $checkRes['id'],
                    'm_id'       => $checkRes['m_id'],
                    'amount'     => $checkRes['order_price'],
                    'addtime'    => time(),
                    'payment_id' => $checkRes['payment_id'],
                    'note'       => '',
                    'admin_user' =>  0
                );
                $res1 = $this->loop_model->insert('order_collection_doc',$collection_data);var_dump($res1);
                if($res1 > 0){
                    $this->db->trans_commit();
                }else{
                    $this->db->trans_rollback();
                }
                $temp["pay_money"] = $data["total_fee"]/100;
                $temp["openid"] = $data["openid"];
                $temp["prepay_id"] = $checkRes["prepay_id"];
                $temp["order_id"] = $data["transaction_id"];
                $temp["order_type"] = $checkRes['order_type'];
                $ee = self::sendmsg($temp); lyLog(var_export($ee,true) , "343paynotify" , true);
                $re = $this->echoCallBack(true);
            }
        }
    }

    public function set_phone($num,$message){
        header("Content-type: text/html; charset=utf-8");
        date_default_timezone_set('PRC'); //设置默认时区为北京时间
        $uid = 'GZJS006780';
        $passwd = 'hl@668';

        $msg = rawurlencode(mb_convert_encoding($message, "gb2312", "utf-8"));

        $gateway = "https://sdk2.028lk.com/sdk2/BatchSend2.aspx?CorpID={$uid}&Pwd={$passwd}&Mobile={$num}&Content={$msg}&Cell=&SendTime=";

        $result = file_get_contents($gateway);

        if(  $result > 0 )
        {
            return true;
        }
        return false;
    }


    /**
     * xml转数组
     * @param string $xml xml数据 *必传
     * @return array|bool|\mix|mixed|\stdClass|string
     */
    public static function xmlToArray($xml){
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimplexmlElement', LIBxml_NOCDATA)), true);
        return $array_data;
    }

    //回调封装
    protected function echoCallBack($status = false){
        if (!$status){
            $result = "<xml>
				<return_code><![CDATA[FAIL]]></return_code>
				<return_msg><![CDATA[未接收到post数据]]></return_msg>
				</xml>";
        }else{
            $result = "<xml>
				<return_code><![CDATA[SUCCESS]]></return_code>
				<return_msg><![CDATA[OK]]></return_msg>
				</xml>";
        }
        echo $result;
    }

    public static function arrayToxml($arr){
        $xml = "<xml>";
        foreach($arr as $key => $val){
            if(is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    public function sendmsg($tempArr)
    {
        $accesstoken = wechattoken(config('miniApp_id'),config('miniApp_Src'));
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$accesstoken;
        $template_id = config("temp_miniapp_pay_seccess");
        $paytime = date("Y-m-d H:i:s");
        if($tempArr['order_type'] == 1){
            $type = '购买套餐';
        }else if($tempArr['order_type'] == 2){
            $type = '链英汇课程学习';
        }else if($tempArr['order_type'] == 3){
            $type = '线下学院';
        }else if($tempArr['order_type'] == 4){
            $type = '语音电话';
        }else if($tempArr['order_type'] == 5){
            $type = '线上问答';
        }
        $data = '{
		  "touser":"'.$tempArr["openid"] .'",  
		  "template_id":"'.$template_id.'",
		  "form_id":"'.$tempArr["prepay_id"].'",   
		  "data": {
			  "keyword1": {
				  "value":"'.$tempArr["pay_money"].' 元"
			  }, 
			  "keyword2": {
				  "value":"'.$paytime.'" 
			  }, 
			  "keyword3": {
				  "value":"'.$type.'"
			  } , 
			  "keyword4": {
				  "value":"'.$tempArr["order_id"].'" 
			  }
		  }
		}';
        $res = jzPost($url,$data);
        return $res;
        //return "";
    }

    public function sendmsganwser($tempArr)
    {
        $accesstoken = wechattoken(config('miniApp_id'),config('miniApp_Src'));
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$accesstoken;
        $template_id = config("temp_miniapp_anwser_cat_success");
        $paytime = date("Y-m-d H:i:s");
        $data = '{
		  "touser":"'.$tempArr["openid"] .'",  
		  "template_id":"'.$template_id.'",
		  "form_id":"'.$tempArr["form_id"].'",   
		  "page":"pages/question-expert-detail/question-expert-detail?type=expert&id='.$tempArr["id"].'",
		  "data": {
			  "keyword1": {
				  "value":"问答论坛"
			  }, 
			  "keyword2": {
				  "value":"'.date('Y-m-d H:i:s',time()).'" 
			  }, 
			  "keyword3": {
				  "value":"用户希望得到您的帮助"
			  } , 
			  "keyword4": {
				  "value":"'.$tempArr["content"].'" 
			  }
		  }
		}';
        $res = jzPost($url,$data);
        return $res;
        //return "";
    }

}