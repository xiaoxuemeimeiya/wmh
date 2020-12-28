<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Role extends CI_Controller
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
        $status = $this->input->post_get('status');
        if (empty($status)) $status = 0;
        if (isset($status)) $where_data['where']['status'] = $status;
        $where_data['where']['shop_id'] = '0';
        $search_where                   = array(
            'status' => $status,
        );
        assign('search_where', $search_where);
        //搜索条件end
        //查到数据
        $list = $this->loop_model->get_list('role', $where_data, $pagesize, $pagesize * ($page - 1), 'name desc');//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('role', array());//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
        display('/system/role/list.html');
    }

    /**
     * 添加编辑
     */
    public function edit($id = '')
    {
        $id             = (int)$id;
        $item['rights'] = '';
        if (!empty($id)) {
            $item = $this->loop_model->get_id('role', $id);
        }
        assign('item', $item);

        //权限资源
        $where_data['where'] = array(
            'status' => '0',
            'type'   => 'manager',
        );
        $role_right_list     = $this->loop_model->get_list('role_right', $where_data, '', '', 'name desc');//列表
        foreach ($role_right_list as $val => $key) {
            preg_match('/\[.*?\]/', $key['name'], $right_pre);
            if (isset($right_pre[0])) {
                $arr_key               = trim($right_pre[0], '[]');
                $right_arr[$arr_key][] = $key;
            }
        }
        assign('right_arr', $right_arr);

        display('/system/role/add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'name' => $data_post['name'],
            );
            if (empty($update_data['name'])) {
                error_json('角色名称不能为空');
            }
            if (empty($data_post['right'])) {
                error_json('角色权限不能为空');
            }

            //组合权限
            $rights_arr = array();
            $right_list = $this->loop_model->get_list('role_right', array('where_in' => array('id'=>$data_post['right']), 'select' => '`right`'));

            foreach ($right_list as $val => $key) {
                $rights_arr[] = trim($key['right'], ',');
            }

            $update_data['rights'] = empty($rights_arr) ? '' : join(',', $rights_arr);

            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_where('role', $update_data, array('id' => $data_post['id'], 'shop_id' => '0'));
                admin_log_insert('修改角色' . $data_post['id']);
            } else {
                //增加数据
                $res = $this->loop_model->insert('role', $update_data);
                admin_log_insert('增加角色' . $res);
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
     * 删除数据到回收站
     */
    public function delete_recycle()
    {
        $id = $this->input->post('id', true);
        if (empty($id)) error_json('id错误');
        $res = $this->loop_model->update_id('role', array('status' => 1), $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('删除角色到回收站' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
        }
    }

    /**
     * 回收站还原
     */
    public function reduction_recycle()
    {
        $id = $this->input->post('id', true);
        if (empty($id)) error_json('id错误');
        $res = $this->loop_model->update_id('role', array('status' => 0), $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('还原角色' . $id);
            error_json('y');
        } else {
            error_json('还原失败');
        }
    }

    /**
     * 删除数据
     */
    public function delete()
    {
        $id = $this->input->post('id', true);
        if (empty($id)) error_json('id错误');
        $res = $this->loop_model->delete_id('role', $id);
        if (!empty($res)) {
            admin_log_insert('删除角色' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
        }
    }

}
