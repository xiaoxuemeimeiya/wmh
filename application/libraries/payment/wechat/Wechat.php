<?php

/**
 * @class Wechat
 * @brief 微信公众号支付
 */
class Wechat
{
    //支付插件名称
    public $name = '微信公众号支付';
    private $SubmitUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';//提交地址

    public function __construct()
    {
        $CI = &get_instance();
        $CI->load->model('loop_model');
        $payment_data         = $CI->loop_model->get_id('payment', 3);
        $this->payment_config = json_decode($payment_data['config'], true);
    }

    /**
     *支付成功回调
     */
    public function success()
    {
        echo 'success';
        exit;
    }

    /**
     *支付失败回调
     */
    public function error()
    {
        echo 'error';
        exit;
    }

    /**
     * @发起jsapi微信支付
     */
    public function do_pay($order_info)
    {
        if (!empty($order_info)) {
            //微信支付统一下单
            $sendData = $this->get_send_data($order_info);

            //微信支付统一下单结果解析
            if ($sendData['return_code'] != 'SUCCESS') {
                msg($sendData['return_msg'], site_url($order_info['client'] . '/member/'));
            }
            if ($sendData['return_code'] == 'SUCCESS' && $sendData['result_code'] == 'SUCCESS') {
                //微信支付jsapi参数
                $timeStamp             = time();
                $jsApiObj["appId"]     = $this->payment_config['appid'][1];
                $jsApiObj["timeStamp"] = "$timeStamp";
                $jsApiObj["nonceStr"]  = get_rand_num('str', 8);
                $jsApiObj["package"]   = "prepay_id=" . $sendData['prepay_id'];
                $jsApiObj["signType"]  = "MD5";
                //除去待签名参数数组中的空值和签名参数
                $psapi_filter = $this->array_filter($jsApiObj);

                //对待签名参数数组排序
                $jsapi_sort = $this->array_sort($psapi_filter);

                //生成签名结果
                $mysign = $this->build_sign($jsapi_sort);

                //签名结果与签名方式加入请求提交参数组中
                $jsApiObj['paySign'] = $mysign;
                $parameters          = json_encode($jsApiObj);

                //调用jsapi支付接口
                $this->submit($parameters, $order_info);
            } else {
                //微信支付统一下单失败
                if ($sendData['return_code'] != 'SUCCESS') {
                    msg($sendData['return_msg'], site_url($order_info['client'] . '/member/'));
                } elseif ($sendData['result_code'] != 'SUCCESS') {
                    msg($sendData['err_code_des'], site_url($order_info['client'] . '/member/'));
                }
            }
        } else {
            error_msg('订单信息错误');
        }
    }

    /**
     * 微信支付统一下单,获得支付参数
     */
    public function get_send_data($order_info)
    {
        $CI = &get_instance();
        $CI->load->helpers('wechat_helper');
        $openid = get_openid();
        if (empty($openid)) {
            msg('用户信息获取失败', site_url($order_info['client'] . '/member/'));
        }
        $return = array();

        //基本参数
        $return['appid']            = $this->payment_config['appid'][1];
        $return['mch_id']           = $this->payment_config['mch_id'][1];
        $return['nonce_str']        = get_rand_num('str', 8);
        $return['body']             = $order_info['order_body'];
        $return['out_trade_no']     = $order_info['order_no'] . '_' . rand(10, 99);
        $return['total_fee']        = $order_info['order_price'];
        $return['spbill_create_ip'] = $CI->input->ip_address();
        $return['notify_url']       = $order_info['server_callback'];
        $return['trade_type']       = 'JSAPI';
        $return['openid']           = $openid;

        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->array_filter($return);

        //对待签名参数数组排序
        $para_sort = $this->array_sort($para_filter);

        //生成签名结果
        $mysign = $this->build_sign($para_sort);

        //签名结果与签名方式加入请求提交参数组中
        $return['sign'] = $mysign;

        $xmlData = $this->conver_xml($return);
        $result  = $this->curl_submit($xmlData);

        return $this->conver_array($result);
    }

    /**
     * 支付回调处理
     */
    public function server_callback()
    {
        $xml_data     = $GLOBALS["HTTP_RAW_POST_DATA"];
        $callbackData = $this->conver_array($xml_data);
        if ($callbackData['return_code'] == 'SUCCESS') {
            if ($callbackData['result_code'] == 'SUCCESS') {
                //除去待签名参数数组中的空值和签名参数
                $para_filter = $this->array_filter($callbackData);

                //对待签名参数数组排序
                $para_sort = $this->array_sort($para_filter);

                //生成签名结果
                $mysign = $this->build_sign($para_sort);

                //验证签名
                if ($mysign == $callbackData['sign']) {
                    $out_trade_no = explode('_', $callbackData['out_trade_no']);
                    $order_no     = $out_trade_no[0];
                    $res          = array(
                        'status'         => 'y',
                        'order_no'       => $order_no,
                        'transaction_id' => $callbackData['transaction_id'],
                    );
                    return $res;
                } else {
                    $message = '签名不匹配';
                }
            } else {
                $message = '错误' . $callbackData['err_code_des'];
            }
        } else {
            $message = '错误' . $callbackData['return_msg'];
        }
        $message = $message ? $message : $callbackData['message'];
        $res     = array(
            'status' => 'n',
            'info'   => $message,
        );
        return $res;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     *              return 去掉空值与签名参数后的新签名参数组
     */
    private function array_filter($para)
    {
        $para_filter = array();
        foreach ($para as $key => $val) {
            if ($key == "sign" || $key == "sign_type" || $val == "") {
                continue;
            } else {
                $para_filter[$key] = $para[$key];
            }
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     *              return 排序后的数组
     */
    private function array_sort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 生成签名结果
     * @param $sort_para 要签名的数组
     *                   return 签名结果字符串
     */
    private function build_sign($sort_para)
    {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->create_linkstring($sort_para);
        //把拼接后的字符串再与安全校验码直接连接起来
        $prestr = $prestr . '&key=' . $this->payment_config['key'][1];
        //把最终的字符串签名，获得签名结果
        $mysgin = md5($prestr);
        return strtoupper($mysgin);
    }

    /**
     * @brief 从array到xml转换数据格式
     * @param array $arrayData
     * @return xml
     */
    private function conver_xml($arrayData)
    {
        $xml = '<xml>';
        foreach ($arrayData as $key => $val) {
            $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * @brief 提交数据
     * @param xml $xmlData 要发送的xml数据
     * @return xml 返回数据
     */
    private function curl_submit($xmlData)
    {
        //接收xml数据的文件
        $url = $this->SubmitUrl;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }


    /**
     * @brief 从xml到array转换数据格式
     * @param xml $xmlData
     * @return array
     */
    private function conver_array($xmlData)
    {
        $result    = array();
        $xmlHandle = xml_parser_create();
        xml_parse_into_struct($xmlHandle, $xmlData, $resultArray);

        foreach ($resultArray as $key => $val) {
            if ($val['tag'] != 'XML') {
                $result[$val['tag']] = $val['value'];
            }
        }
        return array_change_key_case($result);
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     *              return 拼接完成以后的字符串
     */
    private function create_linkstring($para)
    {
        $arg = "";
        foreach ($para as $key => $val) {
            $arg .= $key . "=" . $val . "&";
        }

        //去掉最后一个&字符
        $arg = trim($arg, '&');

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 微信调起jsapi
     * @param $para 需要拼接的数组
     */
    private function submit($parameters, $order_info)
    {
        $pay_complete_url = site_url($order_info['client'] . '/member/order');
        $html             = <<<eof
				<html>
				<head>
					<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
					<title>微信安全支付</title>
					<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
					<script type="text/javascript" src="/public/js/jquery.js"></script>
					<script type="text/javascript" src="/public/js/layer/layer.js"></script>
					<script type="text/javascript">
						//调用微信JS api 支付
						function jsApiCall()
						{
							WeixinJSBridge.invoke(
								'getBrandWCPayRequest',
								$parameters,
								function(res){
									WeixinJSBridge.log(res.err_msg);
									//alert(res.err_code+res.err_desc+res.err_msg);return false;
									if(res.err_msg=='get_brand_wcpay_request:ok'){
										window.location.href='$pay_complete_url';
									}else{
										layer.msg('支付失败');
										setTimeout(function(){
										    window.location.href='$pay_complete_url';
										},1000)
									}
								}
							);
						}

						function callpay()
						{
							if (typeof WeixinJSBridge == "undefined"){
								if( document.addEventListener ){
									document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
								}else if (document.attachEvent){
									document.attachEvent('WeixinJSBridgeReady', jsApiCall);
									document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
								}
							}else{
								jsApiCall();
							}
						}
						window.onload = function(){callpay();}
					</script>
				</head>
				<body>

				</body>
				</html>
eof;
        echo $html;
    }

}