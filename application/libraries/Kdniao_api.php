<?php
/**
 * @class Kdniao_api
 * @brief 快递鸟快递api查询接口
 */
class Kdniao_api
{
    public function __construct()
    {
        $this->submit_url = 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx';
    }

    /**
     *获取信息
     */
    public function get_traces($shipperCode, $logisticCode)
    {
        $post_data = array(
            'OrderCode'    => '',
            'ShipperCode'  =>$shipperCode,
            'LogisticCode' =>$logisticCode
        );

        $datas = array(
            'EBusinessID' => config_item('delivery_appid'),
            'RequestType' => '1002',
            'RequestData' => urlencode(json_encode($post_data)) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = self::encrypt(json_encode($post_data));

        $result = self::send_post($this->submit_url, $datas);

        $res_data = json_decode($result, true);
        if ($res_data['Success']) {
            $res = json_encode($res_data['Traces']);
        }
        return $res;
    }

    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    function send_post($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], 80);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data) {
        return urlencode(base64_encode(md5($data.config_item('delivery_appkey'))));
    }
}