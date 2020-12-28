<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 新版首页
     */
    public function good(){
        $where_data['select'] = 'id,name,image,reid';
        $where_data['where']['reid'] = 1;//免费
        $this->load->model('goods/goods_model');
        $list_data            = $this->loop_model->get_list('goods_category', $where_data, '', '', 'sortnum asc,id asc');
        foreach($list_data as $k=>$v){
            $search_where['cat_id'] = $v['id'];
            $list = $this->goods_model->search($search_where, '');
            $list_data[$k]= array_merge($v,$list);
        }
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $list_data;
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 广告拿出来
     */
    public function adv()
    {
        $position_id  = $this->input->get_post('position_id', true) ? $this->input->get_post('position_id', true) : 1;//默认首页轮播图
        $where['where']['position_id'] = $position_id;
        $list = $this->loop_model->get_list('adv', $where);
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $list;
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 首页
     */
    public function index()
    {
        $cat_id  = $this->input->get_post('cat_id', true);
        $page = $this->input->get_post('page', true) ? $this->input->get_post('page', true) : 1;
        if (empty($cat_id)) {
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = "参数缺失cat_id";
            echo json_encode($this->ResArr);exit;
        }
        //搜索条件
        $search_where = array(
            'cat_id'       => $this->input->get_post('cat_id', true),
            //'min_price'    => $this->input->get_post('min_price', true),
            //'max_price'    => $this->input->get_post('max_price', true),
            //'limit'        => (int)$this->input->get_post('limit', true),//显示数量
        );

        //属性条件
        $attr = $this->input->get_post('attr', true);
        if (!empty($attr)) {
            foreach ($attr as $v => $k) {
                $search_where['attr'][$v] = $k;
            }
        }
        $search_where['page'] = $page;
        //查询数据
        $this->load->model('goods/goods_model');
        $res_data = $this->goods_model->search($search_where, '');
     
        $this->ResArr["code"] = 200;
        $this->ResArr["data"]['goods'] = $res_data;
        echo json_encode($this->ResArr);exit;
    }

     /**
     * 拼单
     */
    public function group()
    {
        $reid = 2;//拼tunan
        $page = $this->input->get_post('page', true) ? $this->input->get_post('page', true) : 1;
        $where_data['where']['reid'] = $reid;
        $where_data['or_where']['id'] = $reid;
        $where_data['select'] = 'id';
        $cat_array= $this->loop_model->get_list('goods_category', $where_data, '', '', 'sortnum asc,id asc');
        $cat_ids = array_column($cat_array,'id');
        //搜索条件
        $search_where = array(
            'cat_id'       => $cat_ids,
            //'min_price'    => $this->input->get_post('min_price', true),
            //'max_price'    => $this->input->get_post('max_price', true),
            //'limit'        => (int)$this->input->get_post('limit', true),//显示数量
        );

        //属性条件
        $attr = $this->input->get_post('attr', true);
        if (!empty($attr)) {
            foreach ($attr as $v => $k) {
                $search_where['attr'][$v] = $k;
            }
        }
        $search_where['page'] = $page;
        //查询数据
        $this->load->model('goods/goods_model');
        $res_data = $this->goods_model->search($search_where, '');
        $this->ResArr["code"] = 200;
        $this->ResArr["data"]['goods'] = $res_data;
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 商品详情
     */
    public function detail()
    {
        $id = $this->input->get_post('id', true);
        if(!$id){
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = "参数缺失";
            echo json_encode($this->ResArr);exit;
        }
        $this->load->model('goods/goods_model');
        $item = $this->goods_model->get_detail($id);
        if(!$item){
            $this->ResArr["code"] =17;
            $this->ResArr["msg"] = "商品不存在或者已下架";
            echo json_encode($this->ResArr);exit;
        }
        //查看用户是否购买过此商品
        $item['is_buy'] = 0;
        $m_id = $this->input->get_post('m_id', true);
        if($m_id){
            $where_data['where']['good_id'] = $id;
            $where_data['where']['m_id'] = $m_id;
            $where_data['where']['payment_status'] = 1;//已经支付
            $where_data['where_in']['status'] = [2,3,4,5];//没有退款
            $order = $this->loop_model->get_list('order', $where_data, '', '', 'id asc');
            if($order){
                $item['is_buy'] = 1;
            }
        }

        $this->ResArr["code"] = 200;
        $this->ResArr["data"]= $item;
        echo json_encode($this->ResArr);exit;
    }


    /**
     * 轮播图
     * $position_id（1-手机版首页banner，2-pc版首页banner，3-pc版首页banner下）
     */
    public function ad()
    {
        $position_id= (int)$this->input->get('position_id');
        if (empty($position_id)) {
            $position_id = 1;   //默认手机版首页banner
        }
        $this->load->model('loop_model');
        $position_data = $this->loop_model->get_id('adv_position', $position_id);
        $adv_html      = '';
        if ($position_data['status'] == '0') {
            //单个的
            if ($position_data['play_type'] == 1) {
                $adv_data = $this->loop_model->get_where('adv', array('position_id' => $position_id, 'start_time<=' => time(), 'end_time>=' => time()));
            } elseif ($position_data['play_type'] == 2) {
                //列表
                $adv_data = $this->loop_model->get_list('adv', array('where' => array('position_id' => $position_id, 'start_time<=' => time(), 'end_time>=' => time())), '', '', 'sortnum asc,id desc');
            } elseif ($position_data['play_type'] == 3) {
                //随机
                $adv_data = $this->loop_model->get_where('adv', array('position_id' => $position_id, 'start_time<=' => time(), 'end_time>=' => time()), '*', 'rand()');
            }
            error_json($adv_data);
            //return $adv_data;
        }

    }

}
