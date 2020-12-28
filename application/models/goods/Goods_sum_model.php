<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_sum_model extends CI_Model
{
    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 取出购物车数据
     * @return array
     */
    public function get_cart($m_id)
    {
        if (!empty($m_id)) {
            $query     = $this->db->get_where('goods_cart', array('m_id' => $m_id));
            $cart_data = $query->row_array();
            if (!empty($cart_data['desc'])) {
                $cart_goods = json_decode($cart_data['desc'], true);//购物车商品数据
                return $cart_goods;
            } else {
                return '购物车没有数据';
            }
        } else {
            return '用户不存在';
        }
    }

    /**
     * 计算购物车中的数据
     * @return array
     */
    public function cart_count($m_id)
    {
        if (!empty($m_id)) {
            $cart_goods = self::get_cart($m_id);
            if (is_array($cart_goods)) {
                $res = self::sku_goods_count($cart_goods);
                return $res;
            } else {
                return $cart_goods;
            }
        } else {
            return '用户不存在';
        }
    }

    /**
     * 计算购物车中选中的商品的数据
     * @param array $select_sku_id 已经选择的skuid
     * @param int   $m_id          用户id
     * @return array
     */
    public function cart_select_count($select_sku_id = array(), $m_id)
    {
        if (!empty($m_id) && !empty($select_sku_id)) {
            $cart_goods = self::get_cart($m_id);//读取购物车
            if (is_array($cart_goods)) {
                foreach ($cart_goods as $val => $key) {
                    if (in_array($val, $select_sku_id)) {
                        $select_cart_goods[$val] = $key;
                    }
                }
                return $select_cart_goods;
            } else {
                return $cart_goods;
            }
        } else {
            return '用户不存在';
        }
    }

    /**
     * 下单时计算商品数据,并查询优惠活动和地址
     * @param array $select_cart_goods 商品数据
     * @param int   $prov              地区
     * @param int   $m_id              用户id
     * @return array
     */
    public function order_count($select_cart_goods, $prov)
    {
        $m_id = get_m_id();
        if (!empty($select_cart_goods) && !empty($prov) && !empty($m_id)) {
            $sku_count_data = self::sku_goods_count($select_cart_goods, 'order');
            if (is_array($sku_count_data)) {
                //计算其他费用
                $this->load->model('system/delivery_model');
                $this->load->model('market/coupons_model');
                foreach ($sku_count_data['list'] as $shop_id => $key) {
                    $shop_list[$shop_id] = $key;
                    $delivery_list       = $coupons_list = array();
                    //店铺配送方式(需要是优惠后的价格在计算是否包邮)
                    $delivery_list = $this->delivery_model->shop_delivery(price_format($key['all_sell_price'] - $key['promotion_price']), $key['all_sku_weight'], $prov, $shop_id, $m_id);
                    if (empty($delivery_list)) {
                        $client_type = $this->uri->segment(1);
                        msg('店铺“' . $key['shop_data']['shop_name'] . '”在该收货地址没有可送达的快递', site_url('/' . $client_type . '/member/address?redirect_url=' . urlencode(get_now_url())));
                        exit;
                    }
                    $shop_list[$shop_id]['delivery_list'] = $delivery_list;

                    //店铺优惠券(需要是优惠后的价格)
                    $coupons_list                        = $this->coupons_model->user_shop_coupons($m_id, $shop_id, price_format($key['all_sell_price'] - $key['promotion_price']));
                    $shop_list[$shop_id]['coupons_list'] = $coupons_list;
                }
                $res = array('result' => array('list' => $shop_list));
                return $res;
            } else {
                return $sku_count_data;
            }
        } else {
            return '参数缺失';
        }
    }

    /**
     * 根据商品skuid查询商品数据
     * @param array  $cart_goods 数据 id数量对应的数组
     * @param string $sum_type   计算方式,cart 购物车计算(不强制判断库存),order 下单计算(需要强制判断库存)
     * @return array
     */
    public function sku_goods_count($cart_goods = array(), $sum_type = 'cart')
    {
        $result = array(
            'list'             => 0,//按店铺商品列表
            'sku_count'        => 0,//商品数量
            'all_sell_price'   => 0,//销售总价格
            'all_market_price' => 0,//市场总价格
        );
        if (empty($cart_goods)) {
            return $result;
        }
        $sku_id = array();
        //所有sku信息
        foreach ($cart_goods as $v => $k) {
            $sku_id[] = $v;
        }
        //查询商品信息
        $this->db->from('goods_sku as gs');
        $this->db->select('gs.*,g.name,g.image,g.shop_id');
        $this->db->where_in('gs.id', $sku_id);
        $this->db->join('goods as g', 'gs.goods_id=g.id');
        $query      = $this->db->get();
        $goods_data = $query->result_array();//echo $this->db->last_query()."<br>";
        $i          = $all_sell_price = $all_market_price = 0;
        foreach ($goods_data as $key) {
            $goods_num = '';
            $goods_num = $cart_goods[$key['id']];//购买数量
            //下单的时候需要判断库存是否足够
            if ($sum_type == 'order') {
                if ($goods_num > $key['store_nums']) {
                    return '商品"' . $key['name'] . '"最多只能购买' . $key['store_nums'] . '件';
                } else if ($key['minimum'] > $goods_num) {
                    return '商品"' . $key['name'] . '"最小起订量为' . $key['minimum'] . '件';
                }
            }
            $sku_value = '';
            $sku_value = json_decode($key['value'], true);
            //如果选择的商品存在图片规格,那么主图显示规格图片
            if (!empty($sku_value)) {
                foreach ($sku_value as $sv) {
                    if ($sv['type'] == 2) {
                        $key['image'] = $sv['value'];
                    }
                }
            }
            $key['num']              = $goods_num;//购买数量
            $key['sell_price']       = format_price(self::sku_member_group_price($key));//单个销售价(计算会员价格)
            $key['sku_sell_price']   = $key['sell_price'] * $goods_num;//单个商品总的销售价
            $key['market_price']     = format_price($key['market_price']);//单个市场价
            $key['sku_market_price'] = $key['market_price'] * $goods_num;//单个商品总的市场价
            $key['sku_weight']       = $key['weight'] * $goods_num;//单个商品总的重量
            $key['value']            = $sku_value;
            $key['weight']           = $key['weight'] * $goods_num;
            //商品列表
            $goods_list[$key['shop_id']]['sku_list'][] = $key;
            //店铺内商品销售总价
            $goods_list[$key['shop_id']]['all_sell_price'] += $key['sku_sell_price'];
            //店铺内商品市场总价
            $goods_list[$key['shop_id']]['all_market_price'] += $key['sku_market_price'];
            //店铺内商品总重量
            $goods_list[$key['shop_id']]['all_sku_weight'] += $key['sku_weight'];
            //店铺内商品总数
            $goods_list[$key['shop_id']]['all_count_sku'] += $goods_num;
            //所有商品总的销售价
            $all_sell_price += $key['sku_sell_price'];
            //所有商品总的市场价
            $all_market_price += $key['sku_market_price'];
            $i++;
        }
        //查询店铺信息和店铺优惠活动
        foreach ($goods_list as $shop_id => $key) {
            //店铺信息
            $shop_data = array();
            $this->db->select('m_id,shop_name,logo');
            $shop_query       = $this->db->get_where('member_shop', array('m_id' => $shop_id));
            $shop_data        = $shop_query->row_array();
            $key['shop_data'] = $shop_data;

            //店铺优惠活动start
            $promotion_data = array();
            $m_id                = get_m_id();
            $shop_all_sell_price = price_format($key['all_sell_price']);//订单商品销售总金额
            $this->load->model('market/promotion_model');
            $promotion_data = $this->promotion_model->cash_list($m_id, $shop_id, $shop_all_sell_price);
            $key['promotion_price'] = $promotion_data['promotion_price'];
            $key['promotion_data']  = $promotion_data['promotion_data'];
            //店铺优惠活动end
            $list[$shop_id] = $key;
        }
        return array('list' => $list, 'sku_count' => $i, 'all_sell_price' => $all_sell_price, 'all_market_price' => $all_market_price);
    }

    /**
     * 会员组价格
     * @param array $sku_data SKU数据
     * @return array
     */
    public function sku_member_group_price($sku_data = array())
    {
        $this->load->helpers('web_helper');
        $m_id = get_m_id();
        if (!empty($m_id)) {
            //查询会员会员组信息
            $this->db->from('member_user as u');
            $this->db->select('u.m_id,g.discount');
            $this->db->where(array('u.m_id' => $m_id));
            $this->db->join('member_user_group as g', 'u.group_id=g.id');
            $m_query     = $this->db->get();
            $member_data = $m_query->row_array();
            $discount    = $member_data['discount'];
            if (!empty($discount) && !empty($sku_data) && $discount > 0) {
                //查询会员会员组价格
                $sell_price = $sku_data['sell_price'] * ($discount / 100);
                return $sell_price;
            }
        }
        return $sku_data['sell_price'];
    }

}
