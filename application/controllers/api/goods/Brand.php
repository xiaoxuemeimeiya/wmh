<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Brand extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 查询所有品牌
     */
    public function index()
    {
        $where_data = array();
        $cat_id     = $this->input->get_post('cat_id', true);
        if (!empty($cat_id)) $where_data['sql'] = "find_in_set(".$cat_id.", cat_id)";
        $list       = $this->loop_model->get_list('goods_brand', $where_data, '', '', 'sortnum asc,id asc');
        if (!empty($list)) {
            error_json($list);
        } else {
            error_json('没有数据');
        }
    }

    /**
     * 根据id获取品牌信息
     */
    public function get_id()
    {
        $id = (int)$this->input->get_post('id', true);
        if (!empty($id)) {
            $category = $this->loop_model->get_id('goods_brand', $id);
            if (!empty($category)) {
                error_json($category);
            } else {
                error_json('没有数据');
            }
        } else {
            error_json('缺少参数');
        }
    }
}
