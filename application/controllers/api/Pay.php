<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Pay extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 订单或这个充值支付
     */
    public function do_pay()
    {
        //必须登录
        $this->load->helpers('web_helper');
        $member_data = member_login();
        $client      = $this->input->get_post('client', true);//来源客户端
        $order_no    = $this->input->get_post('order_no', true);//订单号,多个之间用,隔开
        $recharge_no = $this->input->get_post('recharge_no', true);//充值单号

        if (!empty($order_no)) {
            //判断订单状态
            $all_order_no = explode(',', $order_no);
            $order_price  = 0;
            foreach ($all_order_no as $key) {
                $order_data = $this->loop_model->get_where('order', array('order_no' => $key, 'payment_status' => 0, 'status' => 1));
                if (!empty($order_data)) {
                    $order_no_data[] = $order_data['order_no'];
                    $order_price     = $order_price + $order_data['order_price'];//支付金额
                    $payment_id      = $order_data['payment_id'];//支付方式
                } else {
                    //msg('订单信息错误,或者订单已支付', site_url($client . '/member/order'));
                    header('location:' . site_url($client . '/member/order'));
                    exit;
                }
                if ($order_data['payment_id'] == 1) {
                    //货到付款商品直接跳转到会员订单中心
                    header('location:' . site_url($client . '/member/order'));
                    exit;
                }
            }
            cache('save', $order_no_data[0], $order_no_data, 7200);
            $pay_data = array(
                'order_body'  => '订单支付',
                'order_no'    => $order_no_data[0],//支付单号
                'order_price' => $order_price,//支付金额
                'payment_id'  => $payment_id,//支付方式
            );
        } elseif (!empty($recharge_no)) {
            //获取充值订单详情
            $online_recharge_data = $this->loop_model->get_where('member_user_online_recharge', array('recharge_no' => $recharge_no, 'status' => 0));
            if (!empty($online_recharge_data)) {
                $pay_data = array(
                    'order_body'  => '在线充值',
                    'order_no'    => 'on' . $online_recharge_data['recharge_no'],//支付单号
                    'order_price' => $online_recharge_data['amount'],//支付金额
                    'payment_id'  => $online_recharge_data['payment_id'],//支付方式
                );
            } else {
                msg('充值信息错误不能进行支付', site_url($client . '/member'));
            }
        }

        $pay_data['client']          = $client;//当前客户端
        $pay_data['callback']        = site_url('/api/pay/callback/' . $pay_data['payment_id'] . '/' . $client);//客户端回调地址
        $pay_data['server_callback'] = site_url('/api/pay/server_callback/' . $pay_data['payment_id']);//服务端回调地址

        if (!empty($pay_data)) {
            //获取支付方式
            $payment_data = $this->loop_model->get_where('payment', array('id' => $pay_data['payment_id'], 'status' => 0));
            if (!empty($payment_data)) {
                //开始支付
                $patment_class_name = $payment_data['class_name'];
                $this->load->library('payment/' . $patment_class_name . '/' . $patment_class_name);
                $this->$patment_class_name->do_pay($pay_data);
            } else {
                msg('支付方式不存在', site_url($client . '/member'));
            }
        }
    }

    /**
     * 订单或充值支付客户端回调
     */
    public function callback($payment_id, $client = '')
    {
        $payment_data = $this->loop_model->get_where('payment', array('id' => $payment_id, 'status' => 0));

        if (!empty($payment_data)) {
            //开始支付回调处理
            $patment_class_name = $payment_data['class_name'];
            $this->load->library('payment/' . $patment_class_name . '/' . $patment_class_name);
            $pay_res = $this->$patment_class_name->callback();
            if ($pay_res['status'] == 'y') {
                msg('支付成功', site_url($client . '/member/order/'));
            } else {
                msg($pay_res['info'], site_url($client . '/member/order/'));
            }
        } else {
            msg('支付方式错误', site_url($client . '/member/order/'));
        }
    }

    /**
     * 订单或这个充值支付服务端回调
     */
    public function server_callback($payment_id)
    {
        $payment_data = $this->loop_model->get_where('payment', array('id' => $payment_id, 'status' => 0));

        if (!empty($payment_data)) {
            //开始支付回调处理
            $patment_class_name = $payment_data['class_name'];
            $this->load->library('payment/' . $patment_class_name . '/' . $patment_class_name);
            $pay_res = $this->$patment_class_name->server_callback();
            if ($pay_res['status'] == 'y') {
                //充值方式
                if (stripos($pay_res['order_no'], 'on') !== false) {
                    $order_no_data = explode('on', $pay_res['order_no']);
                    $recharge_no   = isset($order_no_data[1]) ? $order_no_data[1] : 0;
                    $this->load->model('member/user_online_recharge');
                    $res = $this->user_online_recharge->update_pay_status($recharge_no);
                    if ($res == 'y') {
                        $this->loop_model->update_where('member_user_online_recharge', array('payment_no' => $pay_res['transaction_id']), array('recharge_no' => $recharge_no));//保存交易流水号
                        $this->$patment_class_name->success();
                    } else {
                        $this->$patment_class_name->error();
                    }
                } else {
                    $order_no = cache('get', $pay_res['order_no']);//取的订单缓存
                    //订单付款
                    foreach ($order_no as $key) {
                        $this->load->model('order/order_model');
                        $order_res = $this->order_model->update_pay_status($key);
                        if ($order_res == 'y') {
                            if (!empty($pay_res['transaction_id'])) {
                                $this->loop_model->update_where('order', array('payment_no' => $pay_res['transaction_id']), array('order_no' => $key));//保存交易流水号
                            }
                        }
                    }
                    if ($order_res == 'y') {
                        $this->$patment_class_name->success();
                    } else {
                        $this->$patment_class_name->error();
                    }
                }
            } else {
                $this->$patment_class_name->error();
            }
        } else {
            echo '支付方式错误';
        }
    }
}
