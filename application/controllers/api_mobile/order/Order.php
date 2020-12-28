<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Order extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $this->load->model('loop_model');
    }

    /**
     * 我的订单列表
     * status(null-全部订单，1-代付款，2-待发货，3-待收货，4-待评价，10-退款/售后）
     * */
   public function order_list()
   {
       //自动执行start********************************************
       $m_id     = (int)$this->input->get_post('m_id');
       $where_data['where']['o.m_id'] = $m_id;
       $this->load->model('order/order_model');
       $this->order_model->auto_cancel();//自动取消超时的订单
       $this->order_model->auto_confirm();//自动确认超时的订单
       //自动执行end**********************************************

       $pagesize = 20;//分页大小
       $page     = (int)$this->input->get_post('page');
       $page <= 1 ? $page = 1 : $page = $page;//当前页数
       //搜索条件start
       //是否删除
       $is_del = $this->input->get_post('is_del');
       if ($is_del == 1) {
           $where_data['where']['is_del'] = $is_del;
       } else {
           $where_data['where']['is_del'] = 0;
       }
       //状态
       $status = $this->input->get_post('status');
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
       $payment_status = $this->input->get_post('payment_status');
       if ($payment_status != '') $where_data['where']['payment_status'] = $payment_status;

       //关键字
       $keyword_where = $this->input->get_post('keyword_where');
       $keyword       = $this->input->get_post('keyword');
       if (!empty($keyword_where) && !empty($keyword)) $where_data['where'][$keyword_where] = $keyword;
       $search_where = array(
           'is_del'         => $is_del,
           'status'         => $status,
           'payment_status' => $payment_status,
           'keyword_where'  => $keyword_where,
           'keyword'        => $keyword,
       );
       //assign('search_where', $search_where);
       //搜索条件end
       $where_data['select'] = 'o.id,o.order_no,o.payment_status,o.status,o.sku_price_real,o.addtime,o.paytime,m.nickname,k.name,k.time,';
       $where_data['join']   = array(
           array('user as m', 'o.m_id=m.id'),
           array('goods as k', 'o.good_id=k.id'),
       );
       //查到数据
       $order_list = $this->loop_model->get_list('order as o', $where_data, $pagesize, $pagesize * ($page - 1), 'o.id desc');//列表
       //assign('list', $order_list);
       //开始分页start
       $all_rows = $this->loop_model->get_list_num('order as o', $where_data);//所有数量
       //assign('page_count', ceil($all_rows / $pagesize));
       //开始分页end
       $this->ResArr['code'] = 200;
       $this->ResArr['data'] = [
           'list'=>$order_list,
           'page_count'=> ceil($all_rows / $pagesize)
       ];;
       echo json_encode($this->ResArr);exit;
   }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        $post_data = $this->input->get_post(NULL);
        if (empty($post_data['id'])){
            $this->ResArr['code'] = 15;
            $this->ResArr['msg'] = '订单id不能为空';
            echo json_encode($this->ResArr);exit;
        }
        $order_data = $this->loop_model->get_where('order',array('id'=>$post_data['id']),'id,m_id,good_id,order_no,payment_status,status,sku_price_real,addtime,paytime');
        if (!$order_data){
            $this->ResArr['code'] = 16;
            $this->ResArr['msg'] = '该订单不存在';
            echo json_encode($this->ResArr);exit;
        }
        $user = $this->loop_model->get_where('user',array('id'=>$order_data['m_id']),'nickname');
        $good = $this->loop_model->get_where('goods',array('id'=>$order_data['good_id']),'name');
        $order_data['nickname'] = $user['nickname'];
        $order_data['name'] = $good['name'];
        $this->ResArr['code'] = 200;
        $this->ResArr['data'] = $order_data;
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 订单提交
     */
    public function commit()
    {
        $good_id   = $this->input->get_post('good_id');//选择的商品,
        $m_id   = $this->input->get_post('m_id');//用户,
      
        if (empty($good_id)) {
            $this->ResArr["code"] = 14;
            $this->ResArr["msg"]= '缺失商品id';
            echo json_encode($this->ResArr);exit;
        }
        $payment_id    = 3;//支付方式（微信支付）
        $where['where']['good_id'] = $good_id;
        $where['where']['m_id'] = $m_id;
        $where['where_in']['status'] = [2,3,4,5];
        $orderData = $this->loop_model->get_list('order',$where);
        if(count($orderData) > 0){
            $this->ResArr["code"] = 11;
            $this->ResArr["msg"]= '该商品已购买';
            echo json_encode($this->ResArr);exit;
        }
        $goodData = $this->loop_model->get_where('goods',array('id'=>$good_id,'status'=>0));
        if(!$goodData){
            $this->ResArr["code"] = 12;
            $this->ResArr["msg"]= '该商品已下架';
            echo json_encode($this->ResArr);exit;
        }
        //组合订单数据
        $order_data = array(
            'm_id'                => $this->input->get_post('m_id'),
            'order_no'            => date('YmdHis') . get_rand_num('int', 6),
            'payment_id'          => $payment_id,
            'good_id'             => $good_id,
            'status'              => 1,
            'sku_price'           => price_format($goodData['market_price']),
            'sku_price_real'      => price_format($goodData['sell_price']),
            'addtime'             => time(),
        );
        //订单总价
        $order_data['order_price'] =price_format($goodData['sell_price']);
        if ($order_data['order_price'] <= 0) $order_data['order_price'] = 0;//订单少于0元的时候直接等于0元

        //查看该用户是否绑定其他用户
        $userbind = $this->loop_model->get_where('user_bind',array('bind_id'=>$this->input->get_post('m_id'),'status'=>1));
        if($userbind && time()-$userbind['addtime'] < 180*24*3600){
            //判断绑定是否过期
            $order_data['share_uid']  = $userbind['m_id'];//分享者id
        }else{
            $order_data['share_uid']  = $this->input->get_post('share_uid') ? $this->input->get_post('share_uid') : '';//分享者id
        }

        $this->load->model('order/order_model');
        //添加订单商品
        $res = $this->order_model->add($order_data,'');
        if($res <1){
            $this->ResArr["code"] = 13;
            $this->ResArr["msg"]= '生成订单失败 ';
            echo json_encode($this->ResArr);exit;
        }
        //订单金额为0时，订单自动完成
        if ($order_data['order_price'] == 0) {
            $this->order_model->update_pay_status($order_data['order_no']);
        }
        $all_order_price = $order_data['order_price'];
        $order_no[]      = $order_data['order_no'];

        //是否生成返利订单
        if(isset($order_data['share_uid']) && !empty($order_data['share_uid'])){
            //插入分佣
            $sameorder = $this->loop_model->get_where('order',['m_id'=>$order_data['share_uid'],'good_id'=> $good_id,'payment_status'=>1],'','paytime desc');
            $total = $this->loop_model->get_list_num('order',['where'=>['m_id'=>$order_data['share_uid'],'good_id'=> $good_id,'payment_status'=>1]]);
            if($sameorder && $total<5){
                //返佣20%
                $rakedata['share_order_id'] = $sameorder['id'];
                $rakedata['order_id'] = $res;
                $rakedata['rake_id'] = 0;
                $rakedata['rake_price'] = $sameorder['order_price']*0.2;
                $rakedata['order_price'] = $sameorder['order_price'];
                $rakedata['rate'] = 20;
                $rakedata['addtime'] = time();
                $rakeres = $this->loop_model->insert('order_rake',$rakedata);
            }elseif($sameorder && $total>5){
                //返佣5%
                $dissameorder = $this->loop_model->get_where('order',['m_id'=>$order_data['share_uid'],'payment_status'=>1],'','paytime desc');
                $rakedata['share_order_id'] = $dissameorder['id'];
                $rakedata['order_id'] = $res;
                $rakedata['rake_id'] = 0;
                $rakedata['rake_price'] = $dissameorder['order_price']*0.05;
                $rakedata['order_price'] = $dissameorder['order_price'];
                $rakedata['rate'] = 5;
                $rakedata['addtime'] = time();
                $rakeres = $this->loop_model->insert('order_rake',$rakedata);
            }else{
                $dissameorder = $this->loop_model->get_where('order',['m_id'=>$order_data['share_uid'],'payment_status'=>1],'','paytime desc');
                if($dissameorder){
                    //返佣5%
                    $rakedata['share_order_id'] = $dissameorder['id'];
                    $rakedata['order_id'] = $res;
                    $rakedata['rake_id'] = 0;
                    $rakedata['rake_price'] = $dissameorder['order_price']*0.05;
                    $rakedata['order_price'] = $dissameorder['order_price'];
                    $rakedata['rate'] = 5;
                    $rakedata['addtime'] = time();
                    $rakeres = $this->loop_model->insert('order_rake',$rakedata);
                }
            }

        }
       
        //订单金额为0时，订单自动完成
        if ($all_order_price <= 0) {
            $this->ResArr["code"] = 200;
            $this->ResArr["pay"] = 1;
            $this->ResArr["msg"]= '支付成功';
            echo json_encode($this->ResArr);exit;
        } else {
            $this->ResArr["code"] = 200;
            $this->ResArr["pay"] = 0;
            $this->ResArr["data"] = $order_no;
            $this->ResArr["msg"]= '生成订单请去支付';
            echo json_encode($this->ResArr);exit;
        }
    }
}
