<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Cart_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 添加一个商品到购物车
     * @param int $sku_id 商品skuid
     * @param int $num 商品数量
     * @param int $m_id 用户id
     */
    public function add($sku_id = '', $num = '', $m_id = '')
    {
        $sku_id = (int)$sku_id;
        $num    = (int)$num;
        $m_id   = (int)$m_id;
        if (!empty($sku_id) && !empty($num) && !empty($m_id)) {
            $this->load->model('loop_model');
            $sku_data = $this->loop_model->get_id('goods_sku', $sku_id);
            if (empty($sku_data)) {
                return '商品不存在';
            } else {
                if ($sku_data['store_nums'] < $num) {
                    return '最多只能购买'.$sku_data['store_nums'].'件';
                } else if ($sku_data['minimum'] > $num) {
                    return '商品最小起订量为'.$sku_data['minimum'].'件';
                } else {
                    //查询购物车是否已经有数据
                    $cart_data = $this->loop_model->get_where('goods_cart', array('m_id' => $m_id));

                    $cart_goods = json_decode($cart_data['desc'], true);//购物车商品数据
                    if (!empty($cart_goods[$sku_id])) {
                        $cart_goods[$sku_id] = $cart_goods[$sku_id] + $num;
                        $update_data = array(
                            'desc'    => json_encode($cart_goods),
                            'addtime' => time(),
                        );
                        $res = $this->loop_model->update_where('goods_cart', $update_data, array('id' => $cart_data['id'], 'm_id' => $m_id));
                        if (!empty($res)) {
                            return 'y';
                        } else {
                            return '加入失败';
                        }
                        //return '商品已经加入了购物车';
                    } else {
                        $cart_goods[$sku_id] = $num;//数量
                        //购物车有数据开始修改
                        if (!empty($cart_data['id'])) {
                            $update_data = array(
                                'desc'    => json_encode($cart_goods),
                                'addtime' => time(),
                            );
                            $res = $this->loop_model->update_where('goods_cart', $update_data, array('id' => $cart_data['id'], 'm_id' => $m_id));
                        } else {
                            //购物车有数据开始新增
                            $insert_data = array(
                                'm_id'    => $this->member_data['id'],
                                'desc'    => json_encode($cart_goods),
                                'addtime' => time(),
                            );
                            $res = $this->loop_model->insert('goods_cart', $insert_data);
                        }
                        if (!empty($res)) {
                            return 'y';
                        } else {
                            return '加入失败';
                        }
                    }
                }
            }
        } else {
            return '参数不全';
        }
    }


    /**
     * 修改购物车商品数量
     * @param int $sku_id 商品skuid
     * @param int $num 商品数量
     * @param int $m_id 用户id
     */
    public function update($sku_id = '', $num = '', $m_id = '')
    {
        $sku_id = (int)$sku_id;
        $num    = (int)$num;
        $m_id   = (int)$m_id;
        if (!empty($sku_id) && !empty($num) && !empty($m_id)) {
            $this->load->model('loop_model');
            //查询购物车是否已经有数据
            $cart_data = $this->loop_model->get_where('goods_cart', array('m_id' => $m_id));

            $cart_goods = json_decode($cart_data['desc'], true);//购物车商品数据
            if (!empty($cart_goods[$sku_id])) {
                $sku_data = $this->loop_model->get_id('goods_sku', $sku_id);
                if (empty($sku_data)) {
                    return '商品不存在';
                } else {
                    if ($sku_data['store_nums'] < $num) {
                        return '最多只能购买'.$sku_data['store_nums'].'件';
                    } else if ($sku_data['minimum'] > $num) {
                        return '商品最小起订量为'.$sku_data['minimum'].'件';
                    } else {
                        $cart_goods[$sku_id] = $num;//数量
                        //购物车有数据开始修改
                        $update_data = array(
                            'desc'    => json_encode($cart_goods),
                            'addtime' => time(),
                        );
                        $res = $this->loop_model->update_where('goods_cart', $update_data, array('id' => $cart_data['id'], 'm_id' => $m_id));
                        if (!empty($res)) {
                            return 'y';
                        } else {
                            return '修改失败';
                        }
                    }
                }
            } else {
                return '购物车不存在该商品';
            }
        } else {
            return '参数不全';
        }
    }

    /**
     * 修改购物车商品数量
     * @param int $sku_id 商品skuid
     * @param int $m_id 用户id
     */
    public function delete($sku_id = '', $m_id = '')
    {
        $m_id = (int)$m_id;
        if (!empty($sku_id) && !empty($m_id)) {
            $this->load->model('loop_model');
            //查询购物车是否已经有数据
            $cart_data = $this->loop_model->get_where('goods_cart', array('m_id' => $m_id));

            $cart_goods = json_decode($cart_data['desc'], true);//购物车商品数据
            if (is_array($sku_id)) {
                //批量删除
                foreach ($sku_id as $key) {
                    $key = (int)$key;
                    unset($cart_goods[$key]);
                }
            } else {
                //单个删除
                $sku_id = (int)$sku_id;
                unset($cart_goods[$sku_id]);
            }
            $update_data = array(
                'desc'    => json_encode($cart_goods),
                'addtime' => time(),
            );
            $res = $this->loop_model->update_where('goods_cart', $update_data, array('id' => $cart_data['id'], 'm_id' => $m_id));
            if (!empty($res)) {
                return 'y';
            } else {
                return '删除失败';
            }
        } else {
            return '参数不全';
        }
    }

    /**
     * 清空购物车
     * @param int $m_id 用户id
     */
    public function clear($m_id = '')
    {
        $m_id = (int)$m_id;
        if (!empty($m_id)) {
            $update_data = array(
                'desc'    => json_encode(array()),
                'addtime' => time(),
            );
            $res = $this->loop_model->update_where('goods_cart', $update_data, array('m_id' => $m_id));
            if (!empty($res)) {
                $error['status'] = 'y';
                return 'y';
            } else {
                $error['info'] = '清空失败';
                return '清空失败';
            }
        } else {
            $error['info'] = '用户信息不存在';
            return $error;
        }
    }
}
