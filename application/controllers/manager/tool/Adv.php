<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Adv extends CI_Controller
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

        //位置
        $position_id = $this->input->post_get('position_id');
        if (!empty($position_id)) $where_data['where']['position_id'] = $position_id;
        $search_where = array(
            'position_id' => $position_id,
            'title'       => $title,
        );
        assign('search_where', $search_where);

        $where_data['select'] = array('a.*,ap.name as position_name');
        $where_data['join']   = array(
            array('adv_position as ap', 'a.position_id=ap.id', 'left')
        );
        //查到数据
        $list = $this->loop_model->get_list('adv as a', $where_data, $pagesize, $pagesize * ($page - 1), 'a.id desc');//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('adv as a', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end

        //广告位列表
        $position_list = $this->loop_model->get_list('adv_position', array('where' => array('status' => '0')));
        assign('position_list', $position_list);
        display('/tool/adv/list.html');
    }

    /**
     * 添加编辑
     */
    public function edit($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item               = $this->loop_model->get_id('adv', $id);
            $item['start_time'] =  $item['start_time'] ? date('Y-m-d H:i:s', $item['start_time']) : '';
            $item['end_time']   =  $item['end_time'] ? date('Y-m-d H:i:s', $item['end_time']) : '';
            assign('item', $item);
        }
        //广告位列表
        $position_list = $this->loop_model->get_list('adv_position', array('where' => array('status' => '0')));
        assign('position_list', $position_list);

        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/tool/adv/add.html');
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
                'type'        => (int)$data_post['type'],
                'position_id' => (int)$data_post['position_id'],
                'start_time'  => strtotime($data_post['start_time']),
                'end_time'    => strtotime($data_post['end_time']),
                'link'        => $data_post['link'],
                'sortnum'     => (int)$data_post['sortnum'],
            );
            //内容
            switch ($update_data['type']) {
                case 1:
                    $update_data['desc'] = $data_post['desc_1'];
                    break;
                case 2:
                    $update_data['desc'] = $data_post['desc_2'];
                    break;
                case 3:
                    $update_data['desc'] = $data_post['desc_3'];
                    break;
            }
            if (empty($update_data['title'])) {
                error_json('名称不能为空');
            }
            $this->load->helpers('form_validation_helper');
            if (!is_url($data_post['link']) && !empty($data_post['link'])) {
                error_json('链接地址错误');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_id('adv', $update_data, $data_post['id']);
                admin_log_insert('修改广告' . $data_post['id']);
            } else {
                //增加数据
                $res = $this->loop_model->insert('adv', $update_data);
                admin_log_insert('增加广告' . $res);
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
        $res = $this->loop_model->delete_id('adv', $id);
        if (!empty($res)) {
            admin_log_insert('删除广告' . $id);
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
        $res                   = $this->loop_model->update_id('adv', $update_data, $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('修改广告status为' . $status . 'id为' . $id);
            error_json('y');
        } else {
            error_json('修改失败');
        }
    }
}
