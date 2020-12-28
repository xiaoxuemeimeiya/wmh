<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Balance extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('web_helper');
        $this->member_data = get_member_data();
        $this->load->model('loop_model');
    }

    /**
     * 提现申请处理
     */
    public function withdraw_save()
    {
        if (is_post()) {
            $data_post = $this->input->post(NULL, true);
            if (empty($data_post['amount'])) error_json('提现金额不能为空');
            if (empty($data_post['name'])) error_json('姓名不能为空');
            if (empty($data_post['pay_number'])) error_json('账户不能为空');
            if ($data_post['type'] == 1 && empty($data_post['bank_name'])) error_json('开户银行不能为空');
            $member_user_data = $this->loop_model->get_where('member_user', array('m_id' => $this->member_data['id']));
            $amount           = price_format($data_post['amount']);
            if ($amount <= 0) {
                error_json('提现金额不能小等于0');
            } elseif ($member_user_data['balance'] < $amount) {
                error_json('账户余额不足');
            } else {
                //开始扣除资金
                $this->load->model('member/user_account_log_model');
                $data   = array(
                    'm_id'   => $this->member_data['id'],
                    'amount' => $amount,
                    'event'  => 2,
                    'note'   => '用户申请提现',
                );
                $log_id = $this->user_account_log_model->insert($data);
                if ($log_id['status'] == 'y') {
                    //开始添加提现单
                    $withdraw_data = array(
                        'm_id'       => $this->member_data['id'],
                        'amount'     => $amount,
                        'name'       => $data_post['name'],
                        'bank_name'  => $data_post['bank_name'],
                        'pay_number' => $data_post['pay_number'],
                        'type'       => $data_post['type'],
                        'addtime'    => time(),
                        'note'       => $data_post['note'],
                    );
                    $res           = $this->loop_model->insert('member_user_withdraw', $withdraw_data);
                    if (!empty($res)) {
                        error_json('y');
                    } else {
                        error_json('申请失败');
                    }
                } else {
                    error_json($log_id['info']);
                }
            }
        } else {
            error_json('提交方式错误');
        }
    }
}
