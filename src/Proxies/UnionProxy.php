<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Proxies;

use InvalidArgumentException;
use Payment\Contracts\IGatewayRequest;
use Payment\Contracts\IPayNotify;
use Payment\Contracts\IPayProxy;
use Payment\Contracts\IQueryProxy;
use Payment\Exceptions\GatewayException;
use Payment\Gateways\Union\TradeQuery;
use Payment\Gateways\Union\VerifyDataSign;
use Payment\Gateways\Union\HandleData;
use Payment\Gateways\Union\Notify;
use Payment\Gateways\Union\CancelTrade;
use Payment\Gateways\Union\Refund;
use Payment\Gateways\Union\PreauthFinish;
use Payment\Payment;
use Payment\Supports\BaseObject;

/**
 * @package Payment\Proxys
 * @author  : xiaowei
 * @date    : 2020/4/28 10:25 PM
 * @version : 1.0.0
 * @desc    : 银联对外暴露的方案集合
 **/
class UnionProxy extends BaseObject implements IPayProxy, IQueryProxy
{
    /**
     * 支付操作
     * @param string $channel
     * @param array $requestParams
     * @return mixed
     * @throws \Payment\Exceptions\GatewayException
     */
    public function pay(string $channel, array $requestParams)
    {
        $className = $this->getChargeClass($channel);
        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Gateway [%s] not exists.', $className), Payment::CLASS_NOT_EXIST);
        }

        try {
            /**
             * @var IGatewayRequest $charge
             */
            $charge = new $className();
            return $charge->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 获取支付类
     * @param string $channel
     * @return string
     */
    private function getChargeClass(string $channel)
    {
        $name = ucfirst(str_replace(['-', '_', ''], '', $channel));
        return "Payment\\Gateways\\Union\\{$name}Charge";
    }

    /**
     * 退款操作
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function refund(array $requestParams)
    {
        try {
            $trade = new Refund();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 异步通知
     * @param IPayNotify $callback
     * @return mixed
     * @throws GatewayException
     */
    public function notify(IPayNotify $callback)
    {
        try {
            $n    = new Notify();
            $data = $n->request(); // 获取数据
        } catch (GatewayException $e) {
            throw $e;
        }

        // 异步 async，同步 sync
        $flag = $callback->handle('Wechat', $data['notify_type'], 'async', $data['notify_data']);

        return $n->response($flag);
    }

    /**
     * 取消交易
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function cancel(array $requestParams)
    {
        try {
            $trade = new CancelTrade();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 关闭交易
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function close(array $requestParams)
    {
        try {
            $trade = new CloseTrade();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 交易查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function tradeQuery(array $requestParams)
    {
        try {
            $trade = new TradeQuery();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 退款查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function refundQuery(array $requestParams)
    {
        try {
            $trade = new RefundQuery();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 转账查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function transferQuery(array $requestParams)
    {
        $channel = $requestParams['channel'] ?? 'bank';

        try {
            if ($channel === 'bank') {
                $trade = new TransferBankQuery();
            } else {
                $trade = new TransferQuery();
            }

            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 账单查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function billDownload(array $requestParams)
    {
        try {
            $trade = new Bill();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 打款结算查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function settleDownload(array $requestParams)
    {
        try {
            $trade = new Settlement();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 对返回数据进行验签
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function verifyData(array $requestParams)
    {
        try {
            $trade = new VerifyDataSign();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }
    /**
     * 对数组进行签名并返回添加了签名项的数据
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function DatasGetSign(array $requestParams)
    {
        try {
            $trade = new HandleData();
            return $trade->DatasGetSign($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }
    /**
     * 解密
     * @param string $data
     * @return mixed
     * @throws GatewayException
     */
    public function decryptData(string $data)
    {
        try {
            $trade = new HandleData();
            return $trade->decryptData($data);
        } catch (GatewayException $e) {
            throw $e;
        }
    }
    /**
     * 加密
     * @param string $data
     * @return mixed
     * @throws GatewayException
     */
    public function encryptData(string $data)
    {
        try {
            $trade = new HandleData();
            return $trade->encryptData($data);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 预授权完成
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function paFinish(array $requestParams)
    {
        try {
            $trade = new PreauthFinish();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }



}
