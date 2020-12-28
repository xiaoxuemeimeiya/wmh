<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Model extends CI_Controller
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
        $search_where = array(
            'status' => $status,
        );
        assign('search_where', $search_where);
        //搜索条件end
        //查到数据
        $list = $this->loop_model->get_list('goods_model', $where_data, $pagesize, $pagesize * ($page - 1));//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('goods_model', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
        display('/goods/model/list.html');
    }

    /**
     * 编辑
     */
    public function edit($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item = $this->loop_model->get_id('goods_model', $id);
            $item['cat_id'] = explode(',', $item['cat_id']);
            //查询扩展属性
            $item['attr_data'] = $this->loop_model->get_list('goods_model_attr', array('where' => array('model_id' => $id)), '', '', 'id asc');
            assign('item', $item);
        }

        //商品分类
        $this->load->model('goods/category_model');
        $cat_list = $this->category_model->get_all();
        assign('cat_list', $cat_list);

        display('/goods/model/add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post = $this->input->post(NULL, true);
            $this->load->model('goods/model_model');
            $res = $this->model_model->update($data_post);
            error_json('y');
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
        $res = $this->loop_model->update_id('goods_model', array('status' => 1), $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('删除模型到回收站' . $id);
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
        $res = $this->loop_model->update_id('goods_model', array('status' => 0), $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('还原模型' . $id);
            error_json('y');
        } else {
            error_json('还原失败');
        }
    }

    /**
     * 彻底删除数据
     */
    public function delete()
    {
        $id = $this->input->post('id', true);
        if (empty($id)) error_json('id错误');
        $res = $this->loop_model->delete_id('goods_model', $id);
        if (!empty($res)) {
            admin_log_insert('彻底删除模型' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
        }
    }
}
