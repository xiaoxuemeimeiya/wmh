<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller
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
        //搜索条件start
        $where_data = array();
        $status     = $this->input->post_get('status');
        if ($status >= '0') $where_data['where']['status'] = $status;
        //角色
        $role_id = $this->input->post_get('role_id');
        if ($role_id != '') $where_data['where']['role_id'] = $role_id;
        //用户名
        $username = $this->input->post_get('username');
        if (!empty($username)) $where_data['where']['username'] = $username;
        $search_where = array(
            'status'   => $status,
            'role_id'  => $role_id,
            'username' => $username,
        );
        assign('search_where', $search_where);
        //搜索条件end
        //查到数据
        $list = $this->loop_model->get_list('admin', $where_data, $pagesize, $pagesize * ($page - 1));//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('admin', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end

        //角色列表
        $role_data    = $this->loop_model->get_list('role', array('where' => array('shop_id' => '0')));
        $role_list[0] = array('id' => 0, 'name' => '超级管理员');
        foreach ($role_data as $key) {
            $role_list[$key['id']] = $key;
        }
        assign('role_list', $role_list);

        assign('status', array('0' => '正常', 1 => '锁定'));//状态
        display('/system/admin/list.html');
    }


    /**
     * 添加编辑
     */
    public function edit($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $admin = $this->loop_model->get_id('admin', $id);
            assign('item', $admin);
        }

        //角色列表
        $role_list   = $this->loop_model->get_list('role', array('where' => array('status'=>0,'shop_id' => '0')));
        $role_list[] = array('id' => 0, 'name' => '超级管理员');
        assign('role_list', $role_list);
        display('/system/admin/add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post = $this->input->post(NULL, true);
            $this->load->model('system/admin_model');
            $res = $this->admin_model->update($data_post);
            if (!empty($res)) {
                error_json($res);
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
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
        $res                   = $this->loop_model->update_id('admin', $update_data, $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('修改管理员status为' . $status . 'id为' . $id);
            error_json('y');
        } else {
            error_json('操作失败');
        }
    }

    /**
     * 验证会员名是否存在
     */
    public function repeat_username()
    {
        $username = $this->input->post('param', true);
        if (!empty($username)) {
            $this->load->model('system/admin_model');
            $member_data = $this->admin_model->repeat_username($username);
            if (!empty($member_data)) {
                error_json('用户名已经存在');
            } else {
                error_json('y');
            }
        }
    }

    /**
     * 修改自己的密码
     */
    public function update_password()
    {
        $id = $this->admin_data['id'];
        if (!empty($id)) {
            $admin = $this->loop_model->get_id('admin', $id, 'username');
            assign('item', $admin);
        }

        display('/system/admin/update_password.html');
    }

    /**
     * 修改自己的密码保存
     */
    public function update_password_save()
    {
        $id = $this->admin_data['id'];
        if (!empty($id)) {
            $data_post = $this->input->post(NULL, true);
            $admin     = $this->loop_model->get_id('admin', $id);
            if ($admin['password'] != md5(md5($data_post['old_password']) . $admin['salt'])) {
                error_json('原密码错误');
            } else {
                if ($data_post['password'] != $data_post['repassword']) {
                    error_json('两次密码不一样');
                } else {
                    $update_data['salt']     = get_rand_num();
                    $update_data['password'] = md5(md5($data_post['password']) . $update_data['salt']);
                    $res                     = $this->loop_model->update_id('admin', $update_data, $id);
                    if ($res) {
                        error_json('y');
                    } else {
                        error_json('修改失败');
                    }
                }
            }
        }
    }

    /**
     * 彻底删除数据
     */
    public function delete()
    {
        $id = $this->input->post('id', true);
        if (empty($id)) error_json('id错误');
        $res = $this->loop_model->delete_where('admin', array('where_in' => array('id' => $id)));
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('彻底删除管理员' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
        }
    }
}
