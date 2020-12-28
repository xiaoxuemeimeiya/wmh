<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Cat extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $this->load->model('loop_model');
    }

    /**
     * 分类列表
     */
    public function cat()
    {
        $where_data['select'] = 'id,name,image,reid';
        $where_data['where']['reid'] = 1;//免费
        $list_data            = $this->loop_model->get_list('goods_category', $where_data, '', '', 'sortnum asc,id asc');
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $list_data;
        echo json_encode($this->ResArr);exit;

    }

}
