<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Gateways\Union;

use Payment\Exceptions\GatewayException;
use Payment\Helpers\CertUtil;
use Payment\Payment;

/**
 * @package Payment\Gateways\Alipay
 * @author  : xiaowei
 * @version : 1.0.0
 * @desc    : 处理notify的问题
 **/
class Notify extends UnionBaseObject
{
    /**
     * 获取请求数据
     * @throws GatewayException
     */
    public function request()
    {
        $resArr = $this->getNotifyData();
        if (empty($resArr)) {
            throw new GatewayException('the notify data is empty', Payment::NOTIFY_DATA_EMPTY);
        }

        if (!is_array($resArr) || $resArr['respCode'] !== self::REQ_SUC || $resArr['respMsg'] == 'success') {
            throw new GatewayException($this->getErrorMsg($resArr), Payment::GATEWAY_REFUSE, $resArr);
        }

        //签名验证
        if (isset($resArr['signature']) && $this->verifySign($resArr) === false) {
            throw new GatewayException('check return data sign failed', Payment::SIGN_ERR, $resArr);
        }
        // 检查商户是否正确
        if (!isset($resArr['merId']) || $resArr['merId'] != self::$config->get('app_id', '')) {
            throw new GatewayException('mch info is error', Payment::MCH_INFO_ERR, $resArr);
        }

        //卡号解密
        if(array_key_exists ("accNo", $resArr)){

            $resArr['cardNo'] = $this->decryptData($resArr['accNo']);

        }

        $notifyType = 'pay';//支付回调

        return [
            'notify_type' => $notifyType,
            'notify_data' => $resArr
        ];
    }

    /**
     * 获取异步通知数据
     * @return array
     */
    protected function getNotifyData()
    {
        $data = empty($_POST) ? $_GET : $_POST;
        if (empty($data) || !is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * 卡号解密
     * @param string $data
     * @return string
     * @author: XiaoWei
     * @date 2020-06-08 16:54
     */
    protected function decryptData(string $data) {
        $crypted = '';
        try {
            $data = base64_decode ( $data );
            $private_key = CertUtil::getSignKeyFromPfx ( $this->signCert, $this->signCertPwd);
            openssl_private_decrypt ( $data, $crypted, $private_key );
        } catch (GatewayException $e) {
            $crypted = '';
        } catch (\Exception $e) {
            $crypted = '';
        }
        return $crypted;
    }

    /**
     * 响应数据
     * @param bool $flag
     * @return string
     */
    public function response(bool $flag)
    {
        if ($flag) {
            return 'success';
        }
        return 'fail';
    }

    /**
     * notify 不需要该方法，不实现
     * @param array $requestParams
     * @return mixed
     */
    protected function getSelfParams(array $requestParams)
    {
        return [];
    }
}
