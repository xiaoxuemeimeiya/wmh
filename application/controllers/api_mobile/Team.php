<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Team extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 分类列表
     */
    public function team_list()
    {
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');

        $list_data   = $this->loop_model->get_list('position', array('is_del'=>0), '', '', 'id asc');

        error_json($list_data);

    }

}
