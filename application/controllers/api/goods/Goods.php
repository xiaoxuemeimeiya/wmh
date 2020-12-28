<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Goods extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
        $this->load->helpers('web_helper');
    }

    /**
     * 列表
     */
    public function goods_list()
    {
        $cat_id  = $this->input->get_post('cat_id', true);
        $keyword = $this->input->get_post('keyword', true);
        if (empty($cat_id) && empty($keyword)) {
            error_json('分类或关键字有一项必填');
        }
        $orderby      = $this->input->get_post('orderby', true);
        $orderby_type = $this->input->get_post('orderby_type', true);
        $screening    = (int)$this->input->get_post('screening', true);//是否需要筛选
        //搜索条件
        $search_where = array(
            'cat_id'       => $this->input->get_post('cat_id', true),
            'brand_id'     => $this->input->get_post('brand_id', true),
            'shop_id'      => $this->input->get_post('shop_id', true),
            'keyword'      => $this->input->get_post('keyword', true),
            'min_price'    => $this->input->get_post('min_price', true),
            'max_price'    => $this->input->get_post('max_price', true),
            'orderby'      => $orderby!='' ? $orderby :  config_item('goods_list_orderby'),
            'orderby_type' => $orderby_type!='' ? $orderby_type : config_item('goods_list_orderby_type'),
            'limit'        => (int)$this->input->get_post('limit', true),//显示数量
        );

        //属性条件
        $attr = $this->input->get_post('attr', true);
        if (!empty($attr)) {
            foreach ($attr as $v => $k) {
                $search_where['attr'][$v] = $k;
            }
        }
        //查询数据
        $this->load->model('goods/goods_model');
        $res_data = $this->goods_model->search($search_where, '', $screening);
        error_json($res_data);
    }


    /**
     * 商品评论
     */
    public function comment_list()
    {
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get_post('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        $goods_id = (int)$this->input->get_post('id', true);
        if (!empty($goods_id)) {
            $where_data['where']['goods_id'] = $goods_id;
            //搜索条件end
            $where_data['select'] = 'c.*,m.username,m.headimgurl';
            $where_data['join']   = array(
                array('member as m', 'c.m_id=m.id')
            );
            //查到数据
            $list_data = $this->loop_model->get_list('goods_comment as c', $where_data, $pagesize, $pagesize * ($page - 1), 'c.id desc');//列表
            foreach ($list_data as $key) {
                $key['username'] = substr($key['username'], 0, 3) .'****'. substr($key['username'], -4, 4);
                $key['addtime']  = date('Y-m-d', $key['addtime']);
                if ($key['sku_value'] != '') {
                    //解析规格属性
                    $key['sku_value'] = json_decode($key['sku_value'], true);
                }
                $list[] = $key;
            }
            //开始分页start
            $all_rows   = $this->loop_model->get_list_num('goods_comment as c', $where_data);//所有数量;
            $page_count = ceil($all_rows / $pagesize);
            //开始分页end

            $res = array('list' => $list, 'page_count' => $page_count, 'all_rows' => $all_rows);
            error_json($res);
        }
    }
}
