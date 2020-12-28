<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_point_log_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 会员积分流水变动
     * @return array
     */
    public function insert($data)
    {
        $error  = array('status' => 'n', 'info' => '处理失败', 'code' => '');
        $amount = (int)$data['amount'];
        if (empty($data['m_id'])) {
            $error['info'] = '用户id不能为空';
            return $error;
        } elseif (empty($data['event'])) {
            $error['info'] = '积分类型不能为空';
            return $error;
        } elseif (!isset($amount)) {
            $error['info'] = '积分不能为空';
            return $error;
        } elseif ($amount < 0) {
            $error['info'] = '积分不能小于0';
            return $error;
        } else {
            $member_data = self::get_member($data['m_id']);
            if (empty($member_data)) {
                $error['info'] = '用户数据不存在';
                return $error;
            }
            $type = self::get_type($data['event']);
            if ($type != 1 && $type != 2) {
                $error['info'] = '类型错误';
                return $error;
            }
            if ($type == 1) {
                $amount_log = $member_data['point'] + $amount;
            } elseif ($type == 2) {
                $amount_log = $member_data['point'] - $amount;
                if ($amount_log < 0) {
                    $error['info'] = '积分账户余额不足';
                    $error['code'] = 'm_p_500';
                    return $error;
                }
            }
            $this->db->trans_start();
            $this->db->where(array('m_id' => $data['m_id']));
            $this->db->update('member_user', array('point' => $amount_log));
            $update_data = array(
                'm_id'       => $data['m_id'],
                'amount'     => $amount,
                'amount_log' => $amount_log,
                'event'      => $data['event'],
                'addtime'    => time(),
                'admin_user' => $data['admin_user'],
                'note'       => $data['note'],
            );
            $this->db->insert('member_user_point_log', $update_data);
            $res_log = $this->db->insert_id();
            $this->db->trans_complete();
            $res_data = $this->db->get_where('member_user_point_log', array('id' => $res_log));
            if (!empty($res_data)) {
                $error['status'] = 'y';
                $error['id']     = $res_log;
            }
            return $error;
        }
    }

    /**
     * 会员信息
     * @return array
     */
    public function get_member($m_id)
    {
        $m_id = (int)$m_id;
        $this->load->model('loop_model');
        $member_data = $this->loop_model->get_where('member_user', array('m_id' => $m_id));
        if (!empty($member_data)) {
            return $member_data;
        }
    }

    /**
     * 资金类型
     * @return array
     */
    public function get_type($event)
    {
        $event = (int)$event;
        //1为增加2为减少
        switch ($event) {
            case 1:
                $type = 1;//下单获得
                break;
            case 2:
                $type = 1;//活动赠送
                break;
            case 3:
                $type = 2;//订单支付
                break;
            case 4:
                $type = 1;//系统充值
                break;
            case 5:
                $type = 2;//系统扣除
                break;
            default:
                $type = '';
        }
        //1为增加2为减少
        if ($type == 1 || $type == 2) {
            return $type;
        }
    }

    /**
     * 资金变动类型名称
     * @return array
     */
    public function get_type_name()
    {
        return array(1 => '下单获得', 2 => '活动赠送', 3 => '订单支付', 4 => '系统充值', 5 => '系统扣除');
    }
}
