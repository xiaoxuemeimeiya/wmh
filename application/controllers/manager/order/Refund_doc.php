<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Refund_doc extends CI_Controller
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
     * 退款单
     */
    public function index()
    {
        $export   = $this->input->get('export');//是否导出
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        $username = $this->input->post_get('username');
        if (!empty($username)) $where_data['where']['m.username'] = $username;
        $search_where = array(
            'username' => $username,
        );
        assign('search_where', $search_where);
        $where_data['where']['doc.status!='] = 0;
        //搜索条件end
        $where_data['select'] = 'doc.*,o.order_no,o.id as order_id,m.username';
        $where_data['join']   = array(
            array('member as m', 'doc.m_id=m.id'),
            array('order as o', 'doc.order_id=o.id')
        );
        //查到数据
        //查到数据
        if (empty($export)) {
            $list_data = $this->loop_model->get_list('order_refund_doc as doc', $where_data, $pagesize, $pagesize * ($page - 1), 'doc.id desc');//列表
            foreach ($list_data as $key) {
                $key['amount'] = format_price($key['amount']);
                $list[]        = $key;
            }
            assign('list', $list);
            //开始分页start
            $all_rows = $this->loop_model->get_list_num('order_refund_doc as doc', $where_data);//所有数量
            assign('page_count', ceil($all_rows / $pagesize));
            //开始分页end

            display('/order/refund_doc/list.html');
        } else {
            $list = $this->loop_model->get_list('order_refund_doc as doc', $where_data, '', '', 'doc.id desc');//列表
            $this->load->library('PHPExcel');
            $this->load->library('PHPExcel/IOFactory');
            $resultPHPExcel = new PHPExcel();
            $i              = 1;
            $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, '订单号');
            $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, '用户名');
            $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, '金额');
            $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, '时间');
            $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, '处理人');
            $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, '备注');
            foreach ($list as $key) {
                $i++;
                $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, $key['order_no']);
                $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, $key['username']);
                $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, '￥' . format_price($key['amount']));
                $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, date('Y-m-d H:i:s', $key['addtime']));
                $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, $key['admin_user']);
                $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, $key['note']);
            }
            $outputFileName = "退款单.xls";
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
     * 退款申请
     */
    public function pending()
    {
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        $where_data['where'] = array('doc.status' => 0);
        //搜索条件end
        $where_data['select'] = 'doc.*,o.order_no,o.id as order_id,m.username';
        $where_data['join']   = array(
            array('member as m', 'doc.m_id=m.id'),
            array('order as o', 'doc.order_id=o.id')
        );
        //查到数据
        $list_data = $this->loop_model->get_list('order_refund_doc as doc', $where_data, $pagesize, $pagesize * ($page - 1), 'doc.id desc');//列表
        foreach ($list_data as $key) {
            $key['amount'] = format_price($key['amount']);
            $list[]        = $key;
        }
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('order_refund_doc as doc', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end

        display('/order/refund_doc/pending.html');
    }

    /**
     * 订单退款
     */
    public function refund_doc_view($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $order_refund_doc_data = $this->loop_model->get_id('order_refund_doc', $id);
            if ($order_refund_doc_data['status'] == 0) {
                $order_refund_doc_data['amount'] = format_price($order_refund_doc_data['amount']);
                assign('order_refund_doc_data', $order_refund_doc_data);
                $order_data                    = $this->loop_model->get_id('order', $order_refund_doc_data['order_id']);
                $order_data['coupons_price']   = format_price($order_data['coupons_price']);
                $order_data['promotion_price'] = format_price($order_data['promotion_price']);
                assign('order_data', $order_data);
                $order_sku_data                        = $this->loop_model->get_id('order_sku', $order_refund_doc_data['sku_id']);
                $order_sku_data['sku_sell_price_real'] = format_price($order_sku_data['sku_sell_price_real']);
                assign('order_sku_data', $order_sku_data);
                display('/order/refund_doc/refund_doc_view.html');
            } else {
                echo "退款申请已经处理";
            }
        }
    }

    /**
     * 订单退款保存
     */
    public function refund_doc_save()
    {
        $id      = (int)$this->input->post('id', true);
        $amount  = $this->input->post('amount', true);
        $channel = (int)$this->input->post('channel', true);
        $desc    = $this->input->post('desc', true);
        $status  = (int)$this->input->post('status', true);
        if (!empty($id) && $amount >= 0) {
            $this->load->model('order/order_model');
            $res = $this->order_model->refund_edit($id, $amount, $this->admin_data['username'], $channel, $desc, $status);
            error_json($res);
        }
    }

    /**
     * 订单退款流程
     */
    public function refund_doc_log($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $refund_doc_log_data = $this->loop_model->get_list('order_refund_doc_log', array('where' => array('doc_id' => $id)));
            assign('refund_doc_log_data', $refund_doc_log_data);
            display('/order/refund_doc/refund_doc_log.html');
        }
    }
}
