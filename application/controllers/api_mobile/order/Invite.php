<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Invite extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }


    /**
     * 我的邀请好友领卡列表
     * status(1-未激活，2-已激活，3-已失效）
     * */
    public function get_card(){
        $getData =  $this->input->post();
        $pagesize = 20;//分页大小
        $page     = (int)$getData['per_page'];
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        $type = $this->input->post_get('type');
        if ($type != '') $where_data['where']['b.status'] = $type;

        $m_id = $getData['m_id'];
        $where_data['select'] = 'a.name,a.position,IFNULL(a.mobile,c.phone) as mobile,a.status,a.addtime,a.sendtime as verytime,d.nickname,d.headimgurl,c.phone,b.status as type,b.addtime as bindtime';
        $where_data['join']   = array(
            array('card a', 'a.m_id=b.bind_id','left'),
            array('phone c', 'c.m_id=b.bind_id','left'),
            array('user d', 'd.id=b.bind_id','left'),
        );
        $where_data['where']['b.m_id'] = $m_id;
        $card = $this->loop_model->get_list('user_bind b',$where_data, $pagesize, $pagesize * ($page - 1), 'a.id desc');
        $all_rows = $this->loop_model->get_list_num('user_bind b', $where_data);//所有数量

        $this->ResArr["code"] = 200;
        $this->ResArr['data'] = [
            'list'=>$card,
            'page_count'=> ceil($all_rows / $pagesize)
        ];
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 我的参团信息列表
     * status(null-全部订单，1-代付款，2-待发货，3-待收货，4-待评价，10-退款/售后）
     * */
   public function order_list()
   {
       //自动执行start********************************************
       $m_id     = (int)$this->input->get_post('m_id');
       $this->load->model('order/order_model');
       $this->order_model->auto_cancel();//自动取消超时的订单
       $this->order_model->auto_confirm();//自动确认超时的订单
       //自动执行end**********************************************

       $pagesize = 20;//分页大小
       $page     = (int)$this->input->get_post('per_page');
       $page <= 1 ? $page = 1 : $page = $page;//当前页数
       //搜索条件start
       //状态
       $status = $this->input->get_post('status');
       if ($status == 1) {
           //待支付的
           $where_data['where']['o.status']      = 1;
           $where_data['where']['o.payment_id>'] = 1;
       } elseif ($status == 2) {
           //待发货的
           $where_data['sql'] = '((o.payment_id=1 and o.status=1) or (o.status=2))';
       } elseif ($status != '') {
           $where_data['where']['o.status'] = $status;
       }
       //用户id
       $where_data['where']['o.m_id'] = $m_id;

       //搜索条件end
       $where_data['select'] = 'o.id,o.good_id,o.order_no,o.payment_status,o.status,convert(o.order_price/10000,decimal(10,2)) as order_price,convert(o.sku_price_real/10000,decimal(10,2)) as sku_price_real,o.addtime,o.paytime,m.nickname,k.name,k.time';
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
       ];
       echo json_encode($this->ResArr);exit;
   }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        $post_data = $this->input->get_post(NULL);
        if (empty($post_data['id'])){
            $this->ResArr['code'] = 200;
            $this->ResArr['msg'] = '订单id不能为空';
            echo json_encode($this->ResArr);exit;
        }
        $order_data = $this->loop_model->get_where('order',array('id'=>$post_data['id']),'id,m_id,good_id,order_no,payment_status,status,convert(order_price/10000,decimal(10,2)) as order_price,convert(sku_price_real/10000,decimal(10,2)) as sku_price_real,,addtime,paytime');
        if (!$order_data){
            $this->ResArr['code'] = 200;
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
     * 邀请参团信息
     * status(null-全部订单，1-代付款，2-待发货，3-待收货，4-待评价，10-退款/售后）
     */
    public function invite_group()
    {
        //自动执行start********************************************
        $m_id     = (int)$this->input->get_post('m_id');
        $this->load->model('order/order_model');
        $this->order_model->auto_cancel();//自动取消超时的订单
        $this->order_model->auto_confirm();//自动确认超时的订单
        //自动执行end**********************************************

        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get_post('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        //状态
        /*
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
        */
        $where_data['where_in']['o.status'] = [2,3,4,5];
        //分享者id
        $where_data['where']['o.share_uid'] = $m_id;

        //搜索条件end
        $where_data['select'] = 'g.phone,o.id,o.order_no,o.payment_status,o.status,convert(o.order_price/10000,decimal(10,2)) as order_price,convert(o.sku_price_real/10000,decimal(10,2)) as sku_price_real,o.addtime,o.paytime,m.nickname,k.name';
        $where_data['join']   = array(
            array('user as m', 'o.m_id=m.id'),
            array('goods as k', 'o.good_id=k.id'),
            array('phone as g', 'g.m_id=m.id'),
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
        ];
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 返佣信息
     */
    public function  rake_back()
    {
        //自动执行start********************************************
        $m_id     = (int)$this->input->get_post('m_id');
        //$this->load->model('order/order_model');
        //$this->order_model->auto_cancel();//自动取消超时的订单
        //$this->order_model->auto_confirm();//自动确认超时的订单
        //自动执行end**********************************************

        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get_post('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        //状态
        $where_data['where']['o.payment_status'] = 1;//已经支付
        $where_data['where_in']['o.status'] = [2,3,4,5];
        //分享者id
        $where_data['where']['o.share_uid'] = $m_id;

        //搜索条件end
        $where_data['select'] = 'o.id,o.order_no,o.payment_status,o.status,o.addtime,o.paytime,m.nickname,k.name,f.rake_id,convert(f.order_price/10000,decimal(10,2)) as order_price,convert(f.rake_price/10000,decimal(10,2)) as rake_price,f.rate,f.ratetime';
        $where_data['join']   = array(
            array('user as m', 'o.m_id=m.id'),
            array('goods as k', 'o.good_id=k.id'),
            array('order_rake as f', 'o.id=f.order_id'),
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
        ];
        echo json_encode($this->ResArr);exit;

    }

}
