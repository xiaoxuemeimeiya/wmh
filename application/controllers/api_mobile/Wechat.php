<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Wechat extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
        $this->load->helpers('wechat_helper');
    }

    /**
     * 获取基本配置
     */
    public function wx_config()
    {
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $url = $this->input->get('url', true);
       // var_dump($url);
        $config = get_jsapi_ticket($url);

        error_json($config);

    }



}
