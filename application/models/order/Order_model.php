<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Order_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 订单详情
     * @param $order_id           int 订单id
     */
    public function get_order($order_id)
    {
        if (!empty($order_id)) {
            $this->load->model('loop_model');
            $order_data                        = $this->loop_model->get_id('order', $order_id);
            if (!empty($order_data)) {
                $order_data['sku_price']           = format_price($order_data['sku_price']);
                $order_data['sku_price_real']      = format_price($order_data['sku_price_real']);
                $order_data['delivery_price']      = format_price($order_data['delivery_price']);
                $order_data['delivery_price_real'] = format_price($order_data['delivery_price_real']);
                $order_data['discount_price']      = format_price($order_data['discount_price']);
                $order_data['order_price']         = format_price($order_data['order_price']);
                return $order_data;
            }
        }
        return false;
    }

    /**
     * 添加订单
     * @return array
     */
    public function add($order_data = array(), $sku_list = '')
    {
        $this->load->model('loop_model');
        if (!empty($order_data)) {
            //$this->db->trans_start();
            //$res1 = $this->loop_model->insert('order',$order_data);return $res1;
            $res1 = $this->db->insert('order', $order_data);
            $res = $this->db->insert_id();
/*
            foreach ($sku_list as $key) {
                $insert_data = array();
                $insert_data = array(
                    'order_id'            => $res,
                    'goods_id'            => $key['goods_id'],
                    'goods_name'          => $key['name'],
                    'sku_id'              => $key['id'],
                    'sku_no'              => $key['sku_no'],
                    'sku_image'           => $key['image'],
                    'sku_num'             => $key['num'],
                    'sku_market_price'    => price_format($key['market_price']),
                    'sku_sell_price_real' => price_format($key['sell_price']),
                    'sku_weight'          => $key['weight'],
                    'sku_value'           => ch_json_encode($key['value']),
                    'shop_id'             => $key['shop_id'],
                );
                $this->db->insert('order_sku', $insert_data);
                self::update_store_nums($key['id'], $key['num'], 'reduce');//更新商品库存
            }
            $this->db->trans_complete();
            */
        }

        if (!empty($res)) {
            return $res;
        } else {
            return false;
        }
    }

    /**
     * 支付成功后修改订单状态
     * @param $order_no  string 订单编号
     * @param $admin_id  int    管理员ID
     * @param $note      string 收款的备注
     * @return false or int order_id
     */
    public function update_pay_status($order_no, $admin_user = '', $note = '')
    {
        //获取订单信息
        $this->load->model('loop_model');
        $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no));

        if (empty($order_data)) {
            return '订单信息不存在';
        }

        if ($order_data['payment_status'] == 1) {
            return 'y';
        } else if ($order_data['payment_status'] == 0) {
            $update_data = array(
                'status'         => $order_data['status'] > 2 ? $order_data['payment_status'] : 2,
                'paytime'        => time(),
                'payment_status' => 1,
            );
            $res         = $this->loop_model->update_id('order', $update_data, $order_data['id']);
            if (empty($res)) {
                return '修改失败';
            }

            //插入收款单
            $collection_data = array(
                'order_id'   => $order_data['id'],
                'm_id'       => $order_data['m_id'],
                'amount'     => $order_data['order_price'],
                'addtime'    => time(),
                'payment_id' => $order_data['payment_id'],
                'note'       => $note,
                'admin_user' => $admin_user ? $admin_user : 0
            );
            $this->loop_model->insert('order_collection_doc', $collection_data);

            return 'y';
        } else {
            return false;
        }
    }


    /**
     * 管理员支付订单
     * @param $order_id   int 订单id
     * @param $admin_user int 管理员
     * @param $note       string 备注
     */
    public function admin_pay($order_id, $admin_user, $note = '')
    {
        if (!empty($order_id) && !empty($admin_user)) {
            $this->load->model('loop_model');
            $order_data = $this->loop_model->get_id('order', $order_id);
            if (is_pay($order_data)) {
                $order_log_data = array(
                    'order_id'   => $order_data['id'],
                    'admin_user' => $admin_user,
                    'action'     => '支付',
                    'addtime'    => date('Y-m-d H:i:s', time()),
                    'note'       => $note,
                );
                $res            = $this->loop_model->insert('order_log', $order_log_data);
                if (!empty($res)) {
                    //修改订单状态
                    $up_res = self::update_pay_status($order_data['order_no'], $admin_user, $note);
                    if (!empty($up_res)) {
                        return 'y';
                    } else {
                        return '支付失败';
                    }
                }
            } else {
                return '该订单已支付或者是货到付款';
            }
        }
    }

    /**
     * 管理员确认订单
     * @param $order_id   int 订单id
     * @param $admin_user int 管理员
     * @param $note       string 备注
     */
    public function admin_confirm($order_id, $admin_user, $note = '')
    {
        if (!empty($order_id) && !empty($admin_user)) {
            $this->load->model('loop_model');
            $order_data = $this->loop_model->get_id('order', $order_id);
            if (is_confirm($order_data)) {
                $order_log_data = array(
                    'order_id'   => $order_data['id'],
                    'admin_user' => $admin_user,
                    'action'     => '确认',
                    'addtime'    => date('Y-m-d H:i:s', time()),
                    'note'       => $note,
                );
                $res            = $this->loop_model->insert('order_log', $order_log_data);
                if (!empty($res)) {
                    //修改订单状态
                    $up_res = self::confirm($order_data['id'], $admin_user, $note);
                    return 'y';
                } else {
                    return '确认失败';
                }
            } else {
                return '订单还不满足确认条件';
            }
        }
    }

    /**
     * 确认订单
     * @param $order_id   int 订单id
     * @param $admin_user int 管理员id
     * @param $note       string 备注
     */
    public function confirm($order_id, $admin_user = '', $note = '')
    {
        if (!empty($order_id)) {
            $this->load->model('loop_model');
            $order_data = $this->loop_model->get_id('order', $order_id);
            $this->load->helpers('order_helper');
            if (is_confirm($order_data)) {
                //查询是否还有退款在处理
                $order_refund_doc_data = $this->loop_model->get_where('order_refund_doc', array('order_id' => $order_data['id'], 'status' => 0));
                if (empty($order_refund_doc_data)) {
                    //修改订单状态
                    $up_res = self::update_pay_status($order_data['order_no'], $admin_user, $note);

                    //确认订单
                    $res = $this->loop_model->update_where('order', array('status' => 4, 'completetime' => time()), array('id' => $order_id));
                    if (!empty($res)) {
                        //转账给商家(货到付款订单不结算)
                        if ($order_data['payment_id'] != 1) {
                            //计算订单实际收款金额(支付金额-退款金额)
                            $order_refund_doc_where = array(
                                'where' => array(
                                    'order_id' => $order_data['id'],
                                    'status'   => 2,
                                )
                            );
                            $refund_doc_data        = $this->loop_model->get_list('order_refund_doc', $order_refund_doc_where);
                            //退款金额
                            $refund_price = 0;
                            foreach ($refund_doc_data as $key) {
                                $refund_price = $refund_price + $key['amount'];
                            }
                            //实际订单成交金额
                            $order_pay_price = $order_data['order_price'] - $refund_price;
                            //结算手续费
                            $commission_price = round($order_pay_price * (config_item('shop_withdraw_commission') / 100));

                            //实际结算金额
                            $actual_pay_price = $order_pay_price - $commission_price;
                            //开始转账给商家
                            $this->load->model('member/user_account_log_model');
                            $account_data = array(
                                'm_id'   => $order_data['shop_id'],
                                'amount' => $actual_pay_price,
                                'event'  => 100,
                                'note'   => '订单【' . $order_data['order_no'] . '】确认结算,其中退款【￥' . format_price($refund_price) . '】,结算手续费【￥' . format_price($commission_price) . '】',
                            );
                            $account_log  = array();
                            $account_log  = $this->user_account_log_model->insert($account_data);
                            if ($account_log['status'] == 'y') {
                                //修改订单结算状态
                                $this->loop_model->update_where('order', array('is_shop_checkout' => 1), array('id' => $order_id));
                            }
                            //发放积分start
                            $this->load->model('member/user_point_log_model');
                            $data   = array(
                                'm_id'   => $order_data['m_id'],
                                'amount' => round(format_price($order_pay_price)),
                                'event'  => 1,
                                'note'   => '订单【' . $order_data['order_no'] . '】确认结算获得',
                            );
                            $this->user_point_log_model->insert($data);
                            //发放积分end
                            //开始赠送礼品
                            $this->load->model('market/promotion_model');
                            $this->promotion_model->give_list($order_data);
                            //开始发放提成等
                            self::flag_amount($order_data['m_id'], $order_pay_price);
                        }
                        //修改商品销量
                        $order_sku_list = $this->loop_model->get_list('order_sku', array('where' => array('order_id' => $order_data['id'])));
                        foreach ($order_sku_list as $key) {
                            if ($key['is_refund'] == 0) {
                                self::update_goods_sale($key['goods_id'], $key['sku_num'], 'add');//更新商品销量
                            }
                        }
                        return 'y';
                    } else {
                        return '确认失败';
                    }
                } else {
                    return '订单还有退款在处理中';
                }
            } else {
                return '订单还不满足确认条件';
            }
        }
    }

    /**
     * 开始发放推荐奖励
     * @param $m_id        int 用户id
     * @param $order_price int 订单金额
     */
    public function flag_amount($m_id, $order_price)
    {

        $share_type = config_item('share_type');
        //分享开启了才开始算提成
        if ($share_type > 0) {
            //发放一级提成
            $flag_one_member = $this->loop_model->get_id('member', $m_id);
            if (!empty($flag_one_member['flag_user'])) {
                self::flag_reward($flag_one_member['flag_user'], floor($order_price * (config_item('share_one_proportion') / 100)));
            }
            if ($share_type == 2 && !empty($flag_one_member['flag_user'])) {
                //发放二级提成
                $flag_two_member = $this->loop_model->get_id('member', $flag_one_member['flag_user']);
                if (!empty($flag_two_member['flag_user'])) {
                    self::flag_reward($flag_two_member['flag_user'], floor($order_price * (config_item('share_two_proportion') / 100)));
                }
            }
        }
    }

    /**
     * 发放推荐奖励到用户账户
     * @param $m_id  int 用户id
     * @param $price int 金额
     */
    public function flag_reward($m_id, $price)
    {
        if (!empty($m_id) && !empty($price)) {
            $type = config_item('share_price_type');
            $this->load->model('loop_model');
            if ($type == 'balance') {
                //开始扣除余额
                $this->load->model('member/user_account_log_model');
                $data   = array(
                    'm_id'   => $m_id,
                    'amount' => $price,
                    'event'  => 6,
                    'note'   => '推荐用户下单奖励',
                );
                $log_id = $this->user_account_log_model->insert($data);
            } elseif ($type == 'redpack') {
                //这里是微信红包
            }
            //开始给用户发送通知
            $oauth_query = $this->db->get_where('member_oauth', array('m_id' => $m_id, 'oauth_type' => 'wechat'));
            $oauth_data  = $oauth_query->row_array();
            if (!empty($oauth_data['oauth_id'])) {
                $this->load->library('wechat/message_template');
                $this->message_template->share_amount($oauth_data['oauth_id'], format_price($price));
            }
        }
    }

    /**
     * 管理员作废订单
     * @param $order_id   int 订单id
     * @param $admin_user int 管理员id
     * @param $note       string 备注
     */
    public function admin_cancel($order_id, $admin_user, $note = '')
    {
        if (!empty($order_id) && !empty($admin_user)) {
            $this->load->model('loop_model');
            $order_data = $this->loop_model->get_id('order', $order_id);
            if ($order_data['status'] != 9) {
                $order_log_data = array(
                    'order_id'   => $order_data['id'],
                    'admin_user' => $admin_user,
                    'action'     => '作废',
                    'addtime'    => date('Y-m-d H:i:s', time()),
                    'note'       => $note,
                );
                $res            = $this->loop_model->insert('order_log', $order_log_data);
                if (!empty($res)) {
                    //修改订单状态
                    $res      = $this->loop_model->update_where('order', array('status' => 9), array('id' => $order_id));
                    $sku_list = $this->loop_model->get_list('order_sku', array('where' => array('order_id' => $order_id)));
                    if($sku_list) {
                        foreach ($sku_list as $key) {
                            //没有发货的还原商品库存
                            if ($key['is_send'] == 0) {
                                self::update_store_nums($key['sku_id'], $key['sku_num'], 'add');
                            }
                        }
                        //退还优惠券
                        $this->load->model('market/coupons_model');
                        $this->coupons_model->back_coupons($order_data['coupons_id']);
                    }
                    return 'y';
                } else {
                    return '作废失败';
                }
            } else {
                return '订单不能作废';
            }
        }
    }

    /**
     * 用户取消
     * @param $order_id int 订单id
     * @param $m_id     int 用户id
     */
    public function cancel($order_id, $m_id)
    {
        if (!empty($order_id) && !empty($m_id)) {
            $this->load->model('loop_model');
            $order_data = $this->loop_model->get_id('order', $order_id);
            $res = $this->loop_model->update_where('order', array('status' => 8), array('id' => $order_id, 'status' => 1, 'm_id' => $m_id));
            if (!empty($res)) {
                $sku_list = $this->loop_model->get_list('order_sku', array('where' => array('order_id' => $order_id)));
                foreach ($sku_list as $key) {
                    //没有发货的还原商品库存
                    if ($key['is_send'] == 0) {
                        self::update_store_nums($key['sku_id'], $key['sku_num'], 'add');
                    }
                }
                //退还优惠券
                $this->load->model('market/coupons_model');
                $this->coupons_model->back_coupons($order_data['coupons_id']);
                return 'y';
            } else {
                return '订单取消失败';
            }
        }
    }

    /**
     * 订单发货
     * @param $order_id           int 订单id
     * @param $express_company_id int 物流公司id
     * @param $delivery_code      string 快递单号
     * @param $admin_user         int 管理员
     * @param $note               string 备注
     */
    public function send($order_id, $express_company_id, $delivery_code, $admin_user, $note = '')
    {
        if (!empty($order_id) && !empty($express_company_id) && !empty($delivery_code) && !empty($admin_user)) {
            $this->load->model('loop_model');
            $order_data = $this->loop_model->get_id('order', $order_id);
            if (is_send($order_data)) {
                //修改订单状态
                $this->db->trans_start();
                //添加订单日志
                $order_log_data = array(
                    'order_id'   => $order_data['id'],
                    'admin_user' => $admin_user,
                    'action'     => '发货',
                    'addtime'    => date('Y-m-d H:i:s', time()),
                    'note'       => $note,
                );
                $this->db->insert('order_log', $order_log_data);

                //添加发货单
                $delivery_doc_data = array(
                    'order_id'           => $order_data['id'],
                    'm_id'               => $order_data['m_id'],
                    'admin_user'         => $admin_user,
                    'shop_id'            => $order_data['shop_id'],
                    'addtime'            => time(),
                    'delivery_code'      => $delivery_code,
                    'express_company_id' => $express_company_id,
                    'note'               => $note,
                );
                $this->db->insert('order_delivery_doc', $delivery_doc_data);
                $this->db->insert_id();

                //修改订单状态
                $this->db->where('id', $order_data['id']);
                $this->db->update('order', array('status' => 3, 'delivery_status' => 1, 'sendtime' => time()));

                //修改订单商品发货状态
                $this->db->where(array('order_id' => $order_data['id'], 'is_refund' => 0));
                $this->db->update('order_sku', array('is_send' => 1));
                $up_res = $this->db->affected_rows();

                $this->db->trans_complete();

                if (!empty($up_res)) {
                    return 'y';
                } else {
                    return '发货失败';
                }
            } else {
                return '该订单已发货';
            }
        }
    }

    /**
     * 订单退款添加
     * @param $refund_doc_data 退款单数据
     * @param $admin_user      管理员
     */
    public function refund_add($refund_doc_data, $admin_user = '')
    {
        if (!empty($refund_doc_data)) {
            $this->db->trans_start();
            //添加退款单
            $this->db->insert('order_refund_doc', $refund_doc_data);
            $doc_id = $this->db->insert_id();

            //修改订单商品发货状态
            $this->db->where('id', $refund_doc_data['sku_id']);
            $this->db->update('order_sku', array('is_refund' => 1));
            $this->db->affected_rows();

            //修改订单状态
            $this->db->where('id', $refund_doc_data['order_id']);
            $this->db->update('order', array('status' => 10));

            //添加退款日志
            $admin_user != '' ? $is_admin = '管理员' : $is_admin = '用户';
            $doc_data = array(
                'doc_id'     => $doc_id,
                'desc'       => $is_admin . '提交了退款申请',
                'addtime'    => time(),
                'admin_user' => $admin_user,
            );
            $this->db->insert('order_refund_doc_log', $doc_data);
            $this->db->trans_complete();
            return $doc_id;
        }
    }

    /**
     * 订单退款编辑
     * @param $id          退款单id
     * @param $amount      金额
     * @param $admin_user  管理员
     * @param $channel     退款方式 1为退款到余额,2为其他渠道
     * @param $desc        备注
     * @param $status      是否同意 0.请用户提交补充资料,1.拒绝,2.同意
     */
    public function refund_edit($id, $amount, $admin_user, $channel = 1, $desc = '', $status = 0)
    {
        $id      = (int)$id;
        $amount  = (int)$amount;
        $channel = (int)$channel;
        $status  = (int)$status;
        if (!empty($id) && $amount >= 0 && !empty($admin_user) && !empty($channel)) {
            $order_refund_doc_data = $this->loop_model->get_id('order_refund_doc', $id);
            if ($order_refund_doc_data['status'] == 0) {
                $amount = price_format($amount);
                if ($amount < 0) error_json('退款金额不能少于0');

                //修改金额
                $order_sku_data = $this->loop_model->get_where('order_sku', array('id' => $order_refund_doc_data['sku_id']));
                if ($amount > $order_sku_data['sku_sell_price_real'] * $order_sku_data['sku_num']) {
                    return '退款金额不能大于' . format_price($order_sku_data['sku_sell_price_real']) * $order_sku_data['sku_num'] . '元';
                }
                $this->loop_model->update_id('order_refund_doc', array('amount' => $amount), $id);

                if ($status == 0) {
                    //用户提交补充资料
                    //添加退款日志
                    $doc_data = array(
                        'doc_id'     => $id,
                        'desc'       => $desc,
                        'addtime'    => time(),
                        'admin_user' => $admin_user,
                    );
                    $res      = $this->db->insert('order_refund_doc_log', $doc_data);
                    if (!empty($res)) {
                        return 'y';
                    } else {
                        return '处理失败';
                    }
                } else if ($status == 1) {
                    //拒绝
                    $res = self::refund_refuse($id, $admin_user, $desc);
                    if (!empty($res)) {
                        return 'y';
                    } else {
                        return '处理失败';
                    }
                } else if ($status == 2) {
                    //同意
                    $res = self::refund_confirm($id, $admin_user, $desc, $channel);
                    if (!empty($res)) {
                        return 'y';
                    } else {
                        return '处理失败';
                    }
                }
            }
        }
    }

    /**
     * 订单退款成功
     * @param $id         退款单id
     * @param $channel    int 退款流向 1为余额账户,2为线下
     * @param $admin_user 管理员
     */
    public function refund_confirm($id, $admin_user, $desc = '', $channel = 1)
    {
        if (!empty($id) && !empty($admin_user) && !empty($channel)) {
            $query           = $this->db->get_where('order_refund_doc', array('id' => $id));
            $refund_doc_data = $query->row_array();

            //订单商品
            $order_sku_query = $this->db->get_where('order_sku', array('id' => $refund_doc_data['sku_id']));
            $order_sku_data  = $order_sku_query->row_array();

            $refund_delivery_price = 0;//邮费金额
            //没有发货的修改商品库存和查看是否需要返还邮费
            if ($order_sku_data['is_send'] == 0) {
                //修改库存
                self::update_store_nums($order_sku_data['sku_id'], $order_sku_data['sku_num'], 'add');
                //查询订单是否发货
                $order_query = $this->db->get_where('order', array('id' => $refund_doc_data['order_id']));
                $order_data  = $order_query->row_array();

                if ($order_data['delivery_status'] == 0) {
                    //查询是否全部退款,如果是全部退款需要返还邮费(没有发货才返还)
                    $sku_wait_refund_query = $this->db->get_where('order_sku', array('order_id' => $refund_doc_data['order_id'], 'is_refund!=' => 2, 'id!=' => $refund_doc_data['sku_id']));
                    $sku_wait_refund_data  = $sku_wait_refund_query->row_array();
                    if (empty($sku_wait_refund_data)) {
                        $refund_delivery_price = $order_data['delivery_price_real'];
                    }
                }
            }

            $this->db->trans_start();
            //修改退款单状态
            $this->db->where('id', $id);
            $res = $this->db->update('order_refund_doc', array('status' => 2, 'dispose_time' => time(), 'admin_user' => $admin_user));

            //修改订单商品退款状态
            $this->db->where('id', $refund_doc_data['sku_id']);
            $this->db->update('order_sku', array('is_refund' => 2));

            //查询订单下没有退款的订单个数
            $this->db->from('order_sku');
            $this->db->where(array('order_id' => $refund_doc_data['order_id'], 'is_refund' => 0));
            $order_sku_num = $this->db->count_all_results();

            //修改订单状态
            $this->db->where('id', $refund_doc_data['order_id']);
            $this->db->update('order', array('status' => 7));//先全部修改成部分退款的状态
            if (empty($order_sku_num)) {
                //全部商品都已经退款的时候直接结算订单
                self::confirm($refund_doc_data['order_id'], $admin_user, '全部退款后自动确认');
                $this->db->where('id', $refund_doc_data['order_id']);
                $this->db->update('order', array('status' => 6));//修改订单状态为退款完成
                //退还优惠券
                $this->load->model('market/coupons_model');
                $this->coupons_model->back_coupons($order_data['coupons_id']);
            }
            $this->db->trans_complete();

            if ($channel == 1) {
                //开始退款给用户
                $this->load->model('member/user_account_log_model');
                $account_data = array(
                    'm_id'       => $refund_doc_data['m_id'],
                    'amount'     => $refund_doc_data['amount'] + $refund_delivery_price,
                    'event'      => 4,
                    'admin_user' => $admin_user,
                    'note'       => $admin_user . '处理店铺【' . $refund_doc_data['shop_id'] . '】订单【' . $refund_doc_data['order_id'] . '】商品【' . $refund_doc_data['id'] . '】退款,其中邮费【￥' . format_price($refund_delivery_price) . '】',
                );
                $this->user_account_log_model->insert($account_data);
            }

            //记录订单日志
            $order_log_data = array(
                'order_id'   => $refund_doc_data['order_id'],
                'admin_user' => $admin_user,
                'action'     => '退款',
                'addtime'    => date('Y-m-d H:i:s', time()),
                'note'       => '订单【' . $refund_doc_data['order_id'] . '】商品【' . $refund_doc_data['id'] . '】由【' . $admin_user . '】退款,金额' . format_price($refund_doc_data['amount']),
            );
            $this->db->insert('order_log', $order_log_data);

            //添加退款日志
            $doc_data = array(
                'doc_id'     => $id,
                'desc'       => '同意退款申请:处理意见【' . $desc . '】',
                'addtime'    => time(),
                'admin_user' => $admin_user,
            );
            $this->db->insert('order_refund_doc_log', $doc_data);
            return $res;
        }
    }

    /**
     * 订单退款拒绝
     * @param $id         退款单id
     * @param $admin_user 管理员
     */
    public function refund_refuse($id, $admin_user, $desc = '')
    {
        if (!empty($id) && !empty($admin_user)) {
            $query           = $this->db->get_where('order_refund_doc', array('id' => $id));
            $refund_doc_data = $query->row_array();

            //订单信息
            $order_query = $this->db->get_where('order', array('id' => $refund_doc_data['order_id']));
            $order_data  = $order_query->row_array();

            $this->db->trans_start();
            //修改退款单状态
            $this->db->where('id', $id);
            $res = $this->db->update('order_refund_doc', array('status' => 2, 'dispose_time' => time(), 'admin_user' => $admin_user));

            //修改订单商品退款状态
            $this->db->where('id', $refund_doc_data['sku_id']);
            $this->db->update('order_sku', array('is_refund' => 2));

            //修改订单状态
            $this->db->where('id', $refund_doc_data['order_id']);
            if ($order_data['delivery_status'] > 0) {
                $this->db->update('order', array('status' => 3));
            } else {
                $this->db->update('order', array('status' => 2));
            }

            $this->db->trans_complete();

            //记录订单日志
            $order_log_data = array(
                'order_id'   => $refund_doc_data['order_id'],
                'admin_user' => $admin_user,
                'action'     => '拒绝退款',
                'addtime'    => date('Y-m-d H:i:s', time()),
                'note'       => '订单【' . $refund_doc_data['order_id'] . '】商品【' . $refund_doc_data['id'] . '】由【' . $admin_user . '】拒绝退款,金额' . format_price($refund_doc_data['amount']),
            );
            $this->db->insert('order_log', $order_log_data);

            //添加退款日志
            $doc_data = array(
                'doc_id'     => $id,
                'desc'       => '管理员拒绝了退款申请:处理意见【' . $desc . '】',
                'addtime'    => time(),
                'admin_user' => $admin_user,
            );
            $this->db->insert('order_refund_doc_log', $doc_data);
            return $res;
        }
    }

    /**
     * 根据订单id查询订单商品详情
     * @param $order_id 订单id
     */
    public function get_order_sku($order_id)
    {
        if (!empty($order_id)) {
            $this->db->from('order_sku');
            $this->db->where(array('order_id' => $order_id));
            $this->db->order_by('id desc');
            $query    = $this->db->get();
            $sku_list = $query->result_array();//echo $this->db->last_query()."<br>";
            foreach ($sku_list as $k) {
                $k['sku_value']           = json_decode($k['sku_value'], true);
                $k['sku_market_price']    = format_price($k['sku_market_price']);
                $k['sku_sell_price_real'] = format_price($k['sku_sell_price_real']);
                $list[]                   = $k;
            }
            return $list;

        }
    }

    /**
     * 商品存库更新操作
     * @param $sku_id    skuID
     * @param $goods_num 改变的库存数量
     * @param $type      增加或者减少 add 或者 reduce
     */
    public function update_store_nums($sku_id, $goods_num, $type = 'add')
    {
        $this->load->model('loop_model');
        if (!empty($sku_id) && !empty($goods_num)) {
            if ($type == 'add') {
                $type = '+';
            } else {
                $type = '-';
            }
            $sku_data = $this->loop_model->get_id('goods_sku', $sku_id);
            //更新sku库存
            $this->loop_model->update_id('goods_sku', array('set' => array(array('store_nums', 'store_nums' . $type . $goods_num))), $sku_id);
            //更新商品库存
            $this->loop_model->update_id('goods', array('set' => array(array('store_nums', 'store_nums' . $type . $goods_num))), $sku_data['goods_id']);
        }
    }

    /**
     * 商品销量更新操作
     * @param $goods_id  商品ID
     * @param $goods_num 商品数量
     * @param $type      增加或者减少 add 或者 reduce
     */
    public function update_goods_sale($goods_id, $goods_num, $type = 'add')
    {
        $this->load->model('loop_model');
        if (!empty($goods_id) && !empty($goods_num)) {
            if ($type == 'add') {
                $type = '+';
            } else {
                $type = '-';
            }
            //更新商品销量
            $this->loop_model->update_id('goods', array('set' => array(array('sale', 'sale' . $type . $goods_num))), $goods_id);
        }
    }

    /**
     * 系统自动确认订单
     */
    public function auto_confirm()
    {
        $where      = array(
            'where'    => array(
                'sendtime<'       => time() - (config_item('order_auto_confirm') * 60),
            ),
            'where_in' => array(
                'status' => array(3, 7),
            ),
        );
        $order_list = $this->loop_model->get_list('order', $where);
        if (!empty($order_list)) {
            foreach ($order_list as $key) {
                $up_res = self::confirm($key['id'], '系统', '系统自动确认');
                if ($up_res == 'y') {
                    $order_log_data = array(
                        'order_id'   => $key['id'],
                        'admin_user' => '系统',
                        'action'     => '确认',
                        'addtime'    => date('Y-m-d H:i:s', time()),
                        'note'       => '系统自动确认',
                    );
                    $res            = $this->loop_model->insert('order_log', $order_log_data);
                }
            }
        }
    }

    /**
     * 系统自动取消订单
     */
    public function auto_cancel()
    {
        $where      = array(
            'where' => array(
                'status'       => 1,
                'payment_id!=' => 1,
                'addtime<'     => time() - (config_item('order_auto_cancel') * 60),//2天
            ),
        );
        $order_list = $this->loop_model->get_list('order', $where);
        if (!empty($order_list)) {
            foreach ($order_list as $key) {
                $up_res = self::admin_cancel($key['id'], '系统', '系统自动作废');

            }
        }
    }

}
