<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

        /**
     * 根据id查询数据(前台展示用)
     * @param int $id 数据id
     * @return array
     */
    public function get_detail($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $goods_data = $this->loop_model->get_where('goods', array('id'=>$id),'id,vedio,name,sub_desc,sale,visit,market_price,sell_price,time');
            if (empty($goods_data)) return false;//msg('商品不存在');
            if ($goods_data['status'] != 0) return false; //msg('商品已下架');
            $goods_data['market_price'] = format_price($goods_data['market_price']);
            $goods_data['sell_price']   = format_price($goods_data['sell_price']);
            $goods_data['endtime']   = $goods_data['time'] ? $goods_data['time'].'个月' : '终身';
            //商品描述
            $desc               = $this->loop_model->get_where('goods_desc', array('goods_id' => $id), 'desc');
            $goods_data['desc'] = $desc['desc'];
            //商品图片
            $goods_data['image_list'] = $this->loop_model->get_list('goods_image', array('where' => array('goods_id' => $id), 'select' => 'url'));

            //商品sku
            /*
            $sku_list = $this->loop_model->get_list('goods_sku', array('where' => array('goods_id' => $id), 'select' => 'id,sku_no,value,store_nums,market_price,sell_price,weight,minimum'));
            $this->load->model('goods/goods_sum_model');
            foreach ($sku_list as $v => $k) {
                $arr                    = $sku_key_data = array();
                $arr['sku_id']          = $k['id'];
                $arr['sku_no']          = $k['sku_no'];
                $arr['store_nums']      = $k['store_nums'];
                $arr['minimum']         = $k['minimum'];
                $arr['market_price']    = format_price($k['market_price']);
                $arr['sell_price']      = format_price($this->goods_sum_model->sku_member_group_price($k));
                $max_min_sell_price[]   = $arr['sell_price'];//得到价格的数据,方便计算最大和最小价格
                $max_min_market_price[] = $arr['market_price'];//得到价格的数据,方便计算最大和最小价格
                $spec_value             = json_decode($k['value'], true);//规格属性解析
                if (!empty($spec_value)) {
                    foreach ($spec_value as $val => $key) {
                        $spec_select_value[$val]['name']    = $key['name'];//属性名
                        $spec_select_value[$val]['type']    = $key['type'];//属性类型
                        $spec_select_value[$val]['value'][] = $key['value'];//属性集合
                        $sku_key_data[]                     = $key['value'];//sku键值
                    }
                }
                $sku_key        = join(';', $sku_key_data);
                $arr['sku_key'] = $sku_key;//skuid的属性名称组合为键值
                $sku_data[]     = $arr;
            }
            
            //属性按名称集合
            if (!empty($spec_select_value)) {
                foreach ($spec_select_value as $k) {
                    $spec_select_value_unique = array_unique($k['value']);//去掉重复的值
                    $spec_select_list[]       = array('name' => $k['name'], 'type' => $k['type'], 'value' => $spec_select_value_unique);//规格列表
                }
            }
            */
            //$goods_data['spec_select_list']     = $spec_select_list;//规格名称和属性列表
            //$goods_data['sku_list']             = $sku_data;//sku列表以属性组合为键值
            //$goods_data['one_sku_data']         = current($sku_data);//第一个sku详情
            return $goods_data;
        }
    }

    /**
     * 根据id查询数据(前台展示用)
     * @param int $id 数据id
     * @return array
     */
    public function get_id($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $goods_data = $this->loop_model->get_id('goods', $id);
            if (empty($goods_data)) msg('商品不存在');
            if ($goods_data['status'] != 0) msg('商品已下架');
            $goods_data['market_price'] = format_price($goods_data['market_price']);
            $goods_data['sell_price']   = format_price($goods_data['sell_price']);
            //商品描述
            $desc               = $this->loop_model->get_where('goods_desc', array('goods_id' => $id), 'desc');
            $goods_data['desc'] = $desc['desc'];
            //商品图片
            $goods_data['image_list'] = $this->loop_model->get_list('goods_image', array('where' => array('goods_id' => $id), 'select' => 'url'));

            //商品sku
            $sku_list = $this->loop_model->get_list('goods_sku', array('where' => array('goods_id' => $id), 'select' => 'id,sku_no,value,store_nums,market_price,sell_price,weight,minimum'));
            $this->load->model('goods/goods_sum_model');
            foreach ($sku_list as $v => $k) {
                $arr                    = $sku_key_data = array();
                $arr['sku_id']          = $k['id'];
                $arr['sku_no']          = $k['sku_no'];
                $arr['store_nums']      = $k['store_nums'];
                $arr['minimum']         = $k['minimum'];
                $arr['market_price']    = format_price($k['market_price']);
                $arr['sell_price']      = format_price($this->goods_sum_model->sku_member_group_price($k));
                $max_min_sell_price[]   = $arr['sell_price'];//得到价格的数据,方便计算最大和最小价格
                $max_min_market_price[] = $arr['market_price'];//得到价格的数据,方便计算最大和最小价格
                $spec_value             = json_decode($k['value'], true);//规格属性解析
                if (!empty($spec_value)) {
                    foreach ($spec_value as $val => $key) {
                        $spec_select_value[$val]['name']    = $key['name'];//属性名
                        $spec_select_value[$val]['type']    = $key['type'];//属性类型
                        $spec_select_value[$val]['value'][] = $key['value'];//属性集合
                        $sku_key_data[]                     = $key['value'];//sku键值
                    }
                }
                $sku_key        = join(';', $sku_key_data);
                $arr['sku_key'] = $sku_key;//skuid的属性名称组合为键值
                $sku_data[]     = $arr;
            }
            //属性按名称集合
            if (!empty($spec_select_value)) {
                foreach ($spec_select_value as $k) {
                    $spec_select_value_unique = array_unique($k['value']);//去掉重复的值
                    $spec_select_list[]       = array('name' => $k['name'], 'type' => $k['type'], 'value' => $spec_select_value_unique);//规格列表
                }
            }
            $goods_data['spec_select_list']     = $spec_select_list;//规格名称和属性列表
            $goods_data['sku_list']             = $sku_data;//sku列表以属性组合为键值
            $goods_data['one_sku_data']         = current($sku_data);//第一个sku详情
            $goods_data['max_min_sell_price']   = $max_min_sell_price;//销售价格区间
            $goods_data['max_min_market_price'] = $max_min_market_price;//市场价区间
            return $goods_data;
        }
    }

    /**
     * 根据id查询数据
     * @param int $id 数据id
     * @return array
     */
    public function admin_edit($id = FALSE, $shop_id = '')
    {
        $id = (int)$id;
        if ($id !== FALSE) {
            //商品信息
            $query = $this->db->get_where('goods', array('id' => $id));
            $row = $query->row_array();
            //商品描述
            $query       = $this->db->get_where('goods_desc', array('goods_id' => $id));//echo $this->db->last_query()."<br>";
            $row_desc    = $query->row_array();
            $row['desc'] = $row_desc['desc'];

            //图片属性
            $this->db->where('goods_id', $id);
            $query             = $this->db->get('goods_image');
            $row['image_list'] = $query->result_array();

            //sku属性
            $this->db->where('goods_id', $id);
            $query    = $this->db->get('goods_sku');
            $sku_list = $query->result_array();
            foreach ($sku_list as $v => $k) {
                $spec_name         = array();//只需要最后一个sku的规格名称
                $k['market_price'] = format_price($k['market_price']);
                $k['sell_price']   = format_price($k['sell_price']);
                $spec_value        = json_decode($k['value'], true);//规格属性解析
                if (!empty($spec_value)) {
                    foreach ($spec_value as $val => $key) {
                        $key['spec_num']   = $val;
                        $key['sv']         = $v;
                        $k['value_list'][] = $key;
                        $spec_name[]       = array('name' => $key['name'], 'type' => $key['type']);//sku的规格名称
                    }

                }
                $sku_data[] = $k;
            }

            $row['endtime'] = date('Y-m-d H:i:s',$row['endtime']);//sku的规格名称
            $row['spec_name'] = $spec_name;//sku的规格名称
            $row['sku_list']  = $sku_data;
            return $row;
        }
    }

    /**
     * 更新或者添加商品
     * @param array $data_post 修改数据
     * @param int   $shop_id   店铺id,后台默认为0,店铺后台为店铺id
     * @return array
     */
    public function update($data_post = array(), $shop_id)
    {
        //数据验证
        if (empty($data_post['name'])) {
            return '商品名称不能为空';
        }elseif (empty($data_post['sub_desc'])) {
            return '商品描述不能为空';
        } elseif (empty($data_post['sell_price'])) {
            return '销售价格错误';
        } elseif (empty($data_post['market_price'])) {
            return '市场价格错误';
        } elseif (empty($data_post['sku_no'])) {
            return 'sku信息不能为空';
        }

        $update_data = array(
            'name'         => $data_post['name'],
            'sub_desc'     => $data_post['sub_desc'],
            'model_id'     => (int)$data_post['model_id'],
            'cat_id'       => (int)$data_post['cat_id'],
            'sell_price'   => price_format(min($data_post['sell_price'])),
            'market_price' => price_format(min($data_post['market_price'])),
            'unit'         => $data_post['unit'],
            'status'       => (int)$data_post['status'],
            'sortnum'      => (int)$data_post['sortnum'],
            'vedio'        => $data_post['type']==1 ? $data_post['vedio'] :$data_post['vedio_url'],
            'edittime'     => time(),
            'time'         => $data_post['time'],
            'json'         => $data_post['json'],
        );

        $data_post['desc'] = remove_xss($this->input->post('desc'));//单独过滤详情xss

        //商品默认图片
        if (!empty($data_post['image_list'])) {
            $data_post['image'] == '' ? $update_data['image'] = ($data_post['image_list'] ? $data_post['image_list'][0] : '') : $update_data['image'] = $data_post['image'];
        }

        //上下架时间
        if ($update_data['status'] == 0) {
            $update_data['up_time'] = time();
        } elseif ($update_data['status'] == 2) {
            $update_data['down_time'] = time();
        }


        //去掉重复的规格名称
        $spec_name = $spec_value = '';
        if (!empty($data_post['spec_name'])) {
            $spec_name = array_unique($data_post['spec_name']);
            if (count($spec_name) != count($data_post['spec_value'])) {
                return 'sku规格信息不完整或者规格名称重复存在';
            }

            //规格值处理
            $spec_value = array_merge($data_post['spec_value']);
            //规格值备注处理
            $spec_value_note = array_merge($data_post['spec_value_note']);
            //判断是否存在重复的规格值
            $spec_value_data = array();
            foreach ($spec_value as $sk) {
                foreach ($sk as $v => $k) {
                    if (empty($k)) error_json('规格值不能为空');
                    $spec_value_data[$v] .= $k;
                }
            }
            $unique_spec_value_data = array_unique($spec_value_data);
            if (count($spec_value_data) != count($unique_spec_value_data)) {
                return '规格值存在相同的值';
            }
        }

        //商品库存
        $store_nums = 0;
        foreach ($data_post['sku_no'] as $v => $k) {
            $store_nums = $store_nums + $data_post['store_nums'][$v];
        }
        $update_data['store_nums'] = $store_nums;

        //保存或者修改商品信息
        $this->load->model('loop_model');
        if (!empty($data_post['id'])) {
            //修改数据
            $where['id'] = (int)$data_post['id'];
            if ($shop_id > 0) {
                $where['shop_id'] = $shop_id;
            }
            $res = $this->loop_model->update_where('goods', $update_data, $where);
            $this->loop_model->update_where('goods_desc', array('desc' => $data_post['desc']), array('goods_id' => $where['id']));
            $goods_id = $where['id'];
        } else {
            //增加数据
            $update_data['addtime'] = time();
            if ($shop_id > 0) {
                $update_data['shop_id'] = $shop_id;
            }
            $this->db->trans_start();
            $this->db->insert('goods', $update_data);
            $res      = $this->db->insert_id();
            $goods_id = $res;
            $res      = $this->db->insert('goods_desc', array('goods_id' => $goods_id, 'desc' => $data_post['desc']));
            $this->db->trans_complete();
        }
        if (!empty($res)) {
            // print_r($data_post);
            //删除已经删除的规格值start
            foreach ($data_post['sku_no'] as $v => $k) {
                $reply_sku_id[] = $data_post['sku_id'][$v];
            }
            if (!empty($reply_sku_id)) {
                $this->db->where('goods_id', $goods_id);
                $this->db->where_not_in('id', $reply_sku_id);
                $this->db->delete('goods_sku');
            }
            //删除已经删除的规格值end

            //sku信息处理
            foreach ($data_post['sku_no'] as $v => $k) {
                $sku_data = array(
                    'goods_id'     => $goods_id,
                    'sku_no'       => $k,
                    'store_nums'   => $data_post['store_nums'][$v],
                    'market_price' => price_format($data_post['market_price'][$v]),
                    'sell_price'   => price_format($data_post['sell_price'][$v]),
                    //'weight'       => $data_post['weight'][$v],
                    'minimum'      => $data_post['minimum'][$v],
                );
                $sk       = array();
                //存在规格值
                if (!empty($spec_name)) {
                    foreach ($spec_name as $val => $key) {
                        $sk[] = array('name' => $key, 'type' => (int)$data_post['spec_name_type'][$val], 'value' => str_replace('"', "'", $spec_value[$val][$v]), 'note' => $spec_value_note[$val][$v],);
                    }
                    $sku_data['value'] = ch_json_encode($sk);
                } else {
                    $sku_data['value'] = '';
                }
                if (!empty($data_post['sku_id'][$v])) {
                    //修改数据
                    $this->loop_model->update_id('goods_sku', $sku_data, $data_post['sku_id'][$v]);
                } else {
                    //增加数据
                    $this->loop_model->insert('goods_sku', $sku_data);
                }
            }

            //商品图片处理
            $this->loop_model->delete_where('goods_image', array('goods_id' => $goods_id));
            if (!empty($data_post['image_list'])) {
                foreach ($data_post['image_list'] as $k) {
                    $image_data = array(
                        'goods_id' => $goods_id,
                        'url'      => $k,
                    );
                    $this->loop_model->insert('goods_image', $image_data);
                }
            }

            //处理商品扩展属性
            foreach ($data_post as $val => $key) {
                if (strpos($val, 'attr_id_') !== false) {
                    $attr_data[ltrim($val, 'attr_id_')] = $key;
                }
            }
            $this->loop_model->delete_where('goods_attr', array('goods_id' => $goods_id));
            if (!empty($attr_data)) {
                foreach ($attr_data as $val => $key) {
                    $attrData = array(
                        'goods_id'   => $goods_id,
                        'model_id'   => $update_data['model_id'],
                        'attr_id'    => $val,
                        'attr_value' => is_array($key) ? join(',', $key) : $key
                    );
                    if ($attrData['attr_value'] != '') {
                        $this->loop_model->insert('goods_attr', $attrData);
                    }
                }
            }
            return 'y';
        } else {
            return '信息保存失败';
        }
    }

    /**
     * 根据指定条件搜索商品信息
     * @param array $data_post 条件
     * @param int   $cache     是否缓存,不为空时需要
     * @param int   $screening 是否需要筛选项,不为空时需要
     * @return array
     */
    public function search($where_data = array(), $cache = '')
    {
        $is_cache = '';
        if (!empty($cache)) {
            $cache_name = md5(json_encode($where_data));
            $reslut_array = cache('get', $cache_name);
            if (!empty($reslut_array)) {
                $is_cache = 'in';
                return $reslut_array;
            }
        }

        if (empty($is_cache)) {
            $cat_id        = $where_data['cat_id'];//分类id
            if (!is_array($cat_id)) {
                $this->load->model('goods/category_model');
                $cat_id = $this->category_model->get_reid_down($cat_id);
            }
            //*******************************************************
            //查询对应的商品start**************************************
            //*******************************************************
            $this->db->from('goods as g');
            $this->db->select('g.id,name,sub_desc,cat_id,sell_price,market_price,image,store_nums,unit,favorite,comments,sale,vedio');

            //搜索条件
            if (!empty($cat_id)) $this->db->where_in('g.cat_id', $cat_id);
            $this->db->where('g.status', 0);
            $this->db->order_by('sortnum', 'asc');

            //分页
            $page = (int)$where_data['page'];//是否有传入参数
            if (empty($page)) $page = (int)$this->input->get_post('per_page', true);//接收url分页
            if (empty($page)) $page = 1;
            if (empty($limit)) $limit = config_item('goods_list_pagesize');
            $this->db->limit($limit, $limit * ($page - 1));
            $query      = $this->db->get();
            $goods_data = $query->result_array();//echo $this->db->last_query()."<br>";

            $this->load->model('goods/goods_sum_model');
            foreach ($goods_data as $key) {
                $key['sell_price']   = format_price($this->goods_sum_model->sku_member_group_price($key));
                $key['market_price'] = format_price($key['market_price']);
                $goods_list[]        = $key;
            }
            //查询对应的商品end

            //*******************************************************
            //查询对应的商品总数start**********************************
            //*******************************************************
            $this->db->from('goods as g');
            //搜索条件
            if (!empty($cat_id)) $this->db->where_in('g.cat_id', $cat_id);
            //if (!empty($min_price)) $this->db->where('g.sell_price>=', $min_price);
            //if (!empty($max_price)) $this->db->where('g.sell_price<=', $max_price);
            //if (!empty($keyword)) $this->db->like('g.name', $keyword);
            //if (!empty($is_hot)) $this->db->where('g.is_hot', $is_hot);
            //if (!empty($is_new)) $this->db->where('g.is_new', $is_new);
            //if (!empty($is_flag)) $this->db->where('g.is_flag', $is_flag);
            //规格属性筛选start
            if (!empty($where_data['attr'])) {
                $where_attr = array_filter($where_data['attr']);
                if (!empty($where_attr)) {
                    $attr_sql = '';
                    foreach ($where_attr as $val => $key) {
                        $attr_sql[] = "(attr_id = $val and find_in_set('$key',attr_value))";
                    }
                    $this->db->join("(select * from " . $this->db->dbprefix('goods_attr') . " where " . join(' or ', $attr_sql) . " group by goods_id having count(goods_id)>=" . count($attr_sql) . ") as ga", 'ga.goods_id=g.id');
                }
            }
            //规格属性筛选end
            //规格属性筛选end
            $this->db->where('g.status', 0);
            $goods_count = $this->db->count_all_results();
            $page_count  = ceil($goods_count / $limit);

            $reslut_array = array('goods_list' => $goods_list, 'page_count' => $page_count);
            if($cache!='') {
                cache('save', $cache_name, $reslut_array);
            }
        }
        return $reslut_array;
    }

    /**
     * 计算商品的价格区间
     * @param $min      int  最小价格
     * @param $min      int  最大价格
     * @param $show_num 展示分组最大数量
     * @return array    价格区间分组
     */
    public function get_goods_price($min, $max, $show_num = 5)
    {
        if ($min <= 0) {
            $min_price = 1;
            $result    = array('0-' . $min_price);
        } else {
            $min_price = floor($min);
            $result    = array('1-' . $min_price);
        }

        //价格计算
        $per_price = floor(($max - $min_price) / ($show_num - 1));

        if ($per_price > 0) {
            for ($add_price = $min_price + 1; $add_price < $max;) {
                $step_price = $add_price + $per_price;
                $step_price = substr(intval($step_price), 0, 1) . str_repeat('9', (strlen(intval($step_price)) - 1));
                $result[]   = $add_price . '-' . $step_price;
                $add_price  = $step_price + 1;
            }
        }
        return $result;
    }

}
