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
       // $where_data['select'] = 'u.id,u.nickname,u.headimgurl,u.balance,u.scribe_time,m.phone,';
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
         $where_data['select'] = 'o.id,o.order_no,o.payment_status,o.status,o.addtime,o.paytime,m.nickname,k.name,f.rate,f.rake_id,convert(f.order_price/10000,decimal(10,2)) as order_price,convert(f.rake_price/10000,decimal(10,2)) as rake_price';
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
        $select_data['select'] = 'a.id,a.name,a.position,a.mobile,a.username,a.password,a.sendtime,a.endtime,a.status,a.addtime,b.nickname,b.headimgurl';
        $card = $this->loop_model->get_where('card',$where);
        $where_data['join']   = array(
            array('user b', 'a.m_id=b.id'),
        );
        $where_data['where']['b.top_id'] = $m_id;
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
}
