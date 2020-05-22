<?php


namespace Payment\Gateways\Union;

use Payment\Contracts\IGatewayRequest;
use Payment\Exceptions\GatewayException;
use Payment\Helpers\ArrayUtil;
use Payment\Payment;

/**
 * @package Payment\Gateways\Union
 * @author  : xiaowei
 * @date    : 2020/5/11 8:27 PM
 * @version : 1.0.0
 * @desc    : 二维码消费（被扫）
 **/
class BarCharge extends UnionBaseObject implements IGatewayRequest
{
    const METHOD = 'gateway/api/backTransReq.do';

    const VERSION = '5.1.0';//版本号

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {

        try {
            $params = $this->buildParams($requestParams);
            $ret    = $this->post(sprintf($this->gatewayUrl, self::METHOD), $params);
            //没收到200应答的情况
            if(count($ret)<=0) {
                throw new GatewayException(sprintf('format cancel trade data get error, [%s]', 'result is null'), Payment::FORMAT_DATA_ERR, ['raw' => $ret]);
            }
            //把key1=value1&key2=value2转array
            $retArr = ArrayUtil::parseQString($ret);

            //签名验证
            if (isset($resArr['signature']) && $this->verifySign($retArr) === false) {
                throw new GatewayException('check return data sign failed', Payment::SIGN_ERR, $retArr);
            }

            if ($retArr["respCode"] == self::REQ_SUC){
                //echo "成功。<br>\n";
                return $retArr;
            } else {
                //echo "失败：" . $retArr["respMsg"] . "。<br>\n";
                throw new GatewayException(sprintf('code:%d, desc:%s', $retArr['respCode'], $retArr['respMsg']), Payment::GATEWAY_CHECK_FAILED, $retArr);
            }

        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    protected function getSelfParams(array $requestParams)
    {
        $totalFee  = $requestParams['amount'] ? bcmul($requestParams['amount'], 100, 0) : 0;

        $bizContent = [
            //以下信息非特殊情况不需要改动
            'version' => self::VERSION,                 //版本号
            'txnType' => '01', //交易类型
            'txnSubType' => '06',				  //交易子类 06：二维码消费
            'bizType' => '000000',				  //业务类型
            'backUrl' => self::$config->get('notify_url', ''),	  //后台通知地址
            'signMethod' => $this->signType,	              //签名方法
            'channelType' => $requestParams['channel_type'] ?? '08',	 //渠道类型，07-PC，08-手机
            'accessType' => '0',		          //接入类型  0：商户直连接入  1：收单机构接入 2：平台商户接入
            'currencyCode' => self::$config->get('currency_code', '156'),//交易币种，境内商户固定156
            //TODO 以下信息需要填写
            //'merId' => self::$config->get('app_id', ''),		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'orderId' => $requestParams['trade_no'] ?? '',	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime' => date('YmdHis'),	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt' => $totalFee,	//交易金额，单位分，此处默认取demo演示页面传递的参数

            'termId' => $requestParams["terminal_id"] ?? '',  //终端号，8位数字字母
            'qrNo'  => $requestParams['auth_code'] ?? '',  //C2B码,付款码


            'riskRateInfo' =>'{commodityName='.$requestParams['trade_no'].'}',//风控信息域

            // 请求方保留域，
            // 透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据。
            // 出现部分特殊字符时可能影响解析，请按下面建议的方式填写：
            // 1. 如果能确定内容不会出现&={}[]"'等符号时，可以直接填写数据，建议的方法如下。
            //    'reqReserved' =>'透传信息1|透传信息2|透传信息3',
            // 2. 内容可能出现&={}[]"'符号时：
            // 1) 如果需要对账文件里能显示，可将字符替换成全角＆＝｛｝【】“‘字符（自己写代码，此处不演示）；
            // 2) 如果对账文件没有显示要求，可做一下base64（如下）。
            //    注意控制数据长度，实际传输的数据长度不能超过1024位。
            //    查询、通知等接口解析时使用base64_decode解base64后再对数据做后续解析。
            //    'reqReserved' => base64_encode('任意格式的信息都可以'),
            'reqReserved' => base64_encode($requestParams['return_param']),//请求方保留域，商户自定义保留域，交易应答时会原样返回


        ];

        return $bizContent;
    }


}
