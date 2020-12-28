<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Shop extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 获取商品列表
     */
    public function get_goods_list()
    {
        header('Access-Control-Allow-Origin: *');
        $page = (int)($this->input->get('page', true));
        $username = $this->input->get('username', true);

        if (empty($username)) {
            error_json('用户名不能为空');
        }

        $page = (($page - 1) <= 0) ? 0 : $page;
        $pageSize = 30;
        $offset = $page * $pageSize;

        // $username = '13800138000';
        $member_data = $this->loop_model->get_where('member', array('username' => $username));

        $query = $this->db->from('goods')
            ->select('id, name, image, desc')
            ->join('goods_desc', 'goods.id = goods_desc.goods_id')
            ->where('goods.shop_id', $member_data['id'])
            ->where('goods.status', 0)
            ->where('sortnum',8)
            ->order_by('goods_id', 'ASC')
            ->limit($pageSize, $offset)
            ->get();
        // echo $this->db->last_query()."<br>";exit;
        $goodsList = $query->result_array();

        $dataList = [
            'count' => count($goodsList),
        ];
        foreach ($goodsList as $goods) {
            $domain = rtrim(config_item('website_domain'), '/');

            if (strpos($goods['desc'], '/uploads/') !== false) {
                //$goods['desc'] = str_replace('src="', 'src="' . $domain, $goods['desc']);
            }

            $dataList['goods_list'][] = [
                'id' => $goods['id'],
                'title' => $goods['name'],
                'link' => $domain . '/mobile/goods/product/' . $goods['id'],
                'image' => $domain . $goods['image'],
                'desc' => $goods['desc'],
            ];
        }
        error_json($dataList);
    }
}
