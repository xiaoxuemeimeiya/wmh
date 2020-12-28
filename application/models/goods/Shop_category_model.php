<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Shop_category_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 后台查询所有数据
     * @return array
     */
    public function get_all($shop_id, $reid = 0)
    {
        $reid = (int)$reid;
        $this->db->where('reid', $reid);
        $this->db->where('shop_id', $shop_id);
        $this->db->order_by('sortnum asc,id asc');
        $query = $this->db->get('goods_shop_category');
        $list = $query->result_array();//echo $this->db->last_query();
        foreach ($list as $key) {
            $key['down'] = self::get_all($shop_id, $key['id']);
            $cat_list[] = $key;
        }
        return $cat_list;
    }

    /**
     * 查询指定ID的所有下级菜单id
     * @param int $reid id
     * @return array
     */
    public function get_reid_down($shop_id, $reid = '')
    {
        $reid = (int)$reid;
        if (!empty($reid)) {
            $this->db->where(array('reid' => $reid));
            $this->db->where('shop_id', $shop_id);
            $query = $this->db->get('goods_shop_category');
            $reid_list = $query->result_array();
            foreach ($reid_list as $key) {
                $id[] = $key['id'];
                $down_id = $this->get_reid_down($shop_id, $key['id']);
                if (!empty($down_id)) {
                    $id = array_merge($id, $down_id);
                }
            }
            return $id;
        }
    }
}
