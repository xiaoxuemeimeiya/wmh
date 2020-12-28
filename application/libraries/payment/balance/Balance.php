<?php

/**
 * @class Balance
 * @brief 余额支付
 */
class Balance
{
    //支付插件名称
    public $name = '余额支付';

    public function __construct()
    {
        $CI = &get_instance();
        $CI->load->model('loop_model');
        $payment_data         = $CI->loop_model->get_id('payment', 2);
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
     *支付回调
     */
    public function callback()
    {
        $CI         = &get_instance();
        $order_info = $CI->input->post(NULL, true);
        //除去待签名参数数组中的空值和签名参数
        $pay_filter = $this->array_filter($order_info);

        //对待签名参数数组排序
        $pay_sort = $this->array_sort($pay_filter);

        //生成签名结果
        $mysign = $this->build_sign($pay_sort);
        //验证签名
        if ($mysign == $order_info['sign']) {
            //充值方式
            if (stripos($order_info['order_no'], 'on') !== false) {
                $message = '充值不能使用余额支付';
            } else {
                //获取订单号
                $order_no = cache('get', $order_info['order_no']);//取的订单缓存
                if (!empty($order_no)) {
                    $CI->load->model('member/user_account_log_model');
                    $status = 0;
                    foreach ($order_no as $key) {
                        //查询订单状态
                        $order_data = $CI->loop_model->get_where('order', array('order_no' => $key));
                        if ($order_data['payment_id'] == 2 && $order_data['payment_status'] == 0) {
                            //开始扣除余额
                            $data   = array(
                                'm_id'   => $order_data['m_id'],
                                'amount' => $order_data['order_price'],
                                'event'  => 3,
                                'note'   => '支付订单' . $order_data['order_no'],
                            );
                            $log_id = $CI->user_account_log_model->insert($data);
                            if ($log_id['status'] == 'y') {
                                //修改订单状态
                                $CI->load->model('order/order_model');
                                $order_res = $CI->order_model->update_pay_status($order_data['order_no']);
                                $status++;
                            } else {
                                if ($log_id['code'] == 'm_b_500') {
                                    msg($log_id['info'], site_url($order_info['client'] . '/member/balance/online_recharge'));
                                } else {
                                    $message = $log_id['info'];
                                }
                            }
                        } else {
                            $message = '订单支付方式错误';
                        }
                    }
                    if ($status>0) {
                        $res       = array(
                            'status' => 'y'
                        );
                        return $res;
                    } else {
                        $message = '支付失败';
                    }
                } else {
                    $message = '订单信息错误';
                }
            }
        } else {
            $message = '签名错误';
        }
        $res = array(
            'status' => 'n',
            'info'   => $message,
        );
        return $res;
    }

    /**
     * @发起支付
     */
    public function do_pay($order_info)
    {
        if (!empty($order_info)) {
            //开始支付订单
            $CI           = &get_instance();
            $callback_url = $order_info['callback'];
            unset($order_info['callback']);
            unset($order_info['server_callback']);
            $order_info['time']     = time();
            $order_info['rand_str'] = get_rand_num('str', 10);

            //除去待签名参数数组中的空值和签名参数
            $pay_filter = $this->array_filter($order_info);

            //对待签名参数数组排序
            $pay_sort = $this->array_sort($pay_filter);

            //生成签名结果
            $mysign = $this->build_sign($pay_sort);

            //签名结果与签名方式加入请求提交参数组中
            $order_info['sign'] = $mysign;

            //调用jsapi支付接口
            $this->submit($order_info, $callback_url);
        }
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
            if ($key == "sign" || $val == "") {
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
     * 支付完成调起
     */
    private function submit($order_info, $callback_url)
    {
        $pay_complete_url = site_url($order_info['client'] . '/member/order/');//取消后跳转地址

        $html = <<<eof
				<html>
				<head>
					<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
					<title>余额支付</title>
					<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
					<script type="text/javascript" src="/public/js/jquery.js"></script>
					<script type="text/javascript" src="/public/js/layer/layer.js"></script>
					<script type="text/javascript">
					    $(function(){
                            layer.confirm('确认使用余额支付？', {
                                btn: ['确认','取消'] //按钮
                            }, function(){
                                $('#pay_form').submit();
                            }, function(){
                                window.location.href='$pay_complete_url';
                            });
					    })
					</script>
				</head>
				<body>
                    <form id="pay_form" name="pay_form" action="$callback_url" method="post">
eof;
        foreach ($order_info as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }
        $html .= <<<eof
                    </form>
				</body>
				</html>
eof;
        echo $html;
    }

}