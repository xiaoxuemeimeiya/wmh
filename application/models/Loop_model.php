<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Loop_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 添加
     * @param string $table 表名
     * @param array $data 添加数据
     * @param str $insert_batch 是否批量添加
     */
    public function insert($table, $data = FALSE, $insert_batch = '')
    {
        if ($data !== FALSE) {
            if ($insert_batch == '') {
                $this->db->insert($table, $data);
            } else {
                $this->db->insert_batch($table, $data);
            }
            $insert_id = $this->db->insert_id();
            if (!empty($insert_id)) {
                return $insert_id;
            } else {
                return $this->db->affected_rows();//没有主键id的返回影响的行数
            }
        }
    }

    /**
     * 根据id查询数据
     * @param string $table 表名
     * @param int $id 数据id
     * @return array
     */
    public function get_id($table, $id = FALSE, $select = "*")
    {
        $id = (int)$id;
        if ($id !== FALSE) {
            $this->db->select($select);
            $query = $this->db->get_where($table, array('id' => $id));//echo $this->db->last_query()."<br>";
            return $query->row_array();
        }
    }

    /**
     * 根据条件查询数据
     * @param string $table 表名
     * @param array $data
     * @return array
     */
    public function get_where($table, $data = FALSE, $select = "*", $orderby = '')
    {
        if ($data !== FALSE) {
            $this->db->select($select);
            //排序
            if (!empty($orderby)) {
                $this->db->order_by($orderby);
            }
            $this->db->limit(1);
            $query = $this->db->get_where($table, $data);//echo $this->db->last_query()."<br>";
            return $query->row_array();
        }
    }

    /**
     * 根据ID修改数据
     * @param string $table 表名
     * @param array $data 修改数据
     * @param array $id 数据id，也可以传入一个数组作为in修改
     * @return array
     */
    public function update_id($table, $data = FALSE, $id = FALSE)
    {
        if (!empty($data) && !empty($id)) {
            if (!empty($data['set'])) {
                foreach ($data['set'] as $k) {
                    $this->db->set($k[0], $k[1], false);
                }
                unset($data['set']);
            }
            //判断是否需要in条件
            if (is_array($id)) {
                foreach ($id as $k) {
                    $ids[] = (int)$k;
                }
                $this->db->where_in('id', $ids);
            } else {
                $id = (int)$id;
                $this->db->where('id', $id);
            }
            $this->db->update($table, $data);//echo $this->db->last_query();exit;
            return $this->db->affected_rows();
        }
    }

    /**
     * 根据条件修改数据
     * @param string $table 表名
     * @param array $data 修改数据
     * @param array $where 修改条件数组
     * @return array
     */
    public function update_where($table, $data = FALSE, $where = array())
    {
        if (!empty($data) && !empty($where)) {
            if (!empty($data['set'])) {
                foreach ($data['set'] as $k) {
                    $this->db->set($k[0], $k[1], false);
                }
                unset($data['set']);
            }
            $where_many_condition = array('where_in','or_where_in','where_not_in','or_where_not_in');
            $where_condition      = array('where','or_where','like','or_like','not_like','or_not_like');
            //判断条件
            foreach ($where as $val => $key) {
                //同一条件下有多个数组
                if (in_array($val, $where_many_condition)) {
                    if (!empty($key)) {
                        foreach ($key as $v=>$k) {
                            $this->db->$val($v, $k);
                        }
                    }
                } elseif (in_array($val, $where_condition)) {
                    if (!empty($key)) $this->db->$val($key);
                } else {
                    //不在上述条件内的直接使用where
                    $this->db->where(array($val=>$key));
                }
            }
            $this->db->update($table, $data);//echo $this->db->last_query();//exit;
            return $this->db->affected_rows();
        }
    }

    /**
     * 根据ID批量删除
     * @param array $id 数据id，也可以传入一个数组作为in修改
     * @return int
     */
    public function delete_id($table, $id = FALSE)
    {
        if (!empty($id)) {
            //判断是否需要in条件
            if (is_array($id)) {
                foreach ($id as $k) {
                    $ids[] = (int)$k;
                }
                $this->db->where_in('id', $ids);
            } else {
                $id = (int)$id;
                $this->db->where('id', $id);
            }
            $this->db->delete($table);//echo $this->db->last_query();exit;
            return $this->db->affected_rows();
        }
    }

    /**
     * 根据条件删除
     * @param array $where 条件数组
     * @return int
     */
    public function delete_where($table, $where = FALSE)
    {
        if (is_array($where)) {
            $where_many_condition = array('where_in','or_where_in','where_not_in','or_where_not_in');
            $where_condition      = array('where','or_where','like','or_like','not_like','or_not_like');
            //判断条件
            foreach ($where as $val => $key) {
                //同一条件下有多个数组
                if (in_array($val, $where_many_condition)) {
                    if (!empty($key)) {
                        foreach ($key as $v=>$k) {
                            $this->db->$val($v, $k);
                        }
                    }
                } elseif (in_array($val, $where_condition)) {
                    if (!empty($key)) $this->db->$val($key);
                } else {
                    //不在上述条件内的直接使用where
                    $this->db->where(array($val=>$key));
                }
            }
            $this->db->delete($table);//echo $this->db->last_query();exit;
            return $this->db->affected_rows();
        } else {
            return false;
        }
    }

    /**
     * 查询所有
     * @param string $table 表名
     * @param array $data 条件
     * @return array
     */
    public function get_list($table, $data = array(), $limit = 0, $offset = 0, $orderby = 'id desc', $cache = '')
    {
        $is_cache = '';
        $result = array();
        //开启缓存
        if($cache!='') {
            $cache_name = md5('list'.$table.json_encode($data).$limit.$offset.$orderby);
            $result = cache('get',$cache_name);
            if (!empty($result)) {
                $is_cache = 'in';
                return $result;
            }
        }
        if (empty($is_cache)) {
            $this->db->from($table);
            if (!empty($data)) {
                foreach ($data as $val => $key) {
                    switch ($val) {
                        case 'join':
                            if (!empty($key)) {
                                foreach ($key as $k) {
                                    $this->db->join($k[0], $k[1], $k[2]);
                                }
                            }
                            break;
                        case 'where_in':
                            if (!empty($key)) {
                                foreach ($key as $v=>$k) {
                                    $this->db->where_in($v, $k);
                                }
                            }
                            break;
                        case 'or_where_in':
                            if (!empty($key)) {
                                foreach ($key as $v=>$k) {
                                    $this->db->or_where_in($v, $k);
                                }
                            }
                            break;
                        case 'where_not_in':
                            if (!empty($key)) {
                                foreach ($key as $v=>$k) {
                                    $this->db->where_not_in($v, $k);
                                }
                            }
                            break;
                        case 'or_where_not_in':
                            if (!empty($key)) {
                                foreach ($key as $v=>$k) {
                                    $this->db->or_where_not_in($v, $k);
                                }
                            }
                            break;
                        case 'select':
                            if (!empty($key)) $this->db->$val($key, FALSE);
                            break;
                        case 'sql':
                            if (!empty($key)) $this->db->where($key);
                            break;
                        default:
                            if (!empty($key)) $this->db->$val($key);
                            break;//不满足以上条件直接调用
                    }
                }
            }
            //查询数量
            if (!empty($limit)) {
                $this->db->limit($limit, $offset);
            }
            //排序
            if (!empty($orderby)) {
                $this->db->order_by($orderby);
            }
            $query = $this->db->get();
            $result = $query->result_array();//echo $this->db->last_query();exit;
            if($cache!='') {
                cache('save', $cache_name, $result);
            }
        }
        return $result;
    }

    /**
     * 查询所有数据总数
     * @param string $table 表名
     * @param array $data 条件
     * @return array
     */
    public function get_list_num($table, $data = array(), $cache = '')
    {
        $is_cache = '';
        //开启缓存
        if($cache!='') {
            $cache_name = md5('list_num'.$table.json_encode($data));
            $result = cache('get',$cache_name);
            if (!empty($result)) {
                $is_cache = 'in';
                return $result;
            }
        }
        if (empty($is_cache)) {
            $this->db->from($table);
            if (!empty($data)) {
                foreach ($data as $val => $key) {
                    switch ($val) {
                        case 'join':
                            if (!empty($key)) {
                                foreach ($key as $k) {
                                    $this->db->join($k[0], $k[1], $k[2]);
                                }
                            }
                            break;
                        case 'where_in':
                            if (!empty($key)) {
                                foreach ($key as $v=>$k) {
                                    $this->db->where_in($v, $k);
                                }
                            }
                            break;
                        case 'or_where_in':
                            if (!empty($key)) {
                                foreach ($key as $v=>$k) {
                                    $this->db->or_where_in($v, $k);
                                }
                            }
                            break;
                        case 'where_not_in':
                            if (!empty($key)) {
                                foreach ($key as $v=>$k) {
                                    $this->db->where_not_in($v, $k);
                                }
                            }
                            break;
                        case 'or_where_not_in':
                            if (!empty($key)) {
                                foreach ($key as $v=>$k) {
                                    $this->db->or_where_not_in($v, $k);
                                }
                            }
                            break;
                        case 'select':
                            if (!empty($key)) $this->db->$val($key, FALSE);
                            break;
                        case 'sql':
                            if (!empty($key)) $this->db->where($key);
                            break;
                        default:
                            if (!empty($key)) $this->db->$val($key);
                            break;//不满足以上条件直接调用
                    }
                }
            }
            $result = $this->db->count_all_results();
            if($cache!='') {
                cache('save', $cache_name, $result);
            }
        }
        return $result;
    }

    /**
     * 查询数据表数据总数
     * @param string $table 表名
     * @return array
     */
    public function count_all($table, $cache = '')
    {
        if($cache!='') {
            $cache_name = md5('count_all'.$table);
            $result = cache('get',$cache_name);
            if (!empty($result)) {
                return $result;
            } else {
                $result = $this->db->count_all($table);
                cache('save', $cache_name, $result);
            }
        } else {
            $result = $this->db->count_all($table);
        }
        return $result;
    }
}
