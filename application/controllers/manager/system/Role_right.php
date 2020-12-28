<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Role_right extends CI_Controller
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
        $type   = $this->input->post_get('type');
        if (empty($status)) $status = 0;
        if (isset($status)) $where_data['where']['status'] = $status;
        if (isset($type)) $where_data['where']['type'] = $type;
        $search_where = array(
            'status' => $status,
            'type'   => $type,
        );
        assign('search_where', $search_where);
        //搜索条件end
        //查到数据
        $list = $this->loop_model->get_list('role_right', $where_data, $pagesize, $pagesize * ($page - 1), 'name desc');//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('role_right', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
        display('/system/role/right_list.html');
    }

    /**
     * 添加
     */
    public function add()
    {
        $type = $this->input->get_post('type');
        assign('type', $type);

        $dir       = APPPATH . 'controllers/' . $type;
        $file_list = self::list_file($dir, $type);
        assign('file_list', $file_list);

        display('/system/role/right_add.html');
    }

    /**
     * 编辑
     */
    public function edit($id)
    {
        $id = (int)$id;
        if (empty($id)) msg('id错误');
        $item          = $this->loop_model->get_id('role_right', $id);
        $item['right'] = explode(',', $item['right']);
        assign('item', $item);

        $type = $item['type'];
        assign('type', $type);
        $dir       = APPPATH . 'controllers/' . $type;
        $file_list = self::list_file($dir, $type);
        assign('file_list', $file_list);
        display('/system/role/right_add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'name'  => $data_post['name'],
                'right' => join(',', array_unique($data_post['right'])),
                'type'  => $data_post['type'],
            );
            if (empty($update_data['name'])) {
                error_json('权限名称不能为空');
            }
            if (empty($data_post['right'])) {
                error_json('权限码不能为空');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_id('role_right', $update_data, $data_post['id']);
                admin_log_insert('修改权限码' . $data_post['id']);
            } else {
                //增加数据
                $res = $this->loop_model->insert('role_right', $update_data);
                admin_log_insert('增加权限码' . $res);
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
        $res = $this->loop_model->update_id('role_right', array('status' => 1), $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('删除权限码到回收站' . $id);
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
        $res = $this->loop_model->update_id('role_right', array('status' => 0), $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('还原权限码' . $id);
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
        $res = $this->loop_model->delete_id('role_right', $id);
        if (!empty($res)) {
            admin_log_insert('删除权限码' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
        }
    }


    /**
     * 显示目录下的所有文件,包括子目录文件
     */
    public function list_file($file_path, $type)
    {
        $file_path      = $file_path . '/';
        $file_path_data = explode($type . '/', $file_path);
        static $file_list = array();
        $dir_data = opendir($file_path);

        while ($dir = readdir($dir_data)) {
            if (!in_array($dir, array('.', '..', '.svn', '.DS_Store'))) {
                if (is_dir($file_path . $dir)) {
                    self::list_file($file_path . $dir, $type);
                } else {
                    $file_list[] = $file_path_data[1] . basename($dir, '.php');
                }
            }
        }
        return $file_list;
    }

    /**
     * 显示文件下的所有action
     */
    public function list_action()
    {
        $file = $this->input->post('file_name', true);
        $type = $this->input->post('type', true);

        $file_dir   = APPPATH . 'controllers/' . $type . '/' . $file . '.php';
        $class_name = basename($file_dir, '.php');
        if ($class_name != 'Role_right') {
            include($file_dir);
        }
        $action_data = get_class_methods($class_name);
        foreach ($action_data as $key) {
            if (!in_array($key, array('__construct', 'get_instance'))) {
                $action_list[] = $key;
            }
        }
        echo ch_json_encode($action_list);
    }

}
