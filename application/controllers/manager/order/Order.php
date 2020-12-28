<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Order extends CI_Controller
{

    private $admin_data;//后台用户登录信息

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('manager_helper');
        $this->admin_data = manager_login();
        assign('admin_data', $this->admin_data);
        $this->load->model('loop_model');
        $this->load->helpers('order_helper');
    }

    /**
     * 列表
     */
    public function index()
    {
        //自动执行start********************************************
        $this->load->model('order/order_model');
        $this->order_model->auto_cancel();//自动取消超时的订单
        $this->order_model->auto_confirm();//自动确认超时的订单
        //自动执行end**********************************************

        $export   = $this->input->get('export');//是否导出
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        //是否删除
        $is_del = $this->input->post_get('is_del');
        if ($is_del == 1) {
            $where_data['where']['is_del'] = $is_del;
        } else {
            $where_data['where']['is_del'] = 0;
        }
        //状态
        $status = $this->input->post_get('status');
        if ($status == 1) {
            //待支付的
            $where_data['where']['status']      = 1;
            $where_data['where']['payment_id>'] = 1;
        } elseif ($status == 2) {
            //待发货的
            $where_data['sql'] = '((payment_id=1 and status=1) or (status=2))';
        } elseif ($status != '') {
            $where_data['where']['status'] = $status;
        }

        //支付状态
        $payment_status = $this->input->post_get('payment_status');
        if ($payment_status != '') $where_data['where']['payment_status'] = $payment_status;

        //关键字
        $keyword_where = $this->input->post_get('keyword_where');
        $keyword       = $this->input->post_get('keyword');
        if (!empty($keyword_where) && !empty($keyword)) $where_data['where'][$keyword_where] = $keyword;
        $search_where = array(
            'is_del'         => $is_del,
            'status'         => $status,
            //'shop_id'        => $shop_id,
            'payment_status' => $payment_status,
            'keyword_where'  => $keyword_where,
            'keyword'        => $keyword,
        );
        assign('search_where', $search_where);
        //搜索条件end
        $where_data['select'] = 'o.*,m.nickname as username';
        $where_data['join']   = array(
            array('user as m', 'o.m_id=m.id')
        );

        //支付方式列表
        $payment_data = $this->loop_model->get_list('payment', array(), '', '', 'id asc');
        foreach ($payment_data as $key) {
            $payment_list[$key['id']] = $key;
        }
        assign('payment_list', $payment_list);


        //查到数据
        if (empty($export)) {
            $list = $this->loop_model->get_list('order as o', $where_data, $pagesize, $pagesize * ($page - 1), 'o.id desc');//列表
            assign('list', $list);
            //开始分页start
            $all_rows = $this->loop_model->get_list_num('order as o', $where_data);//所有数量
            assign('page_count', ceil($all_rows / $pagesize));
            //开始分页end


            display('/order/order/list.html');
        } else {
            $list = $this->loop_model->get_list('order as o', $where_data, '', '', 'o.id desc');//列表
            $this->load->library('PHPExcel');
            $this->load->library('PHPExcel/IOFactory');
            $resultPHPExcel = new PHPExcel();
            $i              = 1;
            $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, '订单号');
            $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, '收货人');
            $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, '支付状态');
            $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, '订单状态');
            $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, '支付方式');
            $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, '用户名');
            $resultPHPExcel->getActiveSheet()->setCellValue('G' . $i, '支付时间');
            $resultPHPExcel->getActiveSheet()->setCellValue('H' . $i, '支付金额');
            $resultPHPExcel->getActiveSheet()->setCellValue('I' . $i, '商品');
            foreach ($list as $key) {
                $i++;
                if ($key['payment_status'] == 0) {
                    $payment_status = '未支付';
                } elseif ($key['payment_status'] == 1) {
                    $payment_status = '已支付';
                }
                if ($key['payment_id'] == 1) {
                    $payment_name = '货到付款';
                } elseif ($key['payment_status'] == 1) {
                    $payment_name = $payment_list[$key['payment_id']]['name'];
                }
                $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, $key['order_no']);
                $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, $key['full_name']);
                $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, $payment_status);
                $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, get_order_status_text($key));
                $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, $payment_name);
                $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, $key['username']);
                $resultPHPExcel->getActiveSheet()->setCellValue('G' . $i, date('Y-m-d H:i:s', $key['paytime']));
                $resultPHPExcel->getActiveSheet()->setCellValue('H' . $i, '￥' . format_price($key['order_price']));
                //订单商品
                $this->load->model('order/order_model');
                $sku_list  = array();
                $sku_list  = $this->order_model->get_order_sku($key['id']);
                $goods_str = '';
                foreach ($sku_list as $k) {
                    $goods_str .= "商品名称：" . $k['goods_name'];
                    if (!empty($k['sku_value'])) {
                        $goods_str .= " 规格：";
                        foreach ($k['sku_value'] as $sku_val) {
                            $goods_str .= $sku_val['name'];
                            if ($sku_val['type'] == 1) {
                                $goods_str .= $sku_val['value'];
                            } else if ($sku_val['type'] == 2) {
                                $goods_str .= $sku_val['note'];
                            }
                        }
                    }
                    $goods_str .= "\r\n";
                }
                $resultPHPExcel->getActiveSheet()->setCellValue('I' . $i, $goods_str);
            }
            $outputFileName = "订单.xls";
            $xlsWriter      = new PHPExcel_Writer_Excel5($resultPHPExcel);
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header('Content-Disposition:inline;filename="' . $outputFileName . '"');
            header("Content-Transfer-Encoding: binary");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            $xlsWriter->save("php://output");
        }
    }

    /**
     * 编辑改价
     */
    public function edit($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item                        = $this->loop_model->get_id('order', $id);
            $item['sku_price_real']      = format_price($item['sku_price_real']);//商品销售价格
            $item['promotion_price']     = format_price($item['promotion_price']);//优惠活动价格
            $item['coupons_price']       = format_price($item['coupons_price']);//优惠券价格
            $item['discount_price']      = format_price($item['discount_price']);//改价金额
            $item['delivery_price_real'] = format_price($item['delivery_price_real']);//邮费
            assign('item', $item);
            display('/order/order/add.html');
        }
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post = $this->input->post(NULL, true);
            $item      = $this->loop_model->get_id('order', $data_post['id']);
            if ($item['status'] > 1) {
                error_json('已经支付的订单不允许修改');
            } else {
                $abs_discount_price  = price_format(abs($data_post['discount_price']));
                $discount_price      = price_format($data_post['discount_price']);
                $delivery_price_real = price_format((int)$data_post['delivery_price_real']);
                if (!is_numeric($abs_discount_price)) error_json('改价只能是数字');
                $end_sku_price = $item['sku_price_real'] + $discount_price - $item['promotion_price'] - $item['coupons_price'];
                if ($end_sku_price < 0) error_json('折扣金额不能大于优惠后订单商品价格');
                if ($delivery_price_real < 0) error_json('邮费不能小于0');

                $update_data                = array(
                    'full_name'           => $data_post['full_name'],
                    'tel'                 => $data_post['tel'],
                    'prov'                => $data_post['prov'],
                    'city'                => $data_post['city'],
                    'area'                => $data_post['area'],
                    'address'             => $data_post['address'],
                    'delivery_price_real' => $delivery_price_real,
                    'discount_price'      => $discount_price,
                    'admin_desc'          => $data_post['admin_desc'],
                );
                $update_data['order_price'] = $end_sku_price + $delivery_price_real;//订单价格
                $res                        = $this->loop_model->update_id('order', $update_data, $data_post['id']);
                if (!empty($res)) {
                    //插入日志
                    $order_log_data = array(
                        'order_id'   => $data_post['id'],
                        'admin_user' => $this->admin_data['username'],
                        'action'     => '编辑',
                        'addtime'    => date('Y-m-d H:i:s', time()),
                        'note'       => '编辑订单,优惠价格:' . $data_post['discount_price'] . ';运费:' . $data_post['delivery_price_real'] . ';收货人:' . $data_post['full_name'] . ';收货人电话:' . $data_post['tel'],
                    );
                    $this->loop_model->insert('order_log', $order_log_data);
                    error_json('y');
                } else {
                    error_json('保存失败');
                }
            }
        } else {
            error_json('提交方式错误');
        }

    }

    /**
     * 管理员备注
     */
    public function admin_desc()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'admin_desc' => $data_post['admin_desc'],
            );
            $res         = $this->loop_model->update_id('order', $update_data, $data_post['id']);
            if (!empty($res)) {
                //插入日志
                $order_log_data = array(
                    'order_id'   => $data_post['id'],
                    'admin_user' => $this->admin_data['username'],
                    'action'     => '备注',
                    'addtime'    => date('Y-m-d H:i:s', time()),
                    'note'       => $data_post['admin_desc'],
                );
                $this->loop_model->insert('order_log', $order_log_data);
                error_json('y');
            } else {
                error_json('保存失败');
            }
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
        $res = $this->loop_model->update_id('order', array('is_del' => 1), $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('删除订单到回收站' . $id);
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
        $res = $this->loop_model->update_id('order', array('is_del' => 0), $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('还原订单' . $id);
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
        $res = $this->loop_model->delete_id('order', $id);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('彻底删除商品' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
        }
    }

    /**
     * 订单详情
     */
    public function view($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $order_data                        = $this->loop_model->get_id('order', $id);
            $order_data['sku_price']           = format_price($order_data['sku_price']);
            $order_data['sku_price_real']      = format_price($order_data['sku_price_real']);
            $order_data['discount_price']      = format_price($order_data['discount_price']);
            $order_data['promotion_price']     = format_price($order_data['promotion_price']);
            $order_data['coupons_price']       = format_price($order_data['coupons_price']);
            $order_data['order_price']         = format_price($order_data['order_price']);
            //订单商品
            $this->load->model('order/order_model');
            $sku_list = $this->order_model->get_order_sku($id);
            assign('sku_list', $sku_list);
            //下单用户
            $member_data = $this->loop_model->get_id('member', $order_data['m_id']);
            assign('member_data', $member_data);
            //支付方式
            $payment_data = $this->loop_model->get_id('payment', $order_data['payment_id']);
            assign('payment_data', $payment_data);


            //订单日志
            $order_log_list = $this->loop_model->get_list('order_log', array('where' => array('order_id' => $order_data['id'])), '', '', 'id asc');
            assign('order_log_list', $order_log_list);

            //收款日志
            $order_collection_doc_where     = array(
                'select' => 'doc.*,p.name',
                'where'  => array('order_id' => $order_data['id']),
                'join'   => array(
                    array('payment as p', 'p.id=doc.payment_id')
                ),
            );
            $order_collection_doc_list_data = $this->loop_model->get_list('order_collection_doc as doc', $order_collection_doc_where, '', '', 'id asc');
            foreach ($order_collection_doc_list_data as $key) {
                $key['amount']               = format_price($key['amount']);
                $order_collection_doc_list[] = $key;
            }
            assign('order_collection_doc_list', $order_collection_doc_list);

            //退款日志
            $order_refund_doc_data = $this->loop_model->get_list('order_refund_doc', array('where' => array('order_id' => $order_data['id'])), '', '', 'id asc');
            foreach ($order_refund_doc_data as $key) {
                $key['log']              = $this->loop_model->get_list('order_refund_doc_log', array('where' => array('doc_id' => $key['id'])), '', '', 'id asc');
                $key['amount']           = format_price($key['amount']);
                $order_refund_doc_list[] = $key;
            }
            assign('order_refund_doc_list', $order_refund_doc_list);
            display('/order/order/view.html');
        }
    }

    /**
     * 管理员支付订单
     */
    public function order_pay($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $note = $this->input->post('note', true);
            $this->load->model('order/order_model');
            $up_res = $this->order_model->admin_pay($id, $this->admin_data['username'], $note);
            error_json($up_res);
        }
    }

    /**
     * 订单发货
     */
    public function order_send($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $order_data = $this->loop_model->get_id('order', $id);
            if (is_send($order_data)) {
                assign('order_data', $order_data);
                //物流公司
                $express_company_data = $this->loop_model->get_list('express_company', array('where' => array('status' => 0)));
                assign('express_company_data', $express_company_data);
                display('/order/order/send.html');
            } else {
                echo '不满足发货条件';
            }
        }
    }

    /**
     * 订单发货保存
     */
    public function order_send_save()
    {
        $id = (int)$this->input->post('id', true);
        if (!empty($id)) {
            $express_company_id = $this->input->post('express_company_id', true);
            $delivery_code      = $this->input->post('delivery_code', true);
            $note               = $this->input->post('note', true);
            $this->load->model('order/order_model');
            $up_res = $this->order_model->send($id, $express_company_id, $delivery_code, $this->admin_data['username'], $note);
            error_json($up_res);
        }
    }

    /**
     * 确认订单
     */
    public function confirm($id)
    {
        $id = (int)$id;
        if (empty($id)) error_json('订单号错误');
        $note = $this->input->post('note', true);
        $this->load->model('order/order_model');
        $res = $this->order_model->admin_confirm($id, $this->admin_data['username'], '管理员确认', $note);
        error_json($res);
    }

    /**
     * 作废订单
     */
    public function order_cancel($id)
    {
        $id = (int)$id;
        if (empty($id)) error_json('订单号错误');
        $note = $this->input->post('note', true);
        $this->load->model('order/order_model');
        $res = $this->order_model->admin_cancel($id, $this->admin_data['username'], '管理员作废', $note);
        error_json($res);
    }

    /**
     * 订单退款
     */
    public function refund($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $order_data = $this->loop_model->get_id('order', $id);
            if (is_refund($order_data)) {
                $order_data['sku_price']           = format_price($order_data['sku_price']);
                $order_data['sku_price_real']      = format_price($order_data['sku_price_real']);
                $order_data['discount_price']      = format_price($order_data['discount_price']);
                $order_data['delivery_price_real'] = format_price($order_data['delivery_price_real']);
                $order_data['order_price']         = format_price($order_data['order_price']);
                assign('order_data', $order_data);
                // //订单商品
                // $sku_list_data = $this->loop_model->get_list('order_sku', array('where' => array('is_refund' => 0, 'order_id' => $order_data['id'])));
                // foreach ($sku_list_data as $key) {
                //     $key['sku_sell_price_real'] = format_price($key['sku_sell_price_real']);
                //     $sku_list[]                 = $key;
                // }
                //订单商品
                $this->load->model('order/order_model');
                $sku_list = $this->order_model->get_order_sku($id);
                assign('sku_list', $sku_list);
                display('/order/order/refund.html');
            } else {
                echo '不满足退款条件';
            }
        }
    }

    /**
     * 订单退款保存
     */
    public function refund_save()
    {
        $sku_id = (int)$this->input->post('sku_id', true);
        if (!empty($sku_id)) {
            $channel = $this->input->post('channel', true);
            $amount  = price_format($this->input->post('amount', true));
            if ($amount < 0) error_json('退款金额不能少于0');
            $desc           = $this->input->post('desc', true);
            $order_sku_data = $this->loop_model->get_where('order_sku', array('id' => $sku_id));
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
                        'm_id'     => $order_data['m_id'],
                        'amount'   => $amount,
                        'goods_id' => $order_sku_data['goods_id'],
                        'sku_id'   => $order_sku_data['id'],
                        'addtime'  => time(),
                        'note'     => $desc,
                        'shop_id'  => $order_data['shop_id'],
                    );
                    $this->load->model('order/order_model');
                    $doc_id = $this->order_model->refund_add($refund_doc_data, $this->admin_data['username']);
                    if (!empty($doc_id)) {
                        //开始处理退款
                        $this->order_model->refund_confirm($doc_id, $this->admin_data['username'], $desc, $channel);
                        error_json('y');
                    } else {
                        error_json('申请失败');
                    }
                }
            } else {
                error_json('已经确认和未支付的不能退款');
            }
        } else {
            error_json('没有选择退款商品');
        }
    }

    /**
     * 订单打印选择快递模板
     */
    public function print_express_select()
    {
        $order_id = $this->input->get('order_id');
        if (!empty($order_id)) {
            $list = $this->loop_model->get_list('express_company', array(), '', '', 'sortnum asc,id desc');//列表
            assign('list', $list);
            assign('order_id', $order_id);
            display('/order/order/print_express_select.html');
        } else {
            echo '订单号错误';
        }
    }

    /**
     * 订单打印选择快递模板
     */
    public function print_express()
    {
        $express_id = (int)$this->input->get('express_id');
        $order_id   = $this->input->get('order_id');
        $id         = explode(',', $order_id);
        if (!empty($id) && !empty($express_id)) {
            $express_data           = $this->loop_model->get_id('express_company', $express_id);
            $express_data['config'] = trim($express_data['config'], '[');
            $express_data['config'] = rtrim($express_data['config'], ']');
            $where_data             = array(
                'where_in' => array('id' => $id),
            );
            $order_list             = $this->loop_model->get_list('order', $where_data);//列表
            $this->load->model('areas_model');
            $this->load->model('order/order_model');
            foreach ($order_list as $key) {
                $key['order_price']        = format_price($key['order_price']);
                $key['area_name']          = $this->areas_model->get_name(array($key['prov'], $key['city'], $key['area']));//地区信息
                $key['sku_list']           = $this->order_model->get_order_sku($key['id']);
                $shop_address              = $this->loop_model->get_where('member_shop_address', array('shop_id' => $key['shop_id'], 'is_default' => 1));
                $shop_address['area_name'] = $this->areas_model->get_name(array($shop_address['prov'], $shop_address['city'], $shop_address['area']));
                $key['shop_address']       = $shop_address;
                $list[]                    = $key;
            }
            assign('list', $list);
            assign('express_data', $express_data);
        }
        display('/order/order/print_express.html');
    }

    /**
     * 打印配货单
     */
    public function print_distribution()
    {
        $order_id = $this->input->get('order_id');
        $id       = explode(',', $order_id);
        if (!empty($id)) {
            $where_data = array(
                'select'   => 'o.*,d.name as delivery_name',
                'where_in' => array('o.id' => $id),
                'join'     => array(
                    array('delivery as d', 'o.delivery_id=d.id')
                )
            );
            $order_list = $this->loop_model->get_list('order as o', $where_data, '', '', 'o.id desc');//列表
            $this->load->model('areas_model');
            $this->load->model('order/order_model');
            foreach ($order_list as $key) {
                $key['sku_price']           = format_price($key['sku_price']);
                $key['sku_price_real']      = format_price($key['sku_price_real']);
                $key['discount_price']      = format_price($key['discount_price']);
                $key['delivery_price_real'] = format_price($key['delivery_price_real']);
                $key['promotion_price']     = format_price($key['promotion_price']);
                $key['coupons_price']       = format_price($key['coupons_price']);
                $key['order_price']         = format_price($key['order_price']);
                $key['area_name']           = $this->areas_model->get_name(array($key['prov'], $key['city'], $key['area']));//地区信息
                $key['sku_list']            = $this->order_model->get_order_sku($key['id']);
                $list[]                     = $key;
            }
            assign('list', $list);
        }
        display('/order/order/print_distribution.html');
    }
}
