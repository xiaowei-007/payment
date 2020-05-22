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
 * @desc    : 该接口提供订单的查询
 **/
class TradeQuery extends UnionBaseObject implements IGatewayRequest
{
    const METHOD = 'gateway/api/queryTrans.do';

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
            //状态验证

            if ($retArr["respCode"] == self::REQ_SUC){
                if ($retArr["origRespCode"] == self::REQ_SUC){
                    //交易成功
                    //TODO

                    //签名验证
                    if (isset($resArr['signature']) && $this->verifySign($retArr) === false) {
                        throw new GatewayException('check return data sign failed', Payment::SIGN_ERR, $retArr);
                    }
                    return $retArr;
                } else if ($retArr["origRespCode"] == "03"
                    || $retArr["origRespCode"] == "04"
                    || $retArr["origRespCode"] == "05"){
                    //后续需发起交易状态查询交易确定交易状态
                    //TODO
                    throw new GatewayException(sprintf('code:%d, desc:%s', $retArr['respCode'], $retArr['respMsg']), Payment::GATEWAY_CHECK_FAILED, $retArr);
                } else {
                    //其他应答码做以失败处理
                    //TODO
                    throw new GatewayException(sprintf('code:%d, desc:%s', $retArr['respCode'], $retArr['respMsg']), Payment::GATEWAY_CHECK_FAILED, $retArr);
                }
            } else if ($retArr["respCode"] == "03"
                || $retArr["respCode"] == "04"
                || $retArr["respCode"] == "05" ){
                //后续需发起交易状态查询交易确定交易状态
                //TODO
                throw new GatewayException(sprintf('code:%d, desc:%s', $retArr['respCode'], $retArr['respMsg']), Payment::GATEWAY_CHECK_FAILED, $retArr);
            } else {
                //其他应答码做以失败处理
                //TODO
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
        $selfParams = [
            //以下信息非特殊情况不需要改动
            'version' => self::VERSION,   //版本号
            'txnType' => '00',		      //交易类型
            'txnSubType' => '00',		  //交易子类
            'bizType' => '000000',		  //业务类型
            'accessType' => '0',		  //接入类型
            'channelType' => $requestParams['channel_type'] ?? '07',	 //渠道类型，07-PC，08-手机
            'signMethod' => $this->signType,	              //签名方法
            //TODO 以下信息需要填写
            'orderId' => $requestParams["trade_no"] ?? '', //请修改被查询的交易的订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数
            'txnTime' => $requestParams["txn_time"] ?? date('YmdHis'),	//请修改被查询的交易的订单发送时间，格式为YYYYMMDDhhmmss，此处默认取demo演示页面传递的参数
        ];

        return $selfParams;
    }
}
