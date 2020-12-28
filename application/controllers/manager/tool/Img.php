<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Img extends CI_Controller
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

        //关键字
        $title = $this->input->post_get('title');
        if (!empty($title)) $where_data['like']['title'] = $title;

        $search_where = array(
            'title'       => $title,
        );
        assign('search_where', $search_where);

        $where_data['select'] = array('*');
        //查到数据
        $list = $this->loop_model->get_list('img', $where_data, $pagesize, $pagesize * ($page - 1), 'id desc');//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('img', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
        assign('domain', $_SERVER['SERVER_NAME']);

        display('/tool/img/list.html');
    }

    /**
     * 添加编辑
     */
    public function edit($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item               = $this->loop_model->get_id('img', $id);
            assign('item', $item);
        }
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/tool/img/add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'title'       => $data_post['title'],
                'addtime'     => $data_post['start_time'],
                'link'        => $data_post['link'],
                'sortnum'     => $data_post['sortnum'],
            );
            if (empty($update_data['title'])) {
                error_json('名称不能为空');
            }
            
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_id('img', $update_data, $data_post['id']);
                admin_log_insert('修改图片' . $data_post['id']);
            } else {
                //增加数据
                $res = $this->loop_model->insert('img', $update_data);
                admin_log_insert('增加图片' . $res);
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
        $res = $this->loop_model->delete_id('img', $id);
        if (!empty($res)) {
            admin_log_insert('删除图片' . $id);
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
        $res                   = $this->loop_model->update_id('img', $update_data, $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('修改广告status为' . $status . 'id为' . $id);
            error_json('y');
        } else {
            error_json('修改失败');
        }
    }
}
