<?php

defined('BASEPATH') OR exit('No direct script access allowed');

//开启调试
$CI = &get_instance();
$CI->output->enable_profiler(false);

/**
 * 缓存操作
 * @param str $type
 * @param str $key
 * @param str $value
 * @param int $times
 */
if (!function_exists('cache')) {
    function cache($type = 'save', $name = false, $data = '', $save_time = '600')
    {
        $cache_type = config_item('cache_type');
        $cache_type != '' ? $cache_type = $cache_type : $cache_type = 'file';
        if (!empty($name)) {
            $CI = &get_instance();
            $CI->load->driver('cache');
            $name = md5($name);
            switch ($type) {
                case 'get':
                    $data = $CI->cache->$cache_type->get($name);
                    return $data;
                    break;
                case 'save':
                    $CI->cache->$cache_type->save($name, $data, $save_time);
                    break;
                case 'del':
                    $CI->cache->$cache_type->delete($name);
                    break;
                case 'clean':
                    $this->cache->clean();
                    break;
            }
        }
    }
}

/**
 ***************************************************************
 * curl请求操作
 ***************************************************************
 */

/**
 * curl以get方式请求
 */
if (!function_exists('curl_get')) {
    function curl_get($url)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}

/**
 * curl以post放上请求
 * @url  提交的地址
 * @data 提交数据
 */
if (!function_exists('curl_post')) {
    function curl_post($url, $data)
    {
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//设置链接
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置是否返回信息
        /*curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:text/json;charset=utf-8',
            'Content-Length:' . strlen($data)
        ));//设置HTTP头*/
        curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//POST数据
        $res = curl_exec($ch);//接收返回信息
        curl_close($ch); //关闭curl链接
        return $res;
    }
}

/**
 * 获取版权信息
 */
if (!function_exists('get_copyright')) {
    function get_copyright()
    {
        return 'Copyright © 2018';
    }
}

/**
 ***************************************************************
 * smarty模板操作
 ***************************************************************
 */

/**
 * smarty 加载模板
 * @param str $tmp_name 模板地址
 */
if (!function_exists('display')) {
    function display($tmp_name = FALSE)
    {
        if ($tmp_name !== FALSE) {
            $CI = &get_instance();
            $CI->load->library('my_smarty');
            $CI->my_smarty->display(get_web_type() . $tmp_name);
        }
    }
}

/**
 * smarty 加载变量
 * @param str $name 变量名
 * @param str $data 变量值
 */
if (!function_exists('assign')) {
    function assign($name = FALSE, $data = FALSE)
    {
        if ($name !== FALSE) {
            $CI = &get_instance();
            $CI->load->library('my_smarty');
            $CI->my_smarty->assign($name, $data);
        }
    }
}


/**
 ***************************************************************
 * url操作
 ***************************************************************
 */


/**
 * 得到当前页面的网址
 */
if (!function_exists('get_now_url')) {
    function get_now_url()
    {
        $domain = $_SERVER['HTTP_HOST'];
        if (!empty($_SERVER['HTTPS'])) {
            $pageURL = 'https://' . $domain;
        } else {
            $pageURL = 'http://' . $domain;
        }
        $pageURL .= $_SERVER["REQUEST_URI"];
        return $pageURL;
    }
}

/**
 * 返回错误信息json格式
 * @param str $info   提示信息
 * @param str $status 状态
 * @return json
 */
if (!function_exists('error_json')) {
    function error_json($info = '信息错误', $status = 'n')
    {
        if ($info == 'y') {
            exit(ch_json_encode(array('status' => 'y')));
        } else {
            if (is_array($info)) {
                $status = 'y';
                if (!empty($info['status'])) {
                    $status = $info['status'];
                    unset($info['status']);
                }
                $res = array(
                    'result' => $info,
                    'status' => $status,
                );
                exit(ch_json_encode($res));
            } else {
                exit(ch_json_encode(array('info' => $info, 'status' => $status)));
            }
        }
    }
}

/**
 * json 编码
 *
 * 解决中文经过 json_encode() 处理后显示不直观的情况
 * 如默认会将“中文”变成"\u4e2d\u6587"，不直观
 * 如无特殊需求，并不建议使用该函数，直接使用 json_encode 更好，省资源
 * json_encode() 的参数编码格式为 UTF-8 时方可正常工作
 *
 * @param array|object $data
 * @return array|object
 */
if (!function_exists('ch_json_encode')) {
    function ch_json_encode($data)
    {
        if (version_compare(phpversion(), '5.4.0') >= 0) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        $result = '';
        $result = json_encode($data);
        //对于中文的转换
        return preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $result);
    }
}

/**
 * 提示信息框,适应手机端页面
 * @param str $msg     提示信息
 * @param str $url     跳转链接
 * @param int $outtime 提示页面停留时间
 *
 */
if (!function_exists('msg')) {
    function msg($msg = FALSE, $url = '-1', $skip_time = '2000')
    {
        if ($msg !== FALSE) {
            if ($url == '-1' || $url == '') {
                $url_str = "history.back(-1)";
            } else {
                $url_str = "window.location.href='$url'";
            }
            //url等于stop时不允许跳转
            if ($url != 'stop') {
                $skip_url = '<script language="javascript">setTimeout("' . $url_str . '",' . $skip_time . ');</script>';
            } else {
                $skip_url = '';
            }
            echo <<<Eof
                <html>
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=uft-8"/>
						<title>提示信息</title>
						<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
					</head>
					<body>
						<script language="javascript" src="/public/js/jquery.js"></script>
						<script language="javascript" src="/public/js/layer/layer.js"></script>
						<script language="javascript">
						layer.msg('$msg');
						</script>
                        $skip_url
					</body>
                </html>
Eof;
            exit;
        }
    }
}

/**
 * 数组转换成连接
 * @param array $data 转换数据
 */
if (!function_exists('array_to_link')) {
    function array_to_link($data = '', $search_rep = '')
    {
        if (is_array($data)) {
            $suffix = '&';
            $link   = '?';
            foreach ($data as $val => $key) {
                $link .= $suffix . $val . '=' . $key;
            }
            return $link;
        } else {
            return false;
        }
    }
}

/**
 * 搜索条件数组转换成连接(并替换已经存在的数据)
 * @param array $data       转换数据
 * @param array $search_rep 需要替换连接中的数据
 */
if (!function_exists('search_array_to_link')) {
    function search_array_to_link($data = '', $search_rep = '')
    {
        $link = get_now_url();
        if (strpos($link, '?')) {
            $now_url = explode('?', $link);
            $link    = $now_url[0] . '?';
        } else {
            $link = $link . '?';
        }
        if (is_array($data)) {
            $suffix = '&';
            foreach ($data as $val => $key) {
                if (is_array($key)) {
                    foreach ($key as $v => $k) {
                        //是否存在重复数据存在的话用新数据替换并删除替换数据的对应数据
                        if (!empty($search_rep)) {
                            if (isset($search_rep[$val . '[' . $v . ']'])) {
                                $k = $search_rep[$val . '[' . $v . ']'];
                                unset($search_rep[$val . '[' . $v . ']']);
                            }
                        }
                        $link .= $suffix . $val . '[' . $v . ']' . '=' . $k;
                    }
                } else {
                    //是否存在重复数据存在的话用新数据替换并删除替换数据的对应数据
                    if (!empty($search_rep)) {
                        if (isset($search_rep[$val])) {
                            $key = $search_rep[$val];
                            unset($search_rep[$val]);
                        }
                    }
                    $link .= $suffix . $val . '=' . $key;
                }
            }
            //不存在的话直接添加
            if (!empty($search_rep)) {
                foreach ($search_rep as $val => $key) {
                    $link .= $suffix . $val . '=' . $key;
                }
            }
        }
        return $link;
    }
}


/**
 * 截取中文字符串
 * @param str $string 要截取的字符串
 * @param int $length 长度
 * @param str $dian   超出后显示
 */
if (!function_exists('cn_substr')) {
    function cn_substr($string, $length, $dian = '')
    {
        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $info);
        $j        = '';
        $wordscut = '';
        for ($i = 0; $i < count($info[0]); $i++) {
            $wordscut .= $info[0][$i];
            $j = ord($info[0][$i]) > 127 ? $j + 2 : $j + 1;
            if ($j > $length - 3) {
                return $wordscut . $dian;
            }
        }
        return join('', $info[0]);
    }
}

/**
 * 生成一个随机数（随机数里面没有0放置在int类型时首位为0不能保存）
 * @param str $type
 * @param int $nums
 */
if (!function_exists('get_rand_num')) {
    function get_rand_num($type = 'str', $nums = '6')
    {


        if ($type == 'str') {
            $str = "abcdefghjkmnpqrstuvwsyzABCDEFGHJKMNPQRSTUVWSYZ123456789";
        } else {
            $str = "123456789";
        }
        $rand_num = '';
        for ($i = 0; $i < $nums; $i++) {
            $rand_num .= substr($str, rand(0, strlen($str) - 1), 1);
        }
        return $rand_num;
    }
}

/**
 * 判断是否是post提交
 */
if (!function_exists('is_post')) {
    function is_post()
    {
        $CI = &get_instance();
        if (strtolower($CI->input->server('REQUEST_METHOD')) == 'post') {
            return true;
        } else {
            return false;
        }
    }
}


/**
 * 根据地区id获取中文
 * @param array $area_id  省市区代码
 * @param str   $join_str 多个之间隔断符号
 */
if (!function_exists('get_area_name')) {
    function get_area_name($area_id, $join_str = '')
    {
        if (!empty($area_id)) {
            $CI = &get_instance();
            $CI->load->model('areas_model');
            $area_name = $CI->areas_model->get_name($area_id);
            if (!empty($area_name)) {
                return join($join_str, $area_name);
            }
            return false;
        }
    }
}

/**
 * 根据中文获取地区id
 * @param array $area_name  省市区名称
 * @param str   $join_str 多个之间隔断符号
 */
if (!function_exists('get_area_id')) {
    function get_area_id($area_name, $join_str = '')
    {
        if (!empty($area_name)) {
            $CI = &get_instance();
            $CI->load->model('areas_model');
            $area_id = $CI->areas_model->get_id($area_name);
            if (!empty($area_id)) {
                return join($join_str, $area_id);
            }
            return false;
        }
    }
}


/**
 * 加载一个编辑器
 * @param str $name   名称
 * @param str $data   填充数据
 * @param int $width  宽度
 * @param int $height 高度
 * @param str $type   模式
 */
if (!function_exists('load_editer')) {
    function load_editer($name = 'desc', $data = FALSE, $width = 700, $height = 300, $type = 'simple')
    {
        $upload_url = site_url('/api/upload/editor_upload');
        $CI         = &get_instance();
        $user_type  = $CI->uri->segment(1, 0);//用户类型,根据前端入口判断
        if ($type == 'simple') {
            //是否精简模式
            $items = "items : ['source', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline','removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist','insertunorderedlist', '|', 'emoticons', 'image', 'multiimage', 'link'],";
        }
        echo <<<Eof
                <!-- 加载编辑器的容器 -->
                <textarea name="$name" id="$name">$data</textarea>
                <script>
                    KindEditor.ready(function(K) {
                        window.editor = K.create('#$name',{
                            width : '$width',
                            height: '$height',
                            uploadJson : '$upload_url?user_type=$user_type',
                            fileManagerJson : '$upload_url?user_type=$user_type',
                            allowFileManager : true,
                            $items
                            afterBlur: function () { this.sync(); }
                        });
                    });
                </script>
Eof;

    }
}


/**
 * show_adv 加载广告
 * @param int $position_id 广告位id
 * @param str $html_start  开始html标签
 * @param str $html_end    结束html标签
 */
if (!function_exists('show_adv')) {
    function show_adv($position_id = FALSE, $html_start = '', $html_end = '')
    {
        if ($position_id !== FALSE) {
            $CI = &get_instance();
            $CI->load->model('tool/adv_model');
            echo $CI->adv_model->get_adv_position($position_id, $html_start, $html_end);
        }
    }
}

/**
 * 格式化价格,将价格转换成分
 * @param float $price 价格
 */
if (!function_exists('price_format')) {
    function price_format($price = FALSE)
    {
        if ($price !== FALSE) {
            return $price * 100;
        }
    }
}

/**
 * 价格格式化,将分转换成价格
 * @param float $price 价格
 */
if (!function_exists('format_price')) {
    function format_price($price = FALSE)
    {
        $price = (int)$price;
        if ($price !== FALSE) {
            return round($price / 100, 2);
        }
    }
}

/**
 * 获取客户设备类型
 * @return string web,mobile,weixin
 */
if (!function_exists('get_client')) {
    function get_client()
    {
        $CI         = &get_instance();
        $agent      = $CI->input->user_agent();
        $mobileList = array('Android', 'iPhone', 'phone', 'ipad');

        if (strpos($agent, 'MicroMessenger') !== false) {
            return 'weixin';
        }

        foreach ($mobileList as $val) {
            if (stripos($agent, $val) !== false) {
                return 'mobile';
            }
        }
        return 'web';
    }
}


/**
 * 加密字符串
 * @param str $str 需要加密的字符串
 * @return string
 */
if (!function_exists('encrypt')) {
    function encrypt($str)
    {
        if (!empty($str)) {
            $CI = &get_instance();
            $CI->load->library('encryption');
            return $CI->encryption->encrypt($str);
        }
        return false;
    }
}

/**
 * 解密字符串
 * @param str $str 需要解密的字符串
 * @return string
 */
if (!function_exists('decrypt')) {
    function decrypt($str)
    {
        if (!empty($str)) {
            $CI = &get_instance();
            $CI->load->library('encryption');
            return $CI->encryption->decrypt($str);
        }
        return false;
    }
}

/**
 * 获取指定的url段的名称
 * param str $num 要获取的段
 */
if (!function_exists('get_segment')) {
    function get_segment($num)
    {
        if (!empty($num)) {
            $CI = &get_instance();
            return $CI->uri->segment($num);
        }
    }
}

/**
 * 获取当前的web网站类型(web/mobile)
 */
if (!function_exists('get_web_type')) {
    function get_web_type()
    {
        $CI       = &get_instance();
        $web_type = $CI->uri->segment(1);//根据网址的第一段来区分是web还是mobile
        if ($web_type != 'mobile' && $web_type != 'manager' && $web_type != 'seller') {
            $web_type = 'web';
        }
        return $web_type;
    }
}

/**
 * 生成图片缩略图并返回地址
 * @param str $url    图片地址
 * @param int $width  图片宽
 * @param int $height 图片高
 * @return string
 */
if (!function_exists('image_thumb')) {
    function image_thumb($url, $width = 0, $height = 0)
    {
        //网络地址直接返回
        if (strpos($url, 'http://') === false) {
            if (!empty($url) && !empty($width) && !empty($height)) {
                $CI = &get_instance();
                $CI->load->model('upload_model');
                $thumb_url = $CI->upload_model->image_thumb($url, $width, $height);
                return site_url('/api/pic?url=' . $thumb_url);
            }
        }
        return $url;
    }
}

/**
 * 格式化友好显示时间
 * @param int $time 需要转换的时间
 * @return string
 */
if (!function_exists('format_time')) {
    function format_time($time)
    {
        $now   = time();
        $day   = date('Y-m-d', $time);
        $today = date('Y-m-d');

        $dayArr   = explode('-', $day);
        $todayArr = explode('-', $today);

        //距离的天数，这种方法超过30天则不一定准确，但是30天内是准确的，因为一个月可能是30天也可能是31天
        $days = ($todayArr[0] - $dayArr[0]) * 365 + (($todayArr[1] - $dayArr[1]) * 30) + ($todayArr[2] - $dayArr[2]);
        //距离的秒数
        $secs = $now - $time;

        if ($todayArr[0] - $dayArr[0] > 0 && $days > 3) {//跨年且超过3天
            return date('Y-m-d', $time);
        } else {

            if ($days < 1) {//今天
                if ($secs < 60) return $secs . '秒前';
                elseif ($secs < 3600) return floor($secs / 60) . "分钟前";
                else return floor($secs / 3600) . "小时前";
            } else if ($days < 2) {//昨天
                $hour = date('h', $time);
                return "昨天" . $hour . '点';
            } elseif ($days < 3) {//前天
                $hour = date('h', $time);
                return "前天" . $hour . '点';
            } else {//三天前
                return date('m月d号', $time);
            }
        }
    }
}


/**
 * layerpage展示
 * @param str    $div_id     显示的容器id
 * @param int    $page_count 总页数
 * @param string $link       链接地址
 * @paran int $groups 连续显示分页数
 * @paran string $first 首页显示文字,。若不显示，设置false即可
 * @paran string $last 尾页显示文字,。若不显示，设置false即可
 * @paran string $prev 上一页文字若不显示，设置false即可
 * @paran string $next 下一页文字若不显示，设置false即可
 * @paran string $skip 是否跳页不显示，设置false即可
 *                           return array
 */
if (!function_exists('page_view')) {
    function page_view($div_id, $page_count, $link, $groups = '5', $first = '首页', $last = '尾页', $prev = '上一页', $next = '下一页', $skip = true, $skin = 'default')
    {
        $page_html = <<<eof
            <div class="$div_id" id="$div_id"></div>
            <script type="text/javascript" src="/public/js/laypage/laypage.js"></script>
            <script language="JavaScript">
                //分页函数
                laypage({
                    cont: '$div_id',
                    pages: $page_count,
                    curr: function(){
                        var page = location.search.match(/per_page=(\d+)/);
                        return page ? page[1] : 1;
                    }(),
                    first: '$first',
                    last: '$last',
                    prev: '$prev',
                    next: '$next',
                    skin: '$skin',
                    skip: '$skip',
                    groups:$groups,
                        jump: function(e, first){
                        if(!first){
                            location.href = '$link&per_page='+e.curr;
                        }
                    }
                });
            </script>
eof;
        return $page_html;
    }
}

/**
 * xss过滤函数
 * @param $string
 * @return string
 */
if (!function_exists('remove_xss')) {
    function remove_xss($string)
    {
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);

        $parm1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');

        $parm2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');

        $parm = array_merge($parm1, $parm2);

        for ($i = 0; $i < sizeof($parm); $i++) {
            $pattern = '/';
            for ($j = 0; $j < strlen($parm[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[x|X]0([9][a][b]);?)?';
                    $pattern .= '|(&#0([9][10][13]);?)?';
                    $pattern .= ')?';
                }
                $pattern .= $parm[$i][$j];
            }
            $pattern .= '/i';
            $string = preg_replace($pattern, '', $string);
        }
        return $string;
    }
}

/**
 * smarty万能获取数据列表
 * @param str   $model   表名
 * @param array $data    参数
 * @param int   $limit   分页大小
 * @param int   $page    页数
 * @param str   $orderby 排序
 * @param str   $cache   是否缓存
 */
if (!function_exists('ym_list')) {
    function ym_list($model, $where_data = array(), $limit = 0, $page = 1, $orderby = 'id desc', $cache = '')
    {
        if (!empty($model)) {
            $CI =& get_instance();
            $CI->load->model('loop_model');
            $list = $CI->loop_model->get_list($model, $where_data, $limit, $limit * ($page - 1), $orderby, $cache);//返回查询数据
            return $list;
        }
    }
}

/**
 * smarty获取商品列表
 * @param array $data         参数
 * @param int   $limit        分页大小
 * @param int   $page         页数
 * @param str   $orderby      排序
 * @param str   $orderby_type 排序顺序
 * @param str   $cache        是否缓存
 */
if (!function_exists('ym_goods_list')) {
    function ym_goods_list($where_data = array(), $limit = 0, $page = 1, $orderby = 'id', $orderby_type = 'desc', $cache = '')
    {
        if (!empty($where_data)) {
            $CI =& get_instance();
            $CI->load->model('goods/goods_model');
            if (!empty($limit)) $where_data['limit'] = $limit;
            if (!empty($page)) $where_data['page'] = $page;
            if (!empty($orderby)) $where_data['orderby'] = $orderby;
            if (!empty($orderby_type)) $where_data['orderby_type'] = $orderby_type;
            $list = $CI->goods_model->search($where_data, $cache);//返回查询数据
            return $list['goods_list'];
        }
    }
}