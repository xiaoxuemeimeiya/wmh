<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Cart extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('web_helper');
        $this->member_data = get_member_data();
        $this->load->model('loop_model');
    }

    /**
     * 添加一个商品到购物车
     */
    public function add()
    {
        $sku_id = (int)$this->input->get_post('sku_id', true);
        $num    = (int)$this->input->get_post('num', true);
        if (empty($sku_id)) error_json('商品SKUID不能为空');
        if (empty($num)) $num = 1;

        $this->load->model('goods/cart_model');
        $res = $this->cart_model->add($sku_id, $num, $this->member_data['id']);
        if ($res == 'y') {
            $cart_data  = $this->loop_model->get_where('goods_cart', array('m_id' => $this->member_data['id']));
            $cart_goods = json_decode($cart_data['desc'], true);//购物车商品数据

            //根据skuid获取商品信息,包括商品数量和总价
            $this->load->model('goods/goods_sum_model');
            $sku_res = $this->goods_sum_model->sku_goods_count($cart_goods);
            if (!empty($sku_res)) {
                unset($sku_res['list']);
                error_json($sku_res);
            }
        } else {
            error_json($res);
        }
    }

    /**
     * 从购物车删除一个商品
     */
    public function delete()
    {
        $sku_id = $this->input->get_post('sku_id', true);
        if (empty($sku_id)) error_json('商品SKUID不能为空');

        $this->load->model('goods/cart_model');
        if (strpos($sku_id, ',')) {
            $sku_id = explode(',', $sku_id);
        }
        $res = $this->cart_model->delete($sku_id, $this->member_data['id']);
        if ($res == 'y') {
            $cart_data  = $this->loop_model->get_where('goods_cart', array('m_id' => $this->member_data['id']));
            $cart_goods = json_decode($cart_data['desc'], true);//购物车商品数据

            //根据skuid获取商品信息,包括商品数量和总价
            $this->load->model('goods/goods_sum_model');
            $sku_res = $this->goods_sum_model->sku_goods_count($cart_goods);
            if (!empty($sku_res)) {
                unset($sku_res['list']);
                error_json($sku_res);
            }
        } else {
            error_json($res);
        }
    }

    /**
     * 修改购物车商品数量
     */
    public function update()
    {
        $sku_id = (int)$this->input->get_post('sku_id', true);
        $num    = (int)$this->input->get_post('num', true);
        if (empty($sku_id)) error_json('商品SKUID不能为空');
        if (empty($num)) error_json('商品数量不能为空');

        $this->load->model('goods/cart_model');
        $res = $this->cart_model->update($sku_id, $num, $this->member_data['id']);
        if ($res == 'y') {
            $cart_data  = $this->loop_model->get_where('goods_cart', array('m_id' => $this->member_data['id']));
            $cart_goods = json_decode($cart_data['desc'], true);//购物车商品数据

            //根据skuid获取商品信息,包括商品数量和总价
            $this->load->model('goods/goods_sum_model');
            $sku_res = $this->goods_sum_model->sku_goods_count($cart_goods);
            if (!empty($sku_res)) {
                unset($sku_res['list']);
                error_json($sku_res);
            }
        } else {
            error_json($res);
        }
    }

    /**
     * 清空购物车
     */
    public function clear()
    {
        $this->load->model('goods/cart_model');
        $res = $this->cart_model->clear($this->member_data['id']);
        error_json($res);
    }

    /**
     * 购物车总体展示(用于在页面显示购物车基本信息,数量,总价等)
     */
    public function cart_count()
    {
        $sku_res = array(
            'list'             => 0,//按店铺商品列表
            'sku_count'        => 0,//商品数量
            'all_sell_price'   => 0,//销售总价格
            'all_market_price' => 0,//市场总价格
        );
        $this->load->model('goods/goods_sum_model');
        $sku_res = $this->goods_sum_model->cart_count($this->member_data['id']);
        if (is_array($sku_res)) {
            unset($sku_res['list']);
            error_json($sku_res);
        } else {
            error_json($sku_res);
        }
    }

    /**
     * 购物车商品展示
     */
    public function cart_list()
    {
        $this->load->model('goods/goods_sum_model');
        $res = $this->goods_sum_model->cart_count($this->member_data['id']);
        if (!empty($res)) {
            error_json($res);
        }
    }


}
