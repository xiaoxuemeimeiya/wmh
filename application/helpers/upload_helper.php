<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * plupload文件上传
 * @param str $browse_button 上传按钮id
 * @param int $parameter     参数
 * @param int $sort_num      页面当前实例个数
 * @param int $init          是否自动实例化
 */
if (!function_exists('plupload')) {
    function plupload($browse_button = 'upload_file', $parameter = array(), $sort_num = '', $init = true)
    {
        if (!empty($parameter['width'])) $width = 'width: ' . $parameter['width'] . ',';//压缩后图片的宽度
        if (!empty($parameter['height'])) $height = 'height: ' . $parameter['height'] . ',';//压缩后图片的高度
        //是否裁剪0为不裁剪,1为裁剪
        $crop = 0;
        if (!empty($parameter['width']) && !empty($parameter['height'])) {
            $crop = 1;//定义了宽高后必须裁剪才能生效,否则高度不生效
        }

        //压缩品质
        $data_quality = (int)$parameter['quality'];
        $data_quality > 0 ? $quality = $data_quality : $quality = 100;

        //上传文件大小单位MB
        $data_size = (int)$parameter['size'];
        $data_size > 0 ? $size = $data_size . 'mb' : $size = 10 . 'mb';

        //上传文件数量
        $data_num = (int)$parameter['num'];
        $data_num > 0 ? $num = $data_num : $num = 1;
        $num > 1 ? $multi_selection = 1 : $multi_selection = 0;

        $upload_url = config_item('upload_url');//上传图片处理地址
        $CI         = &get_instance();
        $user_type  = $CI->uri->segment(1, 0);//用户类型,根据前端入口判断
        if ($init) $uploader_init = 'uploader' . $sort_num . '.init()';//是否自动实例化
        $html = <<<Eof
            <script type="text/javascript" src="/public/plupload/plupload.full.min.js"></script>
            <script type="text/javascript" src="/public/plupload/i18n/zh_CN.js"></script>
            <script language="JavaScript">
                var plupload_pic_num$sort_num = 0;//图片数量
                var now_up_pic_num$sort_num = 1;//当前上传的第几张
                //实例化一个plupload上传对象
                var uploader$sort_num = new plupload.Uploader({
                    browse_button : '$browse_button', //触发文件选择对话框的按钮，为那个元素id
                    url : '$upload_url?user_type=$user_type', //服务器端的上传页面地址
                    flash_swf_url : '/public/plupload/Moxie.swf', //swf文件，当需要使用swf方式进行上传时需要配置该参数
                    silverlight_xap_url : '/public/plupload/Moxie.xap', //silverlight文件，当需要使用silverlight方式进行上传时需要配置该参数
                    multi_selection : $multi_selection,
                    filters : {
                        max_file_size : '$size',
                        mime_types: [
                            {title : "Image files", extensions : "jpg,gif,png,jpeg"},
                        ]
                    },
                    resize: {
                      $width
                      $height
                      crop: $crop,
                      quality: $quality,
                      preserve_headers: false
                    },
                    init: {
                        //选择文件
                        FilesAdded: function(up,files){
                            //展示加载效果
                            var loading = '<div class="upload_loading"><div class="upload_loading_box"><div class="spinner"><div class="rect1 spinner_loading"></div> <div class="rect2 spinner_loading"></div> <div class="rect3 spinner_loading"></div> <div class="rect4 spinner_loading"></div> <div class="rect5 spinner_loading"></div><div class="upload_loading_progress">上传中...</div></div></div></div><style type="text/css">.upload_loading{position:fixed;z-index:999999999;top:0;left:0;width:100%;height:100%;text-align:center;background:rgba(0,0,0,.5);font-size:0}.upload_loading:before{content:"";display:inline-block;height:100%;vertical-align:middle;margin-right:-.25em}.upload_loading_box{display:inline-block;vertical-align:middle;width:80%;height:auto;position:relative}.spinner{margin:100px auto;width:100px;height:60px;text-align:center;font-size:10px}.spinner>.spinner_loading{background-color:#00a6ea;height:100%;width:6px;display:inline-block;-webkit-animation:stretchdelay 1.2s infinite ease-in-out;animation:stretchdelay 1.2s infinite ease-in-out}.spinner .rect2{-webkit-animation-delay:-1.1s;animation-delay:-1.1s}.spinner .rect3{-webkit-animation-delay:-1s;animation-delay:-1s}.spinner .rect4{-webkit-animation-delay:-.9s;animation-delay:-.9s}.spinner .rect5{-webkit-animation-delay:-.8s;animation-delay:-.8s}.upload_loading_progress { color: #00a6ea;}@-webkit-keyframes stretchdelay{0%,100%,40%{-webkit-transform:scaleY(.4)}20%{-webkit-transform:scaleY(1)}}@keyframes stretchdelay{0%,100%,40%{transform:scaleY(.4);-webkit-transform:scaleY(.4)}20%{transform:scaleY(1);-webkit-transform:scaleY(1)}}</style>';
                            files_num$sort_num = files.length;//上传文件个数
                            now_pic_num$sort_num = plupload_pic_num$sort_num+files_num$sort_num;//当前临时文件加一句上传的总数
                            //只限制一张图片的时候可以重复选择
                            if (now_pic_num$sort_num>$num && $num>1) {
                                for (var i in files) {
                                    uploader$sort_num.removeFile(files[i].id);//删除文件列队中的文件
                                }
                                layer.msg('最多只能上传"$num"张图片');
                            } else {
                                plupload_pic_num$sort_num = plupload_pic_num$sort_num+files_num$sort_num;
                                $('body').append(loading);
                                uploader$sort_num.start();
                            }
                        },
                        //单个文件上传完成
                        FileUploaded: function(up,file,result){
                            now_up_pic_num$sort_num++;
                            $('.upload_loading_progress').text('第'+now_up_pic_num$sort_num+'张0%');
                            show_plupload$sort_num($.parseJSON(result.response));//成功回调函数
                        },
                        //全部文件上传完成
                        UploadComplete: function(up,files){
                            now_up_pic_num$sort_num = 1;//再次上传从1开始计数
                            $('.upload_loading').remove();//关闭加载效果
                        },
                        //上传进度
                        UploadProgress: function(up,files,total){
                            $('.upload_loading_progress').text('第'+now_up_pic_num$sort_num+'张'+files.percent+'%');//上传进度
                        },
                        //返回错误
                        Error: function(up,err){
                            $('.upload_loading').remove();//关闭加载效果
                            layer.msg(err.message);
                        }
                    }
                });
                $uploader_init
            </script>
Eof;
        echo $html;
    }
}

/**
 * ajaxfileupload文件上传
 * @param str $browse_button 上传按钮id
 * @param int $size          上传文件大小
 */
if (!function_exists('ajax_upload')) {
    function ajax_upload()
    {
        $upload_url = config_item('upload_url');
        $CI         = &get_instance();
        $user_type  = $CI->uri->segment(1, 0);//用户类型,根据前端入口判断
        $html       = <<<Eof
            <script type="text/javascript" src="/public/js/ajaxfileupload.js"></script>
            <script type="text/javascript" src="/public/js/exif.js"></script>
            <script language="JavaScript">
                //上传缩略图(文件域名称),宽度限制,高度限制,是否裁剪
                function upload_file(obj, upfile_width, upfile_height, crop){
                    var upfile_name = $(obj).attr('name');
                    //展示加载效果
                    var loading = '<div class="upload_loading"><div class="upload_loading_box"><div class="spinner"><div class="rect1 spinner_loading"></div> <div class="rect2 spinner_loading"></div> <div class="rect3 spinner_loading"></div> <div class="rect4 spinner_loading"></div> <div class="rect5 spinner_loading"></div><div class="upload_loading_progress">上传中...</div></div></div></div><style type="text/css">.upload_loading{position:fixed;z-index:999999999;top:0;left:0;width:100%;height:100%;text-align:center;background:rgba(0,0,0,.5);font-size:0}.upload_loading:before{content:"";display:inline-block;height:100%;vertical-align:middle;margin-right:-.25em}.upload_loading_box{display:inline-block;vertical-align:middle;width:80%;height:auto;position:relative}.spinner{margin:100px auto;width:100px;height:60px;text-align:center;font-size:10px}.spinner>.spinner_loading{background-color:#00a6ea;height:100%;width:6px;display:inline-block;-webkit-animation:stretchdelay 1.2s infinite ease-in-out;animation:stretchdelay 1.2s infinite ease-in-out}.spinner .rect2{-webkit-animation-delay:-1.1s;animation-delay:-1.1s}.spinner .rect3{-webkit-animation-delay:-1s;animation-delay:-1s}.spinner .rect4{-webkit-animation-delay:-.9s;animation-delay:-.9s}.spinner .rect5{-webkit-animation-delay:-.8s;animation-delay:-.8s}.upload_loading_progress { color: #00a6ea;}@-webkit-keyframes stretchdelay{0%,100%,40%{-webkit-transform:scaleY(.4)}20%{-webkit-transform:scaleY(1)}}@keyframes stretchdelay{0%,100%,40%{transform:scaleY(.4);-webkit-transform:scaleY(.4)}20%{transform:scaleY(1);-webkit-transform:scaleY(1)}}</style>';
		            $('body').append(loading);
		            //获取图片的exif信息
		            var file = obj.files['0'];
                    EXIF.getData(file, function() {
                        var orientation = EXIF.getTag(this, 'Orientation');//图片方向
                        $.ajaxFileUpload({
                            url:'$upload_url?user_type=$user_type&file_name='+upfile_name+'&width='+upfile_width+'&height='+upfile_height+'&crop='+crop+'&orientation='+orientation,
                            type:'post',
                            secureuri:false,
                            fileElementId:upfile_name,
                            dataType: 'json',
                            success: function (data) {
                                $('.upload_loading').remove();//关闭加载效果
                                if(data.status == 'success') {
                                    show_upload(upfile_name, data.url);//展示图片
                                } else {
                                    layer.msg(data.error)
                                    return false;
                                }
                            }
                        });
                    });
                }
            </script>
Eof;
        echo $html;
    }
}
