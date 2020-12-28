<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_withdraw extends CI_Controller
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
        if (isset($status) && $status != '') {
            $where_data['where']['w.status'] = $status;
        }
        //用户组
        $group_id = $this->input->post_get('group_id');
        if ($group_id != '') $where_data['where']['group_id'] = $group_id;
        //关键字
        $username = $this->input->post_get('username');
        if (!empty($username)) $where_data['where']['username'] = $username;
        $search_where = array(
            'status'   => $status,
            'username' => $username,
        );
        assign('search_where', $search_where);
        //搜索条件end
        $where_data['select'] = 'm.username,w.*';
        $where_data['join']   = array(
            array('member as m', 'w.m_id=m.id')
        );
        //查到数据
        $list_data = $this->loop_model->get_list('member_user_withdraw as w', $where_data, $pagesize, $pagesize * ($page - 1), 'w.id desc');//列表
        foreach ($list_data as $key) {
            $key['amount'] = format_price($key['amount']);
            $list[]        = $key;
        }
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('member_user_withdraw as w', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end

        assign('status', array('0' => '等待审核', '1' => '审核成功', '2' => '拒绝并退还资金', '3' => '拒绝不退还资金'));//状态
        assign('type', array('1' => '银行', '2' => '支付宝', '3' => '微信'));//状态
        display('/member/user/withdraw_list.html');
    }

    /**
     * 编辑
     */
    public function edit($id)
    {
        $id = (int)$id;
        if (empty($id)) msg('id错误');
        $member_user_withdraw = $this->loop_model->get_id('member_user_withdraw', $id);
        assign('item', $member_user_withdraw);

        display('/member/user/withdraw_add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post           = $this->input->post(NULL, true);
            $update_data['note'] = $data_post['note'];
            //修改数据
            $res = $this->loop_model->update_id('member_user_withdraw', $update_data, $data_post['id']);
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
     * 修改数据状态
     */
    public function update_status()
    {
        $id     = $this->input->post('id', true);
        $status = $this->input->get_post('status', true);
        if (empty($id) || $status == '') error_json('id错误');
        $status                    = (int)$status;
        $update_data['status']     = $status;
        $update_data['endtime']    = time();
        $member_user_withdraw_data = $this->loop_model->get_id('member_user_withdraw', $id);
        if ($member_user_withdraw_data['status'] == 0) {
            $res = $this->loop_model->update_where('member_user_withdraw', $update_data, array('id' => $id));
            admin_log_insert('修改用户提现status为' . $status . 'id为' . $id);
            if ($status == 2) {
                if (!empty($res)) {
                    //开始退还资金
                    $this->load->model('member/user_account_log_model');
                    $data = array(
                        'm_id'       => $member_user_withdraw_data['m_id'],
                        'amount'     => $member_user_withdraw_data['amount'],
                        'event'      => 5,
                        'admin_user' => $this->admin_data['username'],
                        'note'       => '管理员提现拒绝退回,提现编号' . $id,
                    );
                    $this->user_account_log_model->insert($data);
                }
            }
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('操作失败');
            }
        } else {
            error_json('本条信息已经处理过,不能重复操作');
        }
    }
}
