<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_online_recharge extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 会员资金流水变动
     * @return array
     */
    public function update_pay_status($recharge_no)
    {
        if (!empty($recharge_no)) {
            //获取订单信息
            $this->load->model('loop_model');
            $online_recharge_data = $this->loop_model->get_where('member_user_online_recharge', array('recharge_no'=>$recharge_no));
            //开始处理
            if ($online_recharge_data['status']==0) {
                //修改状态
                $res = $this->loop_model->update_id('member_user_online_recharge', array('status'=>1), $online_recharge_data['id']);
                if (!empty($res)) {
                    //开始划入资金
                    $this->load->model('member/user_account_log_model');
                    $data = array(
                        'm_id'       => $online_recharge_data['m_id'],
                        'amount'     => $online_recharge_data['amount'],
                        'event'      => 1,
                        'note'       => '在线充值',
                    );
                    $log_id = $this->user_account_log_model->insert($data);
                    if ($log_id['status']=='y') {
                        return 'y';
                    } else {
                        return '资金转入失败';
                    }
                } else {
                    return '状态修改失败';
                }
            } else {
                return '该充值订单已经处理';
            }
        }

    }
}
