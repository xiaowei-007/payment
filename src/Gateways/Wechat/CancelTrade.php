<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Gateways\Wechat;

use Payment\Contracts\IGatewayRequest;
use Payment\Exceptions\GatewayException;

/**
 * @package Payment\Gateways\Wechat
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/11/26 6:55 PM
 * @version : 1.0.0
 * @desc    : 撤销订单，支付交易返回失败或支付系统超时，调用该接口撤销交易
 **/
class CancelTrade extends WechatBaseObject implements IGatewayRequest
{
    const METHOD = 'secapi/pay/reverse';


    /**
     *
     * @param array $requestParams
     * @return mixed
     */
    protected function getSelfParams(array $requestParams)
    {

        $selfParams = [
            'transaction_id' => $requestParams['transaction_id'] ?? '',
            'out_trade_no'   => $requestParams['trade_no'] ?? '',
            //服务商模式下
            'sub_appid'        => $requestParams['sub_appid'] ?? '',
            'sub_mch_id'        => $requestParams['sub_mch_id'] ?? '',
        ];

        return $selfParams;
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
            return $this->requestWXApi(self::METHOD, $requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }
}
