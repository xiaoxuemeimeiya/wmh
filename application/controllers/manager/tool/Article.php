<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Article extends CI_Controller
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

        $where_data['select'] = array('a.*,cat.name as cat_name');
        $where_data['join']   = array(
            array('article_cat as cat', 'a.cat_id=cat.id', 'left')
        );
        //查到数据
        $list = $this->loop_model->get_list('article as a', $where_data, $pagesize, $pagesize * ($page - 1), 'a.id desc');//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('article as a', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
        display('/tool/article/list.html');
    }


    /**
     * 添加编辑
     */
    public function edit($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item = $this->loop_model->get_id('article', $id);
            assign('item', $item);
        }

        //文章分类
        $this->load->model('tool/article_cat_model');
        $cat_list = $this->article_cat_model->get_all();
        assign('cat_list', $cat_list);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/tool/article/add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'title'    => $data_post['title'],
                'cat_id'   => (int)$data_post['cat_id'],
                'image'    => $data_post['image'],
                'desc'     => remove_xss($this->input->post('desc')),//单独过滤详情xss,,
                'edittime' => time(),
            );
            if (empty($update_data['title'])) {
                error_json('标题不能为空');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_id('article', $update_data, $data_post['id']);
                admin_log_insert('修改文章' . $data_post['id']);
            } else {
                $update_data['addtime'] = time();
                //增加数据
                $res = $this->loop_model->insert('article', $update_data);
                admin_log_insert('增加文章' . $res);
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
        $res = $this->loop_model->delete_id('article', $id);
        if (!empty($res)) {
            admin_log_insert('删除文章' . $id);
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
        $res                   = $this->loop_model->update_id('article', $update_data, $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('修改文章status为' . $status . 'id为' . $id);
            error_json('y');
        } else {
            error_json('修改失败');
        }
    }
}
