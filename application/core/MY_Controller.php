<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    private $whiteList = [
        'api_mobile/user/reg/verify_code',//获取验证码(图片验证码)
        'api_mobile/user/reg/send',//发送验证码（手机验证码）
        'api_mobile/user/reg/user_reg',//用户注册,用户登入
        'api_mobile/goods/index/ad',//广告列表
        'api_mobile/goods/index/index',//首页列表
        'api_mobile/goods/index/group',//首页列表
        'api_mobile/goods/product/goods_list',//商品列表
        'api_mobile/goods/product/product',//商品详情
        'api_mobile/pay/do_pay',//支付
        'api_mobile/pay/callback/2/mobile',
        'api_mobile/user/info/validation_code',
        'api_mobile/user/info/free_card',
        'api_mobile/goods/index/adv',
        'api_mobile/goods/index/good',
        'api_mobile/order/invite/order_detail'
    ];
	
	public function __construct()
	{
		parent::__construct();
        $this->load->model('loop_model');
        $this->validateRequest();
	}

    public function validateRequest()
    {
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $formData = $_REQUEST;
        $url = $formData['url'];
        if (!in_array($url, $this->whiteList)) {
            /*
            if (empty($formData['m_id']) || empty($formData['timestamp']) ||
                empty($formData['sign']) || empty($formData['token'])
            ) {
                error_json('参数缺失');
            }
            */
            if (empty($formData['m_id']) || empty($formData['timestamp']) || empty($formData['token'])
            ) {
                error_json('参数缺失');
            }

            $user = $this->loop_model->get_where('user',  array('id' => $formData['m_id'],'is_delete'=>0));
            if(!$user) {
                error_json('用户不存在');
            }
            //暂时关闭token验证

            if (!$this->tokenVerify($formData['token'], $formData['m_id'])) {
                error_json('令牌失效','3');
            }

        }

        // sign verify
        /*暂时不验证签名
        if (!$this->signVerify($formData, $formData['sign'],$formData['m_id'])) {
            error_json('签名错误');
         }
        */
        return true;
    }

    public function tokenVerify($token, $m_id)
    {
        $userToken = cache('get', 'user_token_'.$m_id);
        if (empty($userToken)) {
            //error_json('令牌失效');
            return false;
        }
        if (!empty($userToken) && $token !== $userToken) {
            error_json('token different');
            return false;
        }
        return true;
    }

    public function signVerify($formData, $sign,$m_id)
    {
        $checkSign = self::calculateSignature($formData, cache('get', 'user_token_'.$m_id));
        return ($checkSign === $sign);
    }

    public static function paramsFilter($params)
    {
        $paramsFilter = array();
        while (list ($key, $value) = each ($params)) {
            if ($key == "r" || $key == "sign" || $key == "ui" || $key == "thumb"|| $value == "") {
                continue;
            } else {
                $paramsFilter[$key] = $params[$key];
            }
        }
        return $paramsFilter;
    }

    public static function paramsSort($params)
    {
        ksort($params);
        reset($params);
        return $params;
    }

    public static function createLinkString($params)
    {
        $string = "";
        while (list ($key, $val) = each($params)) {
            $string .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $string = substr($string, 0, count($string) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $string = stripslashes($string);
        }

        return $string;
    }

    public static function calculateSignature($formData, $salt)
    {
        $filterParams = self::paramsFilter($formData);
        $sortParams = self::paramsSort($filterParams);
        $prepareParams = self::createLinkString($sortParams);
        return md5($prepareParams . $formData['timestamp'] . $salt);
    }

}

class Manager_Controller extends CI_Controller {
	private $admin_data;//后台用户登录信息
    public function __construct()
    {
        parent::__construct();
		$this->load->helpers('manager_helper');
        $this->admin_data = manager_login();
        assign('admin_data', $this->admin_data);
        $this->load->model('loop_model');
		$this->pageSize = 15;
    }
}