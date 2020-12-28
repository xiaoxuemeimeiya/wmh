<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_group extends CI_Controller
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
        $list = $this->loop_model->get_list('member_user_group', array(), $pagesize, $pagesize * ($page - 1));//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('member_user_group', array());//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
        display('/member/user/group_list.html');
    }

    /**
     * 编辑
     */
    public function edit($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item = $this->loop_model->get_id('member_user_group', $id);
            assign('item', $item);
        }
        display('/member/user/group_add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post                 = $this->input->post(NULL, true);
            $update_data['group_name'] = $data_post['group_name'];
            $update_data['discount']   = $data_post['discount'];
            if (empty($update_data['group_name'])) {
                error_json('名称不能为空');
            }
            if ($update_data['discount'] < 0 || $update_data['discount'] > 100) {
                error_json('折扣率只能在0-100之间');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_id('member_user_group', $update_data, $data_post['id']);
                admin_log_insert('修改会员组' . $data_post['id']);
            } else {
                //增加数据
                $res = $this->loop_model->insert('member_user_group', $update_data);
                admin_log_insert('增加会员组' . $res);
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
        if ($id == 1) error_json('默认用户组不能删除');
        $res = $this->loop_model->delete_id('member_user_group', $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('删除会员组' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
        }
    }

}
