<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_favorite extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
        $this->load->helpers('web_helper');
        $this->member_data = get_member_data();
    }

    /**
     * 添加收藏商品
     */
    public function add_favorite()
    {
        if (is_post()) {
            $id = (int)$this->input->get_post('id', true);
            if (!empty($id)) {
                $goods = $this->loop_model->get_id('goods', $id);
                if (empty($goods)) error_json('商品不存在');

                $favorite_data = $this->loop_model->get_where('goods_favorite', array('goods_id' => $id, 'm_id' => $this->member_data['id']));
                if (!empty($favorite_data)) {
                    error_json($favorite_data['id'], 'reply');
                } else {
                    $res = $this->loop_model->insert('goods_favorite', array('goods_id' => $id, 'm_id' => $this->member_data['id'], 'addtime' => time()));
                    if (!empty($res)) {
                        error_json('y');
                    } else {
                        error_json('收藏失败');
                    }
                }
            }
        }
    }

    /**
     * 删除收藏商品
     */
    public function delete_favorite()
    {
        if (is_post()) {
            $id = $this->input->get_post('id', true);
            if (!empty($id)) {
                if (is_array($id)) {
                    $res = $this->loop_model->delete_where('goods_favorite', array('where_in' => array('id' => $id), 'where' => array('m_id' => $this->member_data['id'])));
                } else {
                    $id = (int)$id;
                    $res = $this->loop_model->delete_where('goods_favorite', array('where' => array('m_id' => $this->member_data['id'], 'id' => $id)));
                }
                if (!empty($res)) {
                    error_json('y');
                } else {
                    error_json('取消收藏失败');
                }
            }
        }
    }
}
