<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Area extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 获取列表
     */
    public function get_list()
    {
        //默认id
        header('Access-Control-Allow-Origin: *');
        $parent_id  = (int)$this->input->get_post('parent_id');//上级id
        $default_id = (int)$this->input->get_post('default_id');//默认id
        if (empty($parent_id)) $parent_id = 0;

        $list = $this->loop_model->get_list('areas', array('where' => array('parent_id' => $parent_id)), '', '', 'sortnum asc,area_id asc');//列表
        if ($parent_id == 0) {
            echo '<option value="">请选择省</option>';
        } else {
            echo '<option value="">请选择</option>';
        }
        foreach ($list as $k) {
            $option = '<option value="' . $k['area_id'] . '"';
            if ($k['area_id'] == $default_id) {
                $option .= " selected";
            }
            $option .= '>' . $k['area_name'] . '</option>';
            echo $option;
        }
    }

    /**
     * 根据地区id获取名称
     */
    public function get_name()
    {
        //地区id,多个id以英文逗号分开
        $area_id = $this->input->get_post('area_id');
        if (!empty($area_id)) {
            if (!strpos(',', $area_id)) {
                $area_id = explode(',', $area_id);
            }
            $this->load->model('areas_model');
            $area_name = $this->areas_model->get_name($area_id);
            echo join(',', $area_name);
        }
    }
}
