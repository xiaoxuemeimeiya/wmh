<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Order extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('web_helper');
        $this->member_data = member_login();
        $this->load->model('loop_model');
        $this->load->helpers('order_helper');
    }

    /**
     * 订单评论提交
     */
    public function comment()
    {
        if (is_post()) {
            $id = (int)$this->input->get_post('order_id', true);
            if (empty($id)) error_json('订单ID错误');
            $order_data = $this->loop_model->get_where('order', array('id' => $id));
            if (is_comment($order_data)) {
                $order_sku     = $this->loop_model->get_list('order_sku', array('where' => array('order_id' => $order_data['id'])));//商品列表
                $comment_level = $this->input->get_post('comment_level', true);//评价等级
                $desc          = $this->input->get_post('desc', true);//评价内容

                if (count($order_sku) == count($comment_level) && count($order_sku) == count($desc)) {
                    //开始修改订单状态
                    $res = $this->loop_model->update_where('order', array('status' => 5), array('id' => $id, 'm_id' => $this->member_data['id']));
                    if (!empty($res)) {
                        $level_goods_num = 0;//好评数量
                        $level_bad_num   = 0;//差评数量
                        foreach ($order_sku as $key) {
                            if ($comment_level[$key['id']] == 1) $level_goods_num++;
                            if ($comment_level[$key['id']] == 3) $level_bad_num++;
                            $comment_data = array(
                                'goods_id'     => $key['goods_id'],
                                'shop_id'      => $key['shop_id'],
                                'm_id'         => $this->member_data['id'],
                                'order_id'     => $id,
                                'order_sku_id' => $key['id'],
                                'sku_value'    => $key['sku_value'],
                                'level'        => $comment_level[$key['id']],
                                'desc'         => $desc[$key['id']],
                                'addtime'      => time(),
                            );
                            $this->loop_model->insert('goods_comment', $comment_data);
                            //修改商品评论数量
                            $this->loop_model->update_id('goods', array('set' => array(array('comments', 'comments+1'))), $key['goods_id']);
                        }
                        //修改店铺评价数
                        if ($level_goods_num > 0 || $level_bad_num > 0) {
                            //查询目前好评数
                            $shop_data     = $this->loop_model->get_where('member_shop', array('m_id' => $order_data['shop_id']));
                            $goods_comment = $shop_data['goods_comment'] + $level_goods_num - $level_bad_num;//计算本次修改好评数
                            if ($goods_comment < 0) $goods_comment = 0;
                            $this->load->model('member/shop_model');
                            $shop_level = $this->shop_model->shop_level($goods_comment);//店铺等级
                            $this->loop_model->update_where('member_shop', array('goods_comment' => $goods_comment, 'level' => $shop_level), array('m_id' => $order_data['shop_id'], 'm_id' => $order_data['shop_id']));
                        }
                        error_json('y');
                    }
                } else {
                    error_json('请评价所有商品');
                }
            } elseif ($order_data['status'] == 5) {
                error_json('订单已经评价');
            } else {
                error_json('订单还不能评价');
            }
        }
    }


    /**
     * 订单退款
     */
    public function refund()
    {
        if (is_post()) {
            $id   = (int)$this->input->get_post('order_sku_id', true);
            $desc = $this->input->get_post('desc', true);
            if (empty($id)) error_json('订单商品ID错误');
            if (empty($desc)) error_json('退款理由不能为空');
            $order_sku_data = $this->loop_model->get_where('order_sku', array('id' => $id));
            //订单信息
            $order_data = $this->loop_model->get_where('order', array('id' => $order_sku_data['order_id']));
            if (is_refund($order_data)) {
                if ($order_sku_data['is_refund'] == 1) {
                    error_json('已经提交了退款申请,请等待处理');
                } elseif ($order_sku_data['is_refund'] == 2) {
                    error_json('退款申请已经处理完成');
                } else {
                    //开始添加退款单
                    $refund_doc_data = array(
                        'order_id' => $order_data['id'],
                        'm_id'     => $this->member_data['id'],
                        'amount'   => $order_sku_data['sku_sell_price_real'] * $order_sku_data['sku_num'],
                        'goods_id' => $order_sku_data['goods_id'],
                        'sku_id'   => $order_sku_data['id'],
                        'addtime'  => time(),
                        'note'     => $desc,
                        'shop_id'  => $order_data['shop_id'],
                    );
                    $this->load->model('order/order_model');
                    $res = $this->order_model->refund_add($refund_doc_data);
                    if (!empty($res)) {
                        error_json('y');
                    } else {
                        error_json('申请失败');
                    }
                }
            } else {
                error_json('已经确认和未支付的不能退款');
            }
        }
    }

    /**
     * 订单退款补充资料
     */
    public function refund_doc_log()
    {
        if (is_post()) {
            $id   = (int)$this->input->get_post('refund_doc_id', true);
            $desc = $this->input->post('desc', true);
            if (empty($id)) error_json('退款ID错误');
            if (empty($desc)) error_json('补充资料不能为空');
            $refund_doc_data = $this->loop_model->get_where('order_refund_doc', array('id' => $id));
            if ($refund_doc_data['status'] == 0) {
                $refund_doc_log_data = array(
                    'doc_id'  => $id,
                    'desc'    => $desc,
                    'addtime' => time(),
                );
                $res                 = $this->loop_model->insert('order_refund_doc_log', $refund_doc_log_data);
                if (!empty($res)) {
                    error_json('y');
                } else {
                    error_json('提交失败');
                }
            } else {
                error_json('已经处理的退款单不能再补充资料');
            }
        }
    }

    /**
     * 取消订单
     */
    public function cancel()
    {
        if (is_post()) {
            $id = (int)$this->input->get_post('id', true);
            if (empty($id)) error_json('订单ID错误');
            $this->load->model('order/order_model');
            $res = $this->order_model->cancel($id, $this->member_data['id']);
            error_json($res);
        }
    }

    /**
     * 确认订单
     */
    public function confirm()
    {
        if (is_post()) {
            $id = (int)$this->input->get_post('id', true);
            if (empty($id)) error_json('订单ID错误');
            //已经发货的才能确认
            $this->load->model('order/order_model');
            $res = $this->order_model->confirm($id, '', '用户确认');
            error_json($res);
        }
    }

    /**
     * 物流查询
     */
    public function delivery_status()
    {
        $id = (int)$this->input->get_post('id', true);
        if (empty($id)) error_json('订单ID错误');
        $order_delivery_doc_data = $this->loop_model->get_where('order_delivery_doc', array('order_id' => $id, 'm_id' => $this->member_data['id']));
        if (!empty($order_delivery_doc_data)) {
            //查询快递编号
            $express_company_data = $this->loop_model->get_id('express_company', $order_delivery_doc_data['express_company_id']);
            $this->load->library('kdniao_api');
            $status_list = $this->kdniao_api->get_traces($express_company_data['code'], $order_delivery_doc_data['delivery_code']);
            if (!empty($status_list)) {
                $result = array(
                    'order_delivery_doc' => $order_delivery_doc_data,
                    'express_company'    => $express_company_data,
                    'status_list'        => json_decode($status_list, true),
                );
                error_json($result);
            } else {
                error_json('物流信息查询失败');
            }
        } else {
            error_json('订单还没发货');
        }
    }

    /**
     * 用户删除订单
     */
    public function delete()
    {
        if (is_post()) {
            $id = (int)$this->input->get_post('id', true);
            if (empty($id)) error_json('订单ID错误');
            $order_data = $this->loop_model->get_id('order', $id);
            //已经取消或者作废的才能删除
            if (is_delete($order_data)) {
                $res = $this->loop_model->update_where('order', array('status' => 0), array('id' => $id, 'm_id' => $this->member_data['id']));
                if (!empty($res)) {
                    error_json('y');
                } else {
                    error_json('删除失败');
                }
            } else {
                error_json('只有取消的订单才能删除');
            }
        }
    }

    /**
     * 用户更换订单支付方式
     */
    public function update_payment()
    {
        if (is_post()) {
            $id         = (int)$this->input->get_post('id', true);
            $payment_id = (int)$this->input->get_post('payment_id', true);
            if (empty($id)) error_json('订单ID错误');
            if (empty($payment_id) || $payment_id == 1) error_json('支付方式ID错误');//不能使用货到付款
            $order_data = $this->loop_model->get_id('order', $id);
            $payment_data = $this->loop_model->get_id('payment', $payment_id);//支付方式
            if (empty($payment_data) || $payment_data['status'] != 0) error_json('支付方式不存在');
            if (is_pay($order_data)) {
                $res = $this->loop_model->update_where('order', array('payment_id' => $payment_id), array('id' => $id, 'm_id' => $this->member_data['id']));
                if (!empty($res)) {
                    error_json('y');
                } else {
                    error_json('支付方式没有变化');
                }
            } else {
                error_json('订单状态不支持修改支付方式');
            }
        }
    }

    /**
     * 订单支付状态
     */
    public function order_pay_status()
    {
        if (is_post()) {
            $order_no = $this->input->get_post('order_no', true);
            if (empty($order_no)) error_json('订单号错误');
            $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no));
            if ($order_data['payment_status']==1) {
                error_json('y');
            } else {
                error_json('订单还未支付');
            }
        }
    }

}
