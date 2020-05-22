<?php

namespace Payment\Gateways\Union;

use Payment\Contracts\IGatewayRequest;
use Payment\Exceptions\GatewayException;
use Payment\Helpers\ArrayUtil;
use Payment\Payment;

/**
 * @package Payment\Gateways\Union
 * @author  : xiaowei
 * @date    : 2020/4/30 3:12 PM
 * @version : 1.0.0
 * @desc    : 手机网站支付预授权完成接口
 **/
class PreauthFinish extends UnionBaseObject implements IGatewayRequest
{
    const METHOD = 'gateway/api/backTransReq.do';

    const VERSION = '5.1.0';//版本号

    //const SIGN_METHOD = '01';//签名方法，非对称签名： 01

    /**
     * 组装参数
     * @param array $requestParams
     * @return mixed
     */
    protected function getSelfParams(array $requestParams)
    {
        $nowTime    = time();

        $totalFee  = $requestParams['amount'] ? bcmul($requestParams['amount'], 100, 0) : 0;

        $bizContent = array(
            //以下信息非特殊情况不需要改动
            'version' => self::VERSION,                 //版本号
            'txnType' => '03',				      //交易类型
            'txnSubType' => '00',				  //交易子类
            'bizType' => '000201',				  //业务类型
            'backUrl' => self::$config->get('notify_url', ''),	  //后台通知地址
            'signMethod' => $this->signType,	              //签名方法
            'channelType' => $requestParams['channel_type'] ?? '08',	 //渠道类型，07-PC，08-手机
            'accessType' => '0',		          //接入类型  0：商户直连接入  1：收单机构接入 2：平台商户接入
            //TODO 以下信息需要填写
            'orderId' => $requestParams['trade_no'] ?? '',	//商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则
            'origQryId' => $requestParams["transaction_id"] ?? '', //原预授权的queryId，可以从预授权的查询接口或者通知接口中获取
            'txnTime' => date('YmdHis', $nowTime),	//订单发送时间，格式为YYYYMMDDhhmmss
            'txnAmt' => $totalFee,	//交易金额，单位分 ，范围为预授权金额的0-115%

        );

        return $bizContent;
    }

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
            //状态验证

            //签名验证
            if (isset($resArr['signature']) && $this->verifySign($retArr) === false) {
                throw new GatewayException('check return data sign failed', Payment::SIGN_ERR, $retArr);
            }

            if ($retArr["respCode"] == self::REQ_SUC){
                //交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
                //TODO
                //echo "受理成功。<br>\n";
                return $retArr;
            } else if ($retArr["respCode"] == "03"
                || $retArr["respCode"] == "04"
                || $retArr["respCode"] == "05" ){
                //后续需发起交易状态查询交易确定交易状态
                //TODO
                //echo "处理超时，请稍微查询。<br>\n";
                throw new GatewayException(sprintf('code:%d, desc:%s', $retArr['respCode'], $retArr['respMsg']), Payment::GATEWAY_CHECK_FAILED, $retArr);
            } else {
                //其他应答码做以失败处理
                //TODO
                //echo "失败：" . $result_arr["respMsg"] . "。<br>\n";
                throw new GatewayException(sprintf('code:%d, desc:%s', $retArr['respCode'], $retArr['respMsg']), Payment::GATEWAY_CHECK_FAILED, $retArr);
            }

        } catch (GatewayException $e) {
            throw $e;
        }

    }


}
