<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Upload_model extends CI_Model
{

    /**
     * 上传
     * @param str $file_name 文件域的名称
     * @param int $width     裁剪图片的宽度
     * @param int $height    裁剪图片的高度
     * @param int $crop      是否裁剪0为不裁剪,1为裁剪
     */
    public function upload($file_name = 'upfile', $width = '', $height = '', $crop = 0, $orientation = 0)
    {
        $width       = (int)$width;
        $height      = (int)$height;
        $crop        = (int)$crop;
        $orientation = (int)$orientation;
        //上传参数配置
        $config['upload_path'] = './uploads/' . date('Y', time()) . '/' . date('m', time()) . '/' . date('d', time()) . '/';
        if (!file_exists($config['upload_path'])) mkdir($config['upload_path'], 0777, true);
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['overwrite']     = FALSE;//是否覆盖同名文件
        $config['max_size']      = '60000';
        $config['encrypt_name']  = TRUE;//文件名随机
        $config['remove_spaces'] = TRUE;//将空格转换为下划线
        $config['detect_mime']   = TRUE;//检测mime类型
        $config['mod_mime_fix']  = TRUE;//有多个后缀名的文件将会添加一个下划线后缀
        //开始上传
        $this->load->library('upload', $config);

        //上传成功
        if ($this->upload->do_upload($file_name)) {
            $image_data = $this->upload->data();
            //gif图片不处理
            if (strtolower($image_data['file_ext']) != '.gif') {
                //开始旋转图片start
                if (!empty($orientation)) {
                    $rotation_angle = 0;
                    switch($orientation) {
                        case 3:
                            $rotation_angle = 180;
                            break;
                        case 6:
                            $rotation_angle = 270;
                            break;
                        case 8:
                            $rotation_angle = 90;
                            break;
                    }
                    if (!empty($rotation_angle)) {
                        $orientation_config['image_library']  = 'gd2';
                        $orientation_config['source_image']   = $image_data['full_path'];
                        $orientation_config['rotation_angle'] = $rotation_angle;//旋转角度
                        //开始处理图片
                        $this->load->library('image_lib');
                        $this->image_lib->initialize($orientation_config);
                        $this->image_lib->rotate();
                    }
                }
                //开始旋转图片end

                //开始处理图片start
                if (!empty($width) || !empty($height)) {
                    $image_config['image_library']  = 'gd2';
                    $image_config['source_image']   = $image_data['full_path'];
                    $image_config['create_thumb']   = TRUE;
                    $image_config['maintain_ratio'] = $crop;
                    $image_config['thumb_marker']   = '';
                    if (!empty($width)) $image_config['width'] = $width;
                    if (!empty($height)) $image_config['height'] = $height;
                    $image_config['quality']    = 80;//图片质量
                    $image_config['master_dim'] = 'auto';
                    $this->load->library('image_lib');
                    $this->image_lib->initialize($image_config);
                    if ($this->image_lib->resize()) {
                        //直接覆盖原来的文件(回和旋转的起冲突,只能覆盖)
                        /*$new_path                = explode('.', $image_data['file_name']);
                        $image_data['file_name'] = $new_path[0] . '_thumb.' . $new_path[1];//新文件地址*/
                    }
                }
                //开始处理图片end
            }

            $file_dir           = str_replace('./uploads', config_item('img_url') . '/uploads', $config['upload_path']);
            $res_data['url']    = $file_dir . $image_data['file_name'];
            $res_data['status'] = 'success';
        } else {
            //上传失败提示错误
            $res_data['error'] = $this->upload->display_errors('', '');
        }
        return $res_data;
    }

    /**
     * 生成缩略图
     * @param str $file_name 文件路径
     * @param int $width     宽度
     * @param int $height    高度
     */
    public function image_thumb($file_name, $width = 0, $height = 0)
    {
        if (!empty($file_name) && !empty($width) && !empty($height)) {
            //原始文件是否存在
            $file_name = '.' . $file_name;
            if (file_exists($file_name)) {
                $file_path = explode('/', $file_name);
                $type_path = explode('.', $file_path[count($file_path) - 1]);
                $prefix    = '_thumb_' . $width . '_' . $height;//新的文件前缀
                $new_name  = $type_path[0] . $prefix . '.' . $type_path[1];//新文件名
                unset($file_path[count($file_path) - 1]);//删除文件名称
                $app_path     = explode(DIRECTORY_SEPARATOR, APPPATH);
                $new_path     = str_replace('./uploads', 'cache/thumb', join('/', $file_path));//保存路径
                $new_file_url = $new_path . '/' . $new_name;

                $new_file_url_file = '/' . $app_path[count($app_path) - 2] . '/' . $new_file_url;
                if (!file_exists($new_file_url)) {
                    //文件不存在开始生成缩略文件
                    if (!file_exists(APPPATH . $new_path)) mkdir(APPPATH . $new_path, 0777, true);//路径不存在创建
                    $image_config['image_library']  = 'gd2';
                    $image_config['source_image']   = APPPATH . '.' . $file_name;//原始图片路径
                    $image_config['create_thumb']   = TRUE;//是否创建缩略图
                    $image_config['maintain_ratio'] = false;
                    $image_config['new_image']      = APPPATH . $new_path;//新文件目录
                    $image_config['thumb_marker']   = $prefix;//新文件后缀
                    if (!empty($width)) $image_config['width'] = $width;
                    if (!empty($height)) $image_config['height'] = $height;
                    $image_config['quality']    = 80;//图片质量
                    $image_config['master_dim'] = 'auto';
                    $this->load->library('image_lib');
                    $this->image_lib->initialize($image_config);
                    if (!$this->image_lib->resize()) {
                        return $file_name;
                    }
                }
                return $new_file_url_file;
            }
        }
        return $file_name;
    }
}
