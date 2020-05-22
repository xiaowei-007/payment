<?php


namespace Payment\Gateways\Union;

use Payment\Exceptions\GatewayException;

/**
 * @package Payment\Gateways\Union
 * @author  : xiaowei
 * @date    : 2020/5/11 8:27 PM
 * @version : 1.0.0
 * @desc    : 网关支付成功数据签名验证
 **/
class VerifyDataSign extends UnionBaseObject
{
    /**
     * 验签返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        try {
            if (empty($requestParams['signature']) || empty($requestParams['signPubKeyCert']) ){
                return false;
            }
            //签名验证
            if ($this->verifySign($requestParams) === false) {

                return false;
            }
            return true;

        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 实现父类的方法--在签名验证中用不到这个方法
     * @param array $requestParams
     * @return mixed
     */
    protected function getSelfParams(array $requestParams)
    {
        return $requestParams;
    }
}
