<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Model_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 更新规格
     * @return array
     */
    public function update($data_post = array())
    {
        $update_data['name']   = $data_post['name'];
        $update_data['cat_id'] = join(',', $data_post['cat_id']);
        if (empty($update_data['name'])) {
            error_json('模型名称不能为空');
        }

        $this->load->model('loop_model');
        if (!empty($data_post['id'])) {
            //修改数据
            $res = $this->loop_model->update_id('goods_model', $update_data, $data_post['id']);
            $model_id = $data_post['id'];
            admin_log_insert('修改模型' . $model_id);
        } else {
            //增加数据
            $res = $this->loop_model->insert('goods_model', $update_data);
            $model_id = $res;
            admin_log_insert('增加模型' . $model_id);
        }
        //整理属性
        if (!empty($data_post['attr'])) {
            self::update_attr($data_post['attr'], $model_id);
        }
        return $res;
    }

    /**
     * 更新模型规格属性
     * @param array $attr_data 模型规格
     * @param int   $model_id  模型id
     * @return array
     */
    public function update_attr($attr_data, $model_id)
    {
        if (empty($attr_data) || empty($model_id)) return false;

        $len = count($attr_data['name']);
        $ids = "";
        for ($i = 0; $i < $len; $i++) {
            if (isset($attr_data['name'][$i]) && isset($attr_data['value'][$i])) {
                $options = str_replace('，', ',', $attr_data['value'][$i]);
                $is_search = isset($attr_data['search'][$i]) ? $attr_data['search'][$i] : 0;

                //设置商品模型扩展属性 字段赋值
                $update_data = array(
                    "model_id" => intval($model_id),
                    "type" => $attr_data['type'][$i],
                    "name" => $attr_data['name'][$i],
                    "value" => trim($options, ','),
                    "search" => $is_search
                );

                $this->load->model('loop_model');
                $id = intval($attr_data['id'][$i]);
                if ($id) {
                    //更新模型扩展属性
                    $res = $this->loop_model->update_id('goods_model_attr', $update_data, $id);
                } else {
                    //新增商品模型扩展属性
                    $id = $this->loop_model->insert('goods_model_attr', $update_data);
                }
                $ids[] = $id;
            }
        }

        if (!empty($ids)) {
            //删除商品模型扩展属性
            $this->db->where('model_id', $model_id);
            $this->db->where_not_in('id', $ids);
            $this->db->delete('goods_model_attr');
        }
    }
}
