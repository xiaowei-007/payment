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
 * @desc    : 订单撤销
 **/
class CancelTrade extends UnionBaseObject implements IGatewayRequest
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

    /**
     * @param array $requestParams
     * @return mixed
     */
    protected function getSelfParams(array $requestParams)
    {
        $totalFee  = $requestParams['amount'] ? bcmul($requestParams['amount'], 100, 0) : 0;
        $selfParams = [
            //以下信息非特殊情况不需要改动
            'version' => self::VERSION,   //版本号
            'txnType' => $requestParams["txn_type"] ?? '31',		      //交易类型
            'txnSubType' => '00',		  //交易子类
            'bizType' => $requestParams["biz_type"] ?? '000201',		  //业务类型
            'accessType' => '0',		  //接入类型
            'channelType' => $requestParams['channel_type'] ?? '08',	 //渠道类型，07-PC，08-手机
            'signMethod' => $this->signType,	              //签名方法
            'backUrl' => self::$config->get('notify_url', ''),	  //后台通知地址

            //TODO 以下信息需要填写
            'orderId' => $requestParams["trade_no"] ?? '',	    //商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'origQryId' => $requestParams["transaction_id"] ?? '', //原消费的queryId，可以从查询接口或者通知接口中获取，此处默认取demo演示页面传递的参数
            'txnTime' => date('YmdHis'),	//订单发送时间，格式为YYYYMMDDhhmmss，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'txnAmt' => $totalFee,       //交易金额，消费撤销时需和原消费一致，此处默认取demo演示页面传递的参数

        ];

        return $selfParams;
    }
}
