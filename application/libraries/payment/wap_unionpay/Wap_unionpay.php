<?php

/**
 * @class Wap_unionpay
 * @brief 银联wap支付
 */
class Wap_unionpay
{
    //支付插件名称
    public $name = '银联wap支付';
    //private $SubmitUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';//提交地址
    private $SubmitUrl = 'https://101.231.204.80:5000/gateway/api/frontTransReq.do';

    public function __construct()
    {
        $CI = &get_instance();
        $CI->load->model('loop_model');
        $payment_data         = $CI->loop_model->get_id('payment', 5);
        $this->payment_config = json_decode($payment_data['config'], true);
        $this->cart_path      = dirname(__FILE__) . '/key/';//证书目录
        $this->pfx_cart_path  = dirname(__FILE__) . '/key/700000000000001_acp.pfx';
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
     * @param 发起银联支付
     */
    public function do_pay($order_info)
    {
        if (!empty($order_info)) {
            $return = array(
                //以下信息非特殊情况不需要改动
                'version'      => '5.0.0',                   //版本号
                'encoding'     => 'utf-8',                  //编码方式
                'txnType'      => '01',                      //交易类型
                'certId'       => self::get_sign_certId($this->pfx_cart_path),     //证书id
                'txnSubType'   => '01',                   //交易子类
                'bizType'      => '000201',                  //业务类型
                'frontUrl'     => $order_info['callback'],  //前台通知地址
                'frontFailUrl' => $order_info['callback'],  //交易失败跳转地址
                'backUrl'      => $order_info['server_callback'],//后台通知地址
                'signMethod'   => '01',                   //签名方法
                'channelType'  => '08',                  //渠道类型，07-PC，08-手机
                'accessType'   => '0',                    //接入类型
                'currencyCode' => '156',                //交易币种，境内商户固定156
                'merId'        => $this->payment_config['mer_id'][1],        //商户代码
                'orderId'      => $order_info['order_no'],    //商户订单号，8-32位数字字母，不能含“-”或“_”
                'txnTime'      => date('YmdHis', time()),    //订单发送时间，格式为YYYYMMDDhhmmss
                'txnAmt'       => $order_info['order_price'],	//交易金额，单位分，
            );
            //除去待签名参数数组中的空值和签名参数
            $para_filter = $this->array_filter($return);

            //对待签名参数数组排序
            $para_sort = $this->array_sort($para_filter);

            //生成签名结果
            $return['signature'] = $this->build_sign($para_sort);

            self::create_auto_form_html($return, $this->SubmitUrl);
        }
    }

    /**
     * 支付客户端回调处理
     */
    public function callback()
    {
        return self::server_callback();
    }

    /**
     * 支付服务端回调处理
     */
    public function server_callback()
    {
        $CI     = &get_instance();
        $return = $CI->input->post(NULL, true);
        if (!empty($return)) {
            $public_key    = self::get_pulbic_key_by_certId($return ['certId']);
            $signature_str = $return ['signature'];
            $signature     = base64_decode($signature_str);
            //除去待签名参数数组中的空值和签名参数
            $para_filter = $this->array_filter($return);

            //对待签名参数数组排序
            $para_sort = $this->array_sort($para_filter);

            //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
            $prestr = $this->create_linkstring($para_sort);
            //把最终的字符串签名，获得签名结果
            $params_sha1x16 = sha1($prestr, FALSE);

            $isSuccess = openssl_verify($params_sha1x16, $signature, $public_key, OPENSSL_ALGO_SHA1);

            if (!empty($isSuccess)) {
                $res = array(
                    'status'         => 'y',
                    'order_no'       => $return['orderId'],
                    'transaction_id' => $return['queryId'],
                );
                return $res;
            } else {
                $message = '签名验证失败';
            }
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
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     *              return 去掉空值与签名参数后的新签名参数组
     */
    private function array_filter($para)
    {
        $para_filter = array();
        foreach ($para as $key => $val) {
            if ($key == "signature" || $val == "") {
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
        //把最终的字符串签名，获得签名结果
        $params_sha1x16 = sha1($prestr, FALSE);
        $private_key    = self::get_private_key($this->pfx_cart_path);

        // 签名
        $sign_falg = openssl_sign($params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1);
        if ($sign_falg) {
            $signature_base64 = base64_encode($signature);
            return $signature_base64;
        } else {
            return false;//签名失败
        }
    }

    /**
     * 取证书ID(.pfx)
     * @return unknown
     */
    private function get_sign_certId($cert_path)
    {
        $pkcs12certdata = file_get_contents($cert_path);
        openssl_pkcs12_read($pkcs12certdata, $certs, $this->payment_config['sign_password'][1]);
        $x509data = $certs ['cert'];
        openssl_x509_read($x509data);
        $certdata = openssl_x509_parse($x509data);
        $cert_id  = $certdata ['serialNumber'];
        return $cert_id;
    }

    /**
     * 取证书ID(.cer)
     *
     * @param unknown_type $cert_path
     */
    function get_certid_by_cer_path($cert_path)
    {
        $x509data = file_get_contents($cert_path);
        openssl_x509_read($x509data);
        $certdata = openssl_x509_parse($x509data);
        $cert_id  = $certdata ['serialNumber'];
        return $cert_id;
    }

    /**
     * 返回(签名)证书私钥 -
     * @return unknown
     */
    private function get_private_key($cert_path)
    {
        $pkcs12 = file_get_contents($cert_path);
        openssl_pkcs12_read($pkcs12, $certs, $this->payment_config['sign_password'][1]);
        return $certs ['pkey'];
    }

    /**
     * 根据证书ID 加载 证书
     * @param unknown_type $certId
     * @return string NULL
     */
    private function get_pulbic_key_by_certId($certId)
    {
        // 证书目录
        $cert_dir = $this->cart_path;
        $handle   = opendir($cert_dir);
        if ($handle) {
            while ($file = readdir($handle)) {
                clearstatcache();
                $filePath = $cert_dir . '/' . $file;
                if (is_file($filePath)) {
                    if (pathinfo($file, PATHINFO_EXTENSION) == 'cer') {
                        if (self::get_certid_by_cer_path($filePath) == $certId) {
                            closedir($handle);
                            return self::get_public_key($filePath);//加载验签证书成功
                        }
                    }
                }
            }
            //'没有找到证书ID为[' . $certId . ']的证书'
        } else {
            //证书目录 ' . $cert_dir . '不正确
        }
        closedir($handle);
        return null;
    }

    /**
     * 取证书公钥 -验签
     *
     * @return string
     */
    private function get_public_key($cert_path)
    {
        return file_get_contents($cert_path);
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
     * 自动跳转表单
     * @param $params 要发送的参数
     * @param $reqUrl 跳转的链接
     */
    private function create_auto_form_html($params, $reqUrl)
    {
        $encodeType = isset ($params ['encoding']) ? $params ['encoding'] : 'UTF-8';
        $html       = <<<eot
                <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
                    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
                    <title>正在跳转到银联支付</title>
                </head>
                <body onload="javascript:document.pay_form.submit();">
                    跳转到银联支付.....
                    <form id="pay_form" name="pay_form" action="{$reqUrl}" method="post">

eot;
        foreach ($params as $key => $value) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
                    </form>
                </body>
                </html>
eot;
        echo $html;
    }


}