<?php
/**
 * @class Wechat
 * @brief 微信扫码支付
 */
class Wechat_native
{
    //支付插件名称
    public $name       = '微信扫码支付';
    private $SubmitUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';//提交地址

    public function __construct()
    {
        $CI = &get_instance();
        $CI->load->model('loop_model');
        $payment_data = $CI->loop_model->get_id('payment', 4);
        $this->payment_config = json_decode($payment_data['config'], true);
    }

    /**
     *支付成功回调
     */
    public function success()
    {
        echo 'success';exit;
    }

    /**
     *支付失败回调
     */
    public function error()
    {
        echo 'error';exit;
    }

    /**
     * @发起jsapi微信支付
     */
    public function do_pay($order_info)
    {
        if(!empty($order_info))
        {
            //微信支付统一下单
            $sendData = $this->get_send_data($order_info);

            //微信支付统一下单结果解析
            if($sendData['return_code']!='SUCCESS')
            {
                msg($sendData['return_msg'], site_url($order_info['client'].'/member/'));
            }
            if($sendData['return_code']=='SUCCESS' && $sendData['result_code']=='SUCCESS')
            {
                $this->submit($sendData, $order_info);
            }
            else
            {
                //微信支付统一下单失败
                if($sendData['return_code']!='SUCCESS')
                {
                    msg($sendData['return_msg'], site_url($order_info['client'].'/member/'));
                }
                elseif($sendData['result_code']!='SUCCESS')
                {
                    msg($sendData['err_code_des'], site_url($order_info['client'].'/member/'));
                }
            }
        }
        else
        {
            error_msg('订单信息错误');
        }
    }

    /**
     * 微信支付统一下单,获得支付参数
     */
    public function get_send_data($order_info)
    {
        $CI = &get_instance();
        $return = array();

        //基本参数
        $return['appid']        = $this->payment_config['appid'][1];
        $return['mch_id']       = $this->payment_config['mch_id'][1];
        $return['nonce_str']    = get_rand_num('str', 8);
        $return['body']         = $order_info['order_body'];
        $return['out_trade_no'] = $order_info['order_no'] . '_' . rand(10, 99);
        $return['total_fee']    = $order_info['order_price'];//金额
        $result['detail']       = $order_info['detail'];//订单详情
        $return['spbill_create_ip'] = $CI->input->ip_address();
        $return['notify_url']   = $order_info['server_callback'];
        $return['trade_type']   = 'NATIVE';

        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->array_filter($return);

        //对待签名参数数组排序
        $para_sort = $this->array_sort($para_filter);

        //生成签名结果
        $mysign = $this->build_sign($para_sort);

        //签名结果与签名方式加入请求提交参数组中
        $return['sign'] = $mysign;

        $xmlData = $this->conver_xml($return);
        $result = $this->curl_submit($xmlData);

        return $this->conver_array($result);
    }

    /**
     * 支付回调处理
     */
    public function server_callback()
    {
        $xml_data = $GLOBALS["HTTP_RAW_POST_DATA"];
        $callbackData = $this->conver_array($xml_data);
        if($callbackData['return_code']=='SUCCESS'){
            if($callbackData['result_code']=='SUCCESS'){
                //除去待签名参数数组中的空值和签名参数
                $para_filter = $this->array_filter($callbackData);

                //对待签名参数数组排序
                $para_sort = $this->array_sort($para_filter);

                //生成签名结果
                $mysign = $this->build_sign($para_sort);

                //验证签名
                if($mysign == $callbackData['sign'])
                {
                    $out_trade_no = explode('_',$callbackData['out_trade_no']);
                    $order_no = $out_trade_no[0];
                    $res = array(
                        'status'         => 'y',
                        'order_no'       => $order_no,
                        'transaction_id' => $callbackData['transaction_id'],
                    );
                    return $res;
                }
                else
                {
                    $message = '签名不匹配';
                }
            }
            else{
                $message = '错误'.$callbackData['err_code_des'];
            }
        }else{
            $message = '错误'.$callbackData['return_msg'];
        }
        $message = $message ? $message : $callbackData['message'];
        $res = array(
            'status' => 'n',
            'info'   => $message,
        );
        return $res;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    private function array_filter($para)
    {
        $para_filter = array();
        foreach($para as $key => $val)
        {
            if($key == "sign" || $key == "sign_type" || $val == "")
            {
                continue;
            }
            else
            {
                $para_filter[$key] = $para[$key];
            }
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
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
     * return 签名结果字符串
     */
    private function build_sign($sort_para)
    {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->create_linkstring($sort_para);
        //把拼接后的字符串再与安全校验码直接连接起来
        $prestr = $prestr.'&key='.$this->payment_config['key'][1];
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
        foreach($arrayData as $key => $val)
        {
            $xml .= '<'.$key.'><![CDATA['.$val.']]></'.$key.'>';
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
        $result = array();
        $xmlHandle = xml_parser_create();
        xml_parse_into_struct($xmlHandle, $xmlData, $resultArray);

        foreach($resultArray as $key => $val)
        {
            if($val['tag'] != 'XML')
            {
                $result[$val['tag']] = $val['value'];
            }
        }
        return array_change_key_case($result);
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function create_linkstring($para)
    {
        $arg  = "";
        foreach($para as $key => $val)
        {
            $arg.=$key."=".$val."&";
        }

        //去掉最后一个&字符
        $arg = trim($arg,'&');

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc())
        {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 微信调起jsapi
     * @param $para 需要拼接的数组
     */
    private function submit($parameters,$order_info)
    {
        $pay_complete_url = site_url($order_info['client'].'/member/order');
        assign('pay_complete_url', $pay_complete_url);
        $order_no = $order_info['order_no'];
        assign('order_no', $order_no);
        $code_url = $parameters['code_url'];
        assign('code_url', $code_url);
        //display('pc/pay/index.html');//页面支付的

        $web_title = config_item('website_title');
        $code_url = $parameters['code_url'];
        $html = <<<eof
				<html>
				<head>
					<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
					<title>$web_title</title>
					<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
					<script language="JavaScript" src="/public/js/jquery.js"></script>
					<script type="text/javascript" src="/public/js/jquery.qrcode-0.12.0.min.js"></script>
					<script language="JavaScript">
                        $(function(){
                            setTimeout("order_pay_status()", 2000);
                            var options = {
                                render: "image",
                                ecLevel: 'H',//识别度
                                fill: '#000',//二维码颜色
                                background: '#ffffff',//背景颜色
                                quiet: 2,//边距
                                width: 200,//宽度
                                height: 200,
                                text: "$code_url",//二维码内容
                                //中间logo start
                                /*mode: 4,
                                mSize: 11 * 0.01,
                                mPosX: 50 * 0.01,
                                mPosY: 50 * 0.01,
                                image:$('#img-buffer')[0],//logo图片*/
                                //中间logo end
                                label: 'jQuery.qrcode',
                                fontname: 'Ubuntu',
                                fontcolor: '#ff9818',
                            };
                            $('#container').empty().qrcode(options);
                        })
                        //查询订单支付状态
                        function order_pay_status(){
                            $.ajax({
                                type:"POST",
                                url: '/api/web/order/order_pay_status',
                                data: 'order_no=$order_no',
                                dataType:"json",
                                success: function(data){
                                    if (data.status=='y') {
                                        window.location.href='$pay_complete_url';
                                    } else {
                                        setTimeout("order_pay_status()", 2000);
                                    }
                                }
                            });
                        }
                    </script>
				</head>
				<body>
                    <div id="container"></div>
				</body>
				</html>
eof;
        echo $html;
    }

}