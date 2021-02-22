<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller
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
     * 用户基本信息列表
     */
    public function index()
    {
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
    
        //关键字
        $username = $this->input->post_get('username');
        if (!empty($username)) $where_data['where']['u.nickname'] = $username;
        $search_where = array(
            'username' => $username,
        );
        assign('search_where', $search_where);
        //搜索条件end
       //$where_data['select'] = 'u.id,u.nickname,u.headimgurl,u.balance,u.scribe_time,m.phone,';
        $where_data['select'] = 'u.id,u.openid,u.nickname,u.headimgurl,u.balance,u.scribe_time,m.phone,k.nickname as top_nickname,k.headimgurl as top_headimgurl,f.position,f.name,f.mobile';
        $where_data['join']         = array(
            array('phone as m', 'm.m_id=u.id','left'),
            array('user as k', 'u.top_id=k.id','left'),
            array('card as f', 'u.id=f.m_id','left')
        );
        //查到数据
        $list_data = $this->loop_model->get_list('user as u', $where_data, $pagesize, $pagesize * ($page - 1), 'u.id desc');//列表
        foreach ($list_data as $key) {
            $key['balance'] = format_price($key['balance']);
            $list[]         = $key;
        }
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('user as u', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
   
        display('/member/user/list.html');
    }

    /**
     * 用户基本信息列表（旧）
     */
    public function old_index()
    {
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        $status = $this->input->post_get('status');
        if (isset($status) && $status != '') {
            $where_data['where'] = array('u.status' => $status);
        } else {
            $where_data['where'] = array('u.status!=' => '1');
        }
        //用户组
        $group_id = $this->input->post_get('group_id');
        if ($group_id != '') $where_data['where']['group_id'] = $group_id;
        //关键字
        $username = $this->input->post_get('username');
        if (!empty($username)) $where_data['where']['username'] = $username;
        $search_where = array(
            'status'   => $status,
            'group_id' => $group_id,
            'username' => $username,
        );
        assign('search_where', $search_where);
        //搜索条件end
        $where_data['where']['id>'] = 1;
        $where_data['join']         = array(
            array('member as m', 'u.m_id=m.id')
        );
        //查到数据
        $list_data = $this->loop_model->get_list('member_user as u', $where_data, $pagesize, $pagesize * ($page - 1), 'm.id desc');//列表
        foreach ($list_data as $key) {
            $key['balance'] = format_price($key['balance']);
            $list[]         = $key;
        }
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('member_user as u', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end

        assign('status', array('0' => '正常', 1 => '删除', 2 => '锁定'));//状态
        //会员组
        $user_group_list = $this->loop_model->get_list('member_user_group');
        foreach ($user_group_list as $k) {
            $group_list[$k['id']] = $k['group_name'];
        }
        assign('group_list', $group_list);
        assign('user_group_list', $user_group_list);
        display('/member/user/list.html');
    }

    /**
     * 用户管理
     */
    public function data()
    {
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
    
        //关键字
        $username = $this->input->post_get('username');
        if (!empty($username)) $where_data['where']['u.nickname'] = $username;
        $search_where = array(
            'username' => $username,
        );
        assign('search_where', $search_where);
        //搜索条件end
       // $where_data['select'] = 'u.id,u.nickname,u.headimgurl,u.balance,u.scribe_time,m.phone,';
        $where_data['select'] = 'u.id,u.nickname,u.headimgurl,m.phone';
        $where_data['join']         = array(
            array('phone as m', 'm.m_id=u.id','left')
        );
        //查到数据
        $list_data = $this->loop_model->get_list('user as u', $where_data, $pagesize, $pagesize * ($page - 1), 'u.id desc');//列表
        foreach ($list_data as $key) {
            $key['balance'] = format_price($key['balance']);
            $list[]         = $key;
        }
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('user as u', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
   
        display('/member/user/data.html');
    }

    /**
     * 现金账户流水
     */
    public function account_log($m_id = '')
    {
        $m_id = (int)$m_id;
        if (!empty($m_id)) {
            $pagesize = 20;//分页大小
            $page     = (int)$this->input->get('per_page');
            $page <= 1 ? $page = 1 : $page = $page;//当前页数
            //搜索条件start

            //搜索条件end
            $where_data['where']['m_id'] = $m_id;
            //查到数据
            $list_data = $this->loop_model->get_list('member_user_account_log', $where_data, $pagesize, $pagesize * ($page - 1), 'id desc');//列表
            foreach ($list_data as $key) {
                $key['amount'] = format_price($key['amount']);
                $list[]        = $key;
            }
            assign('list', $list);
            //开始分页start
            $all_rows = $this->loop_model->get_list_num('member_user_account_log', $where_data);//所有数量
            assign('page_count', ceil($all_rows / $pagesize));
            //开始分页end
            assign('m_id', $m_id);
        }
        $this->load->model('member/user_account_log_model');
        assign('event_name', $this->user_account_log_model->get_type_name());
        display('/member/user/account_log.html');
    }

    /**
     * 现金账户充值
     */
    public function account_online_recharge($m_id = '')
    {
        $m_id   = (int)$m_id;
        $amount = price_format($this->input->post('amount'));
        if (!empty($m_id) && !empty($amount)) {
            //开始划入资金
            $this->load->model('member/user_account_log_model');
            $data = array(
                'm_id'       => $m_id,
                'amount'     => $amount,
                'event'      => 1,
                'note'       => '管理员后台直接充值',
                'admin_user' => $this->admin_data['username'],
            );
            $log_id = $this->user_account_log_model->insert($data);
            if ($log_id['status'] == 'y') {
                admin_log_insert('给用户' . $m_id . '充值'. format_price($amount) .'元');
                error_json('y');
            } else {
                error_json('资金转入失败');
            }
        } else {
            error_json('用户id和充值金额不能小于0');
        }
    }

    /**
     * 现金账户扣除
     */
    public function account_withdraw($m_id = '')
    {
        $m_id   = (int)$m_id;
        $amount = price_format($this->input->post('amount'));
        if (!empty($m_id) && !empty($amount)) {
            //开始划入资金
            $this->load->model('member/user_account_log_model');
            $data = array(
                'm_id'       => $m_id,
                'amount'     => $amount,
                'event'      => 7,
                'note'       => '管理员后台直接扣除',
                'admin_user' => $this->admin_data['username'],
            );
            $log_id = $this->user_account_log_model->insert($data);
            if ($log_id['status'] == 'y') {
                admin_log_insert('给用户' . $m_id . '扣除'. format_price($amount) .'元');
                error_json('y');
            } else {
                error_json('资金扣除失败');
            }
        } else {
            error_json('用户id和扣除金额不能小于0');
        }
    }

    /**
     * 积分账户流水
     */
    public function point_log($m_id = '')
    {
        $m_id = (int)$m_id;
        if (!empty($m_id)) {
            $pagesize = 20;//分页大小
            $page     = (int)$this->input->get('per_page');
            $page <= 1 ? $page = 1 : $page = $page;//当前页数
            //搜索条件start

            //搜索条件end
            $where_data['where']['m_id'] = $m_id;
            //查到数据
            $list = $this->loop_model->get_list('member_user_point_log', $where_data, $pagesize, $pagesize * ($page - 1), 'id desc');//列表
            assign('list', $list);
            //开始分页start
            $all_rows = $this->loop_model->get_list_num('member_user_point_log', $where_data);//所有数量
            assign('page_count', ceil($all_rows / $pagesize));
            //开始分页end
            assign('m_id', $m_id);
        }
        $this->load->model('member/user_point_log_model');
        assign('event_name', $this->user_point_log_model->get_type_name());
        display('/member/user/point_log.html');
    }

    /**
     * 积分账户增加
     */
    public function point_online_recharge($m_id = '')
    {
        $m_id   = (int)$m_id;
        $amount = (int)$this->input->post('amount');
        if (!empty($m_id) && !empty($amount)) {
            //开始转入积分
            $this->load->model('member/user_point_log_model');
            $data = array(
                'm_id'       => $m_id,
                'amount'     => $amount,
                'event'      => 4,
                'note'       => '管理员后台直接充值',
                'admin_user' => $this->admin_data['username'],
            );
            $log_id = $this->user_point_log_model->insert($data);
            if ($log_id['status']=='y') {
                admin_log_insert('给用户' . $m_id . '充值'. $amount .'积分');
                error_json('y');
            } else {
                error_json('积分转入失败');
            }
        } else {
            error_json('用户id和充值积分个数不能小于0');
        }
    }

    /**
     * 积分账户扣除
     */
    public function point_withdraw($m_id = '')
    {
        $m_id   = (int)$m_id;
        $amount = (int)$this->input->post('amount');
        if (!empty($m_id) && !empty($amount)) {
            //开始转入积分
            $this->load->model('member/user_point_log_model');
            $data = array(
                'm_id'       => $m_id,
                'amount'     => $amount,
                'event'      => 5,
                'note'       => '管理员后台直接扣除',
                'admin_user' => $this->admin_data['username'],
            );
            $log_id = $this->user_point_log_model->insert($data);
            if ($log_id['status']=='y') {
                admin_log_insert('给用户' . $m_id . '扣除'. $amount .'积分');
                error_json('y');
            } else {
                error_json('积分扣除失败');
            }
        } else {
            error_json('用户id和扣除积分个数不能小于0');
        }
    }

    /**
     * 参团列表
     */
    public function group()
    {
        $m_id = (int)$this->input->get_post('m_id');
        //自动执行start********************************************
       //$m_id     = (int)$this->input->get_post('m_id');
       $this->load->model('order/order_model');
       $this->order_model->auto_cancel();//自动取消超时的订单
       $this->order_model->auto_confirm();//自动确认超时的订单
       //自动执行end**********************************************

       $pagesize = 20;//分页大小
       $page     = (int)$this->input->get_post('per_page');
       $page <= 1 ? $page = 1 : $page = $page;//当前页数
       //搜索条件start
        //是否删除
        $is_del = $this->input->post_get('is_del');
        if ($is_del == 1) {
            $where_data['where']['o.is_del'] = $is_del;
        } else {
            $where_data['where']['o.is_del'] = 0;
        }
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
        //支付状态
        $payment_status = $this->input->post_get('payment_status');
        if ($payment_status != '') $where_data['where']['o.payment_status'] = $payment_status;

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
       $where_data['select'] = 'o.id,o.order_no,o.payment_status,o.status,o.order_price,o.sku_price_real,o.addtime,o.paytime,m.headimgurl,m.nickname,k.name';
       $where_data['join']   = array(
           array('user as m', 'o.m_id=m.id'),
           array('goods as k', 'o.good_id=k.id'),
       );
       //查到数据
       $order_list = $this->loop_model->get_list('order as o', $where_data, $pagesize, $pagesize * ($page - 1), 'o.id desc');//列表
       assign('list', $order_list);
       assign('m_id',$m_id);
       //开始分页start
       $all_rows = $this->loop_model->get_list_num('order as o', $where_data);//所有数量
       assign('page_count', ceil($all_rows / $pagesize));
       display('/member/user/group.html');
    }

    /**
     * 邀请参团列表
     * status(null-全部订单，1-代付款，2-待发货，3-待收货，4-待评价，10-退款/售后）
     */
    public function invite_group($m_id)
    {
      //自动执行start********************************************
      //$m_id     = (int)$this->input->get_post('m_id');
      $this->load->model('order/order_model');
      //$this->order_model->auto_cancel();//自动取消超时的订单
      //$this->order_model->auto_confirm();//自动确认超时的订单
      //自动执行end**********************************************
      $pagesize = 20;//分页大小
      $page     = (int)$this->input->get_post('per_page');
      $page <= 1 ? $page = 1 : $page = $page;//当前页数
      //搜索条件start
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
      //分享者id
      $where_data['where']['o.share_uid'] = $m_id;

      //搜索条件end
      $where_data['select'] = 'g.phone,o.id,o.order_no,o.payment_status,o.status,o.order_price,o.sku_price_real,o.addtime,o.paytime,m.id as m_id,m.nickname,k.name';
      $where_data['join']   = array(
          array('user as m', 'o.m_id=m.id'),
          array('goods as k', 'o.good_id=k.id'),
          array('phone as g', 'g.m_id=m.id'),
      );
      //查到数据
      $order_list = $this->loop_model->get_list('order as o', $where_data, $pagesize, $pagesize * ($page - 1), 'o.id desc');//列表
      assign('list', $order_list);
      //开始分页start
      $all_rows = $this->loop_model->get_list_num('order as o', $where_data);//所有数量
      assign('page_count', ceil($all_rows / $pagesize));
      display('/member/user/invite_group.html');
    }

    /**
     * 邀请列表(邀请好友领卡列表)
     * status(0-待激活，1-已激活，2-激活失效，3-已过期）
     */
    public function invite($m_id)
    {
        $getData =  $this->input->post();
        $pagesize = 20;//分页大小
        $page     = (int)$getData['per_page'];
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
    
        $status = $this->input->post_get('status');
        if ($status != '') $where_data['where']['a.status'] = $status;

        //$m_id = $getData['m_id'];
        $where_data['select'] = 'a.id,a.name,a.position,IFNULL(a.mobile,c.phone) as mobile,a.username,a.password,a.sendtime,a.endtime,a.status,a.addtime,b.nickname,b.headimgurl';
        $where_data['join']   = array(
            array('card a', 'a.m_id=b.id','left'),
            array('phone c', 'c.m_id=b.id','left'),
        );
        $where_data['where']['b.top_id'] = $m_id;
        $card = $this->loop_model->get_list('user b',$where_data, $pagesize, $pagesize * ($page - 1), 'a.id desc');
        assign('list', $card);
        $all_rows = $this->loop_model->get_list_num('user b', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        display('/member/user/invite.html');
    }

    /**
     * 返佣列表
     */
    public function prize($m_id)
    {
         //自动执行start********************************************
         //$m_id     = (int)$this->input->get_post('m_id');
         $this->load->model('order/order_model');
         $this->order_model->auto_cancel();//自动取消超时的订单
         $this->order_model->auto_confirm();//自动确认超时的订单
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
         $where_data['select'] = 'o.id,o.order_no,o.payment_status,o.status,o.addtime,o.paytime,m.nickname,m.headimgurl,k.name,f.rate,f.rake_id,convert(f.order_price/10000,decimal(10,2)) as order_price,convert(f.rake_price/10000,decimal(10,2)) as rake_price';
         $where_data['join']   = array(
             array('user as m', 'o.m_id=m.id'),
             array('goods as k', 'o.good_id=k.id'),
             array('order_rake as f', 'o.id=f.order_id'),
         );
         //查到数据
         $order_list = $this->loop_model->get_list('order as o', $where_data, $pagesize, $pagesize * ($page - 1), 'o.id desc');//列表
         assign('list', $order_list);
         //开始分页start
         $all_rows = $this->loop_model->get_list_num('order as o', $where_data);//所有数量
         assign('page_count', ceil($all_rows / $pagesize));
         display('/member/user/prize.html');
    }

    /**
     * 邀请信息编辑
     */
    public function invite_edit($id)
    {
        $select_data = 'id,name,m_id,position,mobile,username,password,sendtime,endtime,status,addtime';
        $where['id'] = $id;
        $card = $this->loop_model->get_where('card',$where,$select_data);
        $user = $this->loop_model->get_where('user',['id'=>$card['m_id']],'nickname,headimgurl');
        assign('card', $card);
        assign('user', $user);
        display('/member/user/invite_edit.html');
    }

    /**
     * 邀请信息保存
     */
    public function invite_save(){
        if (is_post()) {
            $data_post           = $this->input->post(NULL, true);
            $update_data['username'] = $data_post['username'];
            $update_data['password'] = $data_post['password'];
            $update_data['status'] = $data_post['status'];
            $card = $this->loop_model->get_id('card',$data_post['id']);
            if($update_data['status'] == 1 && $card['status'] != 1){
                //发卡了
                $update_data['sendtime'] = time();
            }
            //修改数据
            $res = $this->loop_model->update_id('card', $update_data, $data_post['id']);
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }
    }

     /**
     * 邀请参团信息编辑
     */
    public function invite_group_edit($id)
    {
        $where['id'] = $id;
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get_post('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        $select_data['select'] = 'a.id,a.name,a.position,a.mobile,a.username,a.password,a.sendtime,a.endtime,a.status,a.addtime,b.nickname,b.headimgurl';
        $card = $this->loop_model->get_where('card',$where);
        $where_data['join']   = array(
            array('user b', 'a.m_id=b.id'),
        );
        $where_data['where']['b.top_id'] = $id;
        $card = $this->loop_model->get_list('card a',$where_data, $pagesize, $pagesize * ($page - 1), 'a.id desc');
        assign('list', $card);
        display('/member/user/invite.html');
    }

    /**
     * 返佣信息修改
     */
    public function prize_edit($id)
    {
        $where['id'] = $id;
        $detail = $this->loop_model->get_where('order',$where);
        $goods = $this->loop_model->get_where('goods',$where,'name');
        $rate = $this->loop_model->get_where('order_rake',['order_id'=>$detail['id']]);
        assign('detail',$detail);
        assign('goods',$goods);
        assign('rate',$rate);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/member/user/prize_edit.html');
    }

    /**
     * 返佣信息保存
     */
    public function prize_save()
    {
        if (is_post()) {
            $data_post           = $this->input->post(NULL, true);
            $update_data['rake_id'] = $data_post['rake_id'];
            if(!$data_post['id'] ){
                error_json('暂无该订单');
            }
            //修改数据
            $this->db->trans_start();
            $res = $this->loop_model->update_where('order', $update_data, ['id'=>$data_post['id']]);
            if ($res >=0 ) {
                $update_data['rakeimg'] = $data_post['rakeimg'];
                if($update_data['rake_id'] == 1 && $res > 0){
                    $update_data['ratetime'] = time();
                }
                $res1 = $this->loop_model->update_where('order_rake', $update_data, ['order_id'=>$data_post['id']]);
                if ($res >=0 ) {
                    $this->db->trans_commit();
                    error_json('y');
                }else{
                    $this->db->trans_rollback();
                    error_json('保存失败');
                }

            } else {
                $this->db->trans_rollback();
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }
    }

    //返佣列表
    public function rate(){
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        $rake_id = $this->input->post_get('rake_id');
        if (isset($rake_id) && $rake_id != '') {
            $where_data['where']['u.rake_id'] = $rake_id;
        }
        $payment_status = $this->input->post_get('payment_status');
        if (isset($payment_status) && $payment_status != '') {
            $where_data['where']['u.payment_status'] = $payment_status;
        }
        //搜索条件starttime,endtime
        $starttime = $this->input->post_get('starttime');
        $endtime = $this->input->post_get('endtime');
        if (isset($starttime) && $starttime != '') {
            $where_data['where']['u.paytime >='] = strtotime($starttime);
        }
        if (isset($endtime) && $endtime != '') {
            $where_data['where']['u.paytime <='] = strtotime($endtime);
        }

        //关键字
        $keyword_where = $this->input->post_get('keyword_where');
        $keyword       = $this->input->post_get('keyword');
        if (!empty($keyword_where) && !empty($keyword)) $where_data['like'][$keyword_where] = $keyword;

        $where_data['where']['u.share_uid >'] = 0;
        $search_where = array(
            'rake_id'   => $rake_id,
            'payment_status'   => $payment_status,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'keyword_where'  => $keyword_where,
            'keyword'        => $keyword,
        );
        assign('search_where', $search_where);
        //搜索条件end
        $where_data['join']         = array(
            array('user as a', 'u.share_uid=a.id'),
            array('order_rake as k', 'k.order_id=u.id'),
            //array('order as o', 'k.order_id=o.id'),
            array('user as b', 'u.m_id=b.id')
        );
        $where_data['select'] = 'u.*,a.nickname as top_nickname,a.headimgurl as top_headimgurl,k.rake_price,k.order_price,b.nickname,b.headimgurl';
        //查到数据
        //$list_data = $this->loop_model->get_list('cash as u', $where_data, $pagesize, $pagesize * ($page - 1), 'a.id desc','');//列表
        $list_data = $this->loop_model->get_list('order as u', $where_data, $pagesize, $pagesize * ($page - 1), 'a.id desc','');//列表
        assign('list', $list_data);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('order as u', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        display('/member/user/rate.html');
    }
/*
    public function day_rate(){
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        $status = $this->input->post_get('type');
        if (isset($status) && $status != '') {
            $where_data['where'] = array('u.type' => $status);
        }
        //搜索条件starttime,endtime
        $starttime = $this->input->post_get('starttime');
        $endtime = $this->input->post_get('endtime');
        if (isset($starttime) && $starttime != '') {
            $where_data['where'] = array('u.addtime >=' => $starttime);
        }
        if (isset($endtime) && $endtime != '') {
            $where_data['where'] = array('u.addtime <=' => $endtime);
        }

        $username = $this->input->post_get('a.username');
        if (!empty($username)) $where_data['where']['a.username'] = $username;
        $search_where = array(
            'status'   => $status,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'username' => $username,
        );
        assign('search_where', $search_where);
        //搜索条件end

        $where_data['join']         = array(
            array('user as a', 'u.m_id=a.id'),
            array('order_rake as k', 'k.order_id=u.order_id'),
            array('order as o', 'k.order_id=o.id'),
        );
        $where_data['select'] = 'sum(k.rake_price) as rake_price,sum(k.order_price) as order_price,a.nickname as top_nickname,a.headimgurl as top_headimgurl,u.*,o.payment_status';
        //查到数据
        $list_data = $this->loop_model->get_group_list('cash as u', $where_data, $pagesize, $pagesize * ($page - 1), 'a.id desc','u.m_id,u.date');//列表
        assign('list', $list_data);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('cash as u', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        display('/member/user/day_rate.html');
    }
*/
    public function day_rate(){
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        $status = $this->input->post_get('state');
        if (isset($status) && $status != '') {
            $where_data['where'] = array('u.state' => $status);
        }
        //搜索条件starttime,endtime
        $time = $this->input->post_get('time');
        if (isset($starttime) && $starttime != '') {
            $where_data['where'] = array('u.date' => $time);
        }

        $username = $this->input->post_get('a.username');
        if (!empty($username)) $where_data['where']['a.username'] = $username;
        $search_where = array(
            'status'   => $status,
            'time' => $time,
            'username' => $username,
        );
        assign('search_where', $search_where);
        //搜索条件end
        $where_data['where'] = array('u.type' => 2);

        $where_data['join']         = array(
            array('user as a', 'u.m_id=a.id'),
        );
        $where_data['select'] = 'a.nickname as nickname,a.headimgurl as headimgurl,u.*';
        //查到数据
/*
        $list_data = $this->loop_model->get_group_list('cash as u', $where_data, $pagesize, $pagesize * ($page - 1), 'a.id desc','u.m_id,u.date');//列表
        foreach($list_data as $k=>$v){
            $where_detail['where'] = array('cash_id'=>$v['id']);
            $detail = $this->loop_model->get_list('cash_log',$where_detail,1,0,'id desc');//未支付订单
            $v['note'] = $detail[0]['note'];
            $v['log_id'] = $detail[0]['id'];
            $list_data[$k] = $v;
        }
*/
        $list_date = $this->loop_model->get_group_list('cash', '', '', '', 'id asc','date');//列表
        assign('list_date', $list_date);
        //assign('list', $list_data);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('cash as u', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        display('/member/user/day_rate.html');
    }


    //批量返现(按订单返现)
    public function reback_batch_order(){
        $this->load->helpers('wechat_helper');
        $id = $this->input->post('id', true);
        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');
        foreach($id as $v){
            //查看是否满足未返现
            $orderDetail = $this->loop_model->get_where('order',array('id'=>$v,'payment_status'=>1));//该订单已支付
            if(!$orderDetail){
                //记录
                cash_log_insert('该返现不存在活不满足条件',$v,1);
            }
            //查看该用户推广者是否存在
            $user = $this->loop_model->get_where('user',array('id'=>$orderDetail['share_uid']));
            if(!$user){
                //记录
                cash_log_insert('该用户不存在',$v,1);//该推广者不存在
            }
            $openid = $user['openid'];
            //$openid = '';
            //$update_data['rake_id'] = 1;//已返佣
            //$res = $this->loop_model->update_where('order', $update_data, ['id'=>$v['id']]);
            $cash = $this->loop_model->get_where('order_rake', ['order_id'=>$orderDetail['id'],'state'=>1,'rake_id'=>0]);
            $partner_trade_no = time().getRandChar(18);
            $input = new \WxPayBizCash();
            $input->SetPartner_trade_no($partner_trade_no);
            $input->SetDesc('cash');
            $input->SetAmount($cash['rake_price']/100);
            //$input->SetAmount(101);
            $input->SetCheck_name('NO_CHECK');
            $input->SetOpenid($openid);
            $config = new \WxPayConfig();
            $order = \WxPayApi::transfers($config,$input);lyLog(var_export($order,true) , "cash" , true);
            //获取该审核订单
            $cash = $this->loop_model->get_where('cash',array('m_id'=>$orderDetail['share_uid'],'order_id'=>$orderDetail['id']));
            $UpdataWhere['m_id'] = $orderDetail['share_uid'];
            $UpdataWhere['order_id'] = $orderDetail['id'];
            if($order["return_code"]=="SUCCESS" && $order['result_code']=='SUCCESS'){
                $updateData['state'] = 1;//状态改为审核通过,已打现
                $updateData['cashtime'] = time();
                $res = $this->loop_model->update_where('cash', $updateData, $UpdataWhere);
                $OrderRakeWhere['order_id'] = $orderDetail['id'];
                $res1 = $this->loop_model->update_where('order_rake', ['rake_id'=>1,'ratetime'=>time()],$OrderRakeWhere );
                $res1 = $this->loop_model->update_where('order', ['rake_id'=>1],['id'=>$orderDetail['id']] );
                lyLog(var_export($res,true) , "res" , true);
                if($res){
                    cash_log_insert('提现成功，记录成功',$cash['id'],0);
                }else{
                    cash_log_insert('提现成功，记录失败',$cash['id'],0);
                }
                //error_json($order['err_code_des']);exit;
            }else if(($order['return_code']=='FAIL') || ($order['result_code']=='FAIL')){lyLog(var_export(555,true) , "res" , true);
                //打款失败
                $reason = (empty($order['err_code_des'])?$order['return_msg']:$order['err_code_des']);
                cash_log_insert($reason ,$cash['id'],1);
                $updateData['cashtime'] = time();
                $updateData['state'] = 1;//状态改为审核失败，提现失败
                $res = $this->loop_model->update_where('cash', $updateData, $UpdataWhere);
                //error_json($reason);exit;
            }else{lyLog(var_export(444,true) , "res" , true);
                //error_json('pay data error!');exit;
                $updateData['cashtime'] = time();
                $updateData['state'] = 1;//状态改为审核失败,提现失败
                $res = $this->loop_model->update_where('cash', $updateData,$UpdataWhere);
                cash_log_insert('提现失败，pay data error!' ,$cash['id'],1);
            }
        }
        error_json('y');exit;
    }


    //批量返现
    public function reback_batch(){
        $this->load->helpers('wechat_helper');
        $id = $this->input->post('id', true);var_dump($id);exit;
        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');
        foreach($id as $v){
            //查看是否满足未返现
            $cash = $this->loop_model->get_where('cash',array('id'=>$v,'state'=>0));//未支付订单
            if(!$cash){
                //记录
                cash_log_insert('该返现不存在活不满足条件',$v,1);
            }
            //查看该用户是否存在
            $user = $this->loop_model->get_where('user',array('id'=>$cash['m_id']));
            if(!$user){
                //记录
                cash_log_insert('该用户不存在',$v,1);
            }
            $openid = $user['openid'];
            //$openid = '';
            //$update_data['rake_id'] = 1;//已返佣
            //$res = $this->loop_model->update_where('order', $update_data, ['id'=>$v['id']]);
            $partner_trade_no = time().getRandChar(18);
            $input = new \WxPayBizCash();
            $input->SetPartner_trade_no($partner_trade_no);
            $input->SetDesc('cash');
            $input->SetAmount($cash['cash']/100);
            //$input->SetAmount(101);
            $input->SetCheck_name('NO_CHECK');
            $input->SetOpenid($openid);
            $config = new \WxPayConfig();
            $order = \WxPayApi::transfers($config,$input);lyLog(var_export($order,true) , "cash" , true);
            if($order["return_code"]=="SUCCESS" && $order['result_code']=='SUCCESS'){
                $UpdataWhere['id'] = $v;
                $updateData['state'] = 1;//状态改为审核通过,已打现
                $updateData['cashtime'] = time();
                $res = $this->loop_model->update_where('cash', $updateData, ['id'=>$v]);
                lyLog(var_export($res,true) , "res" , true);
                if($res){
                    cash_log_insert('提现成功，记录成功',$v,0);
                }else{
                    cash_log_insert('提现成功，记录失败',$v,0);
                }
                //error_json($order['err_code_des']);exit;
            }else if(($order['return_code']=='FAIL') || ($order['result_code']=='FAIL')){lyLog(var_export(555,true) , "res" , true);
                //打款失败
                $reason = (empty($order['err_code_des'])?$order['return_msg']:$order['err_code_des']);
                cash_log_insert($reason ,$v,1);
                $updateData['cashtime'] = time();
                $res = $this->loop_model->update_where('cash', $updateData, ['id'=>$v]);
                //error_json($reason);exit;
            }else{lyLog(var_export(444,true) , "res" , true);
                //error_json('pay data error!');exit;
                $updateData['cashtime'] = time();
                $res = $this->loop_model->update_where('cash', $updateData, ['id'=>$v]);
                cash_log_insert('提现失败，pay data error!' ,$v,1);
            }
        }
        error_json('y');exit;
    }

    //审核提现
    public function ajaxcheck(){
        $postData = $this->request->post();
        if(!isset($postData['type']) || empty($postData)){
            $this->ResArr["code"] = "3";
            $this->ResArr["msg"] = "参数缺失！";
        }
        if($postData['type'] == 2){
            //审核成功
            $datainfo =  Db::table('add_cash')->where('id',$postData['id'])->find();
            if(!$datainfo){
                $this->ResArr["code"] = "3";
                $this->ResArr["msg"] = "该提现记录不存在！";
                return json($this->ResArr);
            }
            if($datainfo["cash"] <= 0){
                $this->ResArr["code"] = "3";
                $this->ResArr["msg"] = "提现金额错误！";
                return json($this->ResArr);
            }
            $userinfo = WeUser::where('id',$datainfo['uid'])->find();
            if(!$userinfo){
                $this->ResArr["code"] = "3";
                $this->ResArr["msg"] = "用户不存在！";
                return json($this->ResArr);
            }
            $openid = $userinfo['openid'];
            //$total_fee = $info['cash']*100;
            $total_fee = $datainfo["cash"]*100;
            require_once Env::get('ROOT_PATH').'extend/wepay3.0.9/WxPay.Api.php';
            require_once Env::get('ROOT_PATH').'extend/wepay3.0.9/WxPay.JsApiPay.php';
            require_once Env::get('ROOT_PATH').'extend/wepay3.0.9/WxPay.Config.php';
            $partner_trade_no = time().getRandChar(18);
            $input = new \WxPayBizCash();
            $input->SetPartner_trade_no($partner_trade_no);
            $input->SetDesc('cash');
            $input->SetAmount($total_fee);
            $input->SetCheck_name('NO_CHECK');
            $input->SetOpenid($openid);
            $config = new \WxPayConfig();
            $order = \WxPayApi::transfers($config,$input);
            if($order["return_code"]=="SUCCESS" && $order['result_code']=='SUCCESS'){
                lyLog(var_export($order,true) , "refund" , true);
                $UpdataWhere['id'] = $postData['id'];
                $updateData['status'] = 2;//状态改为审核通过
                $res = Db::table('add_cash')->where($UpdataWhere)->update($updateData);
            }else if(($order['return_code']=='FAIL') || ($order['result_code']=='FAIL')){
                //退款失败
                //原因
                $reason = (empty($order['err_code_des'])?$order['return_msg']:$order['err_code_des']);
                $this->ResArr['code'] = "2";
                $this->ResArr['msg'] = $reason;
            }
            else{
                $this->ResArr['code'] = "2";
                $this->ResArr['msg'] = "pay data error!";
            }
        }elseif($postData['type'] == 3){
            //审核失败
            $res = AddCash::where('id',$postData['id'])->update(['status'=>$postData['type']]);
            if($res){
                $this->ResArr["code"] = "0";
                $this->ResArr["msg"] = "提交成功！";
            }else{
                $this->ResArr["code"] = "3";
                $this->ResArr["msg"] = "提交失败！";
            }
        }else{
            $this->ResArr["code"] = "3";
            $this->ResArr["msg"] = "选择方式错误！";
        }

        return json($this->ResArr);
    }

}
