<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Adv_position extends CI_Controller
{

    private $admin_data;//后台用户登录信息

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('manager_helper');
        $this->admin_data = manager_login();
        assign('admin_data', $this->admin_data);
        $this->load->model('loop_model');
    }

    /**
     * 列表
     */
    public function index()
    {
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //查到数据
        $list = $this->loop_model->get_list('adv_position', array(), $pagesize, $pagesize * ($page - 1));//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('adv_position', array());//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
        display('/tool/adv/position_list.html');
    }


    /**
     * 添加编辑
     */
    public function edit($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item = $this->loop_model->get_id('adv_position', $id);
            assign('item', $item);
        }

        display('/tool/adv/position_add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'name'      => $data_post['name'],
                'width'     => (int)$data_post['width'],
                'height'    => (int)$data_post['height'],
                'play_type' => (int)$data_post['play_type'],
                'status'    => (int)$data_post['status'],
            );
            if (empty($update_data['name'])) {
                error_json('名称不能为空');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_id('adv_position', $update_data, $data_post['id']);
                admin_log_insert('修改广告位' . $data_post['id']);
            } else {
                //增加数据
                $res = $this->loop_model->insert('adv_position', $update_data);
                admin_log_insert('增加广告位' . $res);
            }
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }

    }

    /**
     * 删除数据
     */
    public function delete()
    {
        $id = $this->input->post('id', true);
        if (empty($id)) error_json('id错误');
        $res = $this->loop_model->delete_id('adv_position', $id);
        if (!empty($res)) {
            admin_log_insert('删除广告位' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
        }
    }

    /**
     * 修改数据状态
     */
    public function update_status()
    {
        $id     = $this->input->post('id', true);
        $status = $this->input->get_post('status', true);
        if (empty($id) || $status == '') error_json('id错误');
        $status                = (int)$status;
        $update_data['status'] = $status;
        $res                   = $this->loop_model->update_id('adv_position', $update_data, $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('修改广告位status为' . $status . 'id为' . $id);
            error_json('y');
        } else {
            error_json('修改失败');
        }
    }
}
