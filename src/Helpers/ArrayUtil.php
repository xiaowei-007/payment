<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Helpers;

/**
 * Class ArrayUtil
 * @package Payment\Helpers
 * @author  : Leo
 * @date    : 2019/3/30 8:11 PM
 * @version : 1.0.0
 * @desc    : 处理数组的工具类
 *
 */
class ArrayUtil
{
    /**
     * 移除空值的key
     * @param $para
     * @return array
     * @author Leo
     */
    public static function paraFilter($para)
    {
        $paraFilter = [];
        foreach ($para as $key => $val) {
            if ($val === '' || $val === null) {
                continue;
            }
            if (!is_array($para[$key])) {
                $para[$key] = is_bool($para[$key]) ? $para[$key] : trim($para[$key]);
            }

            $paraFilter[$key] = $para[$key];
        }

        return $paraFilter;
    }

    /**
     * 删除一位数组中，指定的key与对应的值
     * @param array $inputs 要操作的数组
     * @param array|string $keys 需要删除的key的数组，或者用（,）链接的字符串
     * @return array
     */
    public static function removeKeys(array $inputs, $keys)
    {
        if (!is_array($keys)) {// 如果不是数组，需要进行转换
            $keys = explode(',', $keys);
        }

        if (empty($keys) || !is_array($keys)) {
            return $inputs;
        }

        $flag = true;
        foreach ($keys as $key) {
            if (array_key_exists($key, $inputs)) {
                if (is_int($key)) {
                    $flag = false;
                }
                unset($inputs[$key]);
            }
        }

        if (!$flag) {
            $inputs = array_values($inputs);
        }
        return $inputs;
    }

    /**
     * 对输入的数组进行字典排序
     * @param array $param 需要排序的数组
     * @return array
     * @author Leo
     */
    public static function arraySort(array $param)
    {
        ksort($param);
        reset($param);

        return $param;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param array $para 需要拼接的数组
     * @param bool $encode 是否需要URL编码
     * @return string
     * @throws \Exception
     */
    public static function createLinkString($para,$encode=false)
    {
        if (!is_array($para)) {
            throw new \Exception('必须传入数组参数');
        }

        reset($para);
        $arg = '';
        foreach ($para as $key => $val) {
            if (is_array($val)) {
                continue;
            }
            $vals = $encode==true ? $val : urldecode($val);
            $arg .= $key . '=' . $vals . '&';
        }
        //去掉最后一个&字符
        $arg && $arg = substr($arg, 0, -1);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }


    /**
     * 获取一个数组中某个key的值，如果key为不存在，返回默认值
     * @param array $arr
     * @param $key
     * @param string $default
     *
     * @return string
     */
    public static function get(array $arr, $key, $default = '')
    {
        if (isset($arr[$key]) && !empty($arr[$key])) {
            return $arr[$key];
        }

        return $default;
    }

    /**
     * 银联网关支付，生成html
     * @param $params
     * @param $reqUrl
     * @return string
     * @author: XiaoWei
     * @date 2020-05-09 16:31
     */
    public static function createAutoFormHtml($params, $reqUrl) {
        $encodeType = isset ( $params ['encoding'] ) ? $params ['encoding'] : 'UTF-8';
        $html = <<<EOT
                <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
                </head>
                <body onload="javascript:document.pay_form.submit();">
                <form id="pay_form" name="pay_form" action="{$reqUrl}" method="post">
EOT;
        foreach ( $params as $key => $value ) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        };
        $html .= <<<EOT
                   </form></body></html>
EOT;
        return $html;
    }

    /**
     * key1=value1&key2=value2转array
     * @param string $str key1=value1&key2=value2的字符串
     * @param bool $needUrlDecode 是否需要解url编码，默认不需要
     * @return array
     * @author: XiaoWei
     * @date 2020-05-11 17:19
     */
    public static function parseQString($str, $needUrlDecode=false){
        $result = array();
        $len = strlen($str);
        $temp = "";
        $curChar = "";
        $key = "";
        $isKey = true;
        $isOpen = false;
        $openName = "\0";
        for($i=0; $i<$len; $i++){
            $curChar = $str[$i];
            if($isOpen){
                if( $curChar == $openName){
                    $isOpen = false;
                }
                $temp .= $curChar;
            } elseif ($curChar == "{"){
                $isOpen = true;
                $openName = "}";
                $temp .= $curChar;
            } elseif ($curChar == "["){
                $isOpen = true;
                $openName = "]";
                $temp .= $curChar;
            } elseif ($isKey && $curChar == "="){
                $key = $temp;
                $temp = "";
                $isKey = false;
            } elseif ( $curChar == "&" && !$isOpen){
                self::putKeyValueToDictionary($temp, $isKey, $key, $result, $needUrlDecode);
                $temp = "";
                $isKey = true;
            } else {
                $temp .= $curChar;
            }
        }
        self::putKeyValueToDictionary($temp, $isKey, $key, $result, $needUrlDecode);
        return $result;
    }

    /**
     * @param $temp
     * @param $isKey
     * @param $key
     * @param $result
     * @param $needUrlDecode
     * @return bool
     * @author: XiaoWei
     * @date 2020-05-11 17:21
     */
    public static function putKeyValueToDictionary($temp, $isKey, $key, &$result, $needUrlDecode) {
        if ($isKey) {
            $key = $temp;
            if (strlen ( $key ) == 0) {
                return false;
            }
            $result [$key] = "";
        } else {
            if (strlen ( $key ) == 0) {
                return false;
            }
            if ($needUrlDecode)
                $result [$key] = urldecode ( $temp );
            else
                $result [$key] = $temp;
        }
    }


}
