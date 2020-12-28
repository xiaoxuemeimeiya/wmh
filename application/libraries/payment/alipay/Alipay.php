<?php

/**
 * @class Alipay
 * @brief 支付宝及时到账
 */
class Alipay
{
    //支付插件名称
    public $name = '支付宝及时到账';
    private $SubmitUrl = 'https://mapi.alipay.com/gateway.do?_input_charset=utf-8';//提交地址

    public function __construct()
    {
        $CI = &get_instance();
        $CI->load->model('loop_model');
        $payment_data         = $CI->loop_model->get_id('payment', 6);
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
            $return = array();
            $return = array(
                "service"        => 'create_direct_pay_by_user',
                "partner"        => $this->payment_config['pid'][1],//pid
                "seller_email"   => $this->payment_config['mch_id'][1],//支付账号email或手机
                "payment_type"   => 1,
                "notify_url"     => $order_info['server_callback'],
                "return_url"     => $order_info['callback'],
                "out_trade_no"   => $order_info['order_no'],//订单号
                "subject"        => $order_info['order_body'],//支付简介
                "total_fee"      => format_price($order_info['order_price']),//支付金额
                "_input_charset" => 'utf-8'
            );

            //除去待签名参数数组中的空值和签名参数
            $para_filter = $this->array_filter($return);

            //对待签名参数数组排序
            $para_sort = $this->array_sort($para_filter);

            //生成签名结果
            $mysign = $this->build_sign($para_sort);

            //签名结果与签名方式加入请求提交参数组中
            $return['sign'] = $mysign;
            $return['sign_type'] = 'MD5';

            $this->submit($return, $order_info);
        } else {
            error_msg('订单信息错误');
        }
    }

    /**
     * 支付回调处理
     */
    public function callback()
    {
        $CI         = &get_instance();
        $callbackData = $CI->input->get(NULL, true);

        if ($callbackData['is_success']=='T') {
            $res       = array(
                'status' => 'y'
            );
            return $res;
        } else {
            $message = '支付失败';
        }
        $res = array(
            'status' => 'n',
            'info'   => $message,
        );
        return $res;
    }

    /**
     * 服务端支付回调处理
     */
    public function server_callback()
    {
        $CI         = &get_instance();
        $callbackData = $CI->input->post(NULL, true);

        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->array_filter($callbackData);

        //对待签名参数数组排序
        $para_sort = $this->array_sort($para_filter);

        //生成签名结果
        $mysign = $this->build_sign($para_sort);

        if ($callbackData['sign'] == $mysign) {
            if($callbackData['trade_status'] == 'TRADE_FINISHED' || $callbackData['trade_status'] == 'TRADE_SUCCESS') {
                $out_trade_no = explode('_', $callbackData['out_trade_no']);
                $order_no     = $out_trade_no[0];
                $res          = array(
                    'status'         => 'y',
                    'order_no'       => $order_no,
                    'transaction_id' => $callbackData['trade_no'],
                );
                return $res;
            } else {
                $message = '交易状态错误';
            }
        } else {
            $message = '签名不正确';
        }
        $res = array(
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
        $prestr = $prestr . $this->payment_config['key'][1];
        //把最终的字符串签名，获得签名结果
        $mysgin = md5($prestr);
        return $mysgin;
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
        $url = $this->SubmitUrl;
        $html = <<<eof
				<html>
				<head>
					<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
					<title>支付宝支付</title>
					<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
				</head>
				<body>
				正在跳转支付宝支付,请稍等......
                    <form id="alipaysubmit" name="alipaysubmit" action="$url" method="get">
eof;
        foreach ($parameters as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }
        $html .= <<<eof
                    <input type='submit' value='确认' style='display:none;'>
                    </form>
                    <script>document.forms['alipaysubmit'].submit();</script>
                </body>
                </html>
eof;
        echo $html;
    }

}