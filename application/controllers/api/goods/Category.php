<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 根据上级栏目查询下级栏目
     */
    public function index()
    {
        $reid  = $this->input->get_post('reid', true);
        $level = (int)$this->input->get_post('level', true);//大于0的时候需要展示下级
        if ($reid == 'flag') {
            $where_data['where'] = array('flag' => 1);
        } else {
            $reid = (int)$reid;
            if (empty($reid)) $reid = 0;
            $where_data['where'] = array('reid' => $reid);
        }
        $where_data['select'] = 'id,name,image';
        $list_data            = $this->loop_model->get_list('goods_category', $where_data, '', '', 'sortnum asc,id asc');
        if (!empty($list_data)) {
            foreach ($list_data as $key) {
                $key['down'] = '';
                //下级栏目
                if (!empty($level) && $reid != 'flag') {
                    $where_down_data['where'] = array('reid' => $key['id']);
                    $where_down_data['select'] = 'id,name,image';
                    $key['down'] = $this->loop_model->get_list('goods_category', $where_down_data, '', '', 'sortnum asc,id asc');
                }
                $list[] = $key;
            }
            error_json($list);
        } else {
            error_json('没有数据');
        }
    }

    /**
     * 根据id获取栏目信息
     */
    public function get_id()
    {
        $id = (int)$this->input->get_post('id', true);
        if (!empty($id)) {
            $category = $this->loop_model->get_id('goods_category', $id);
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
