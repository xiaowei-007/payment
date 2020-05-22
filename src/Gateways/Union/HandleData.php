<?php


namespace Payment\Gateways\Union;

use Payment\Exceptions\GatewayException;
use Payment\Helpers\CertUtil;

/**
 * @package Payment\Gateways\HandleData
 * @author  : xiaowei
 * @date    : 2020/5/11 8:27 PM
 * @version : 1.0.0
 * @desc    : 数据加密，卡号解密等数据处理
 **/
class HandleData extends UnionBaseObject
{
    /**
     * 解密
     * @param string $data
     * @return String|null
     * @throws GatewayException
     * @author: XiaoWei
     * @date 2020-05-15 16:10
     */
    public function decryptData(string $data) {
        $crypted = null;
        try {
            $data = base64_decode ( $data );
            $private_key = CertUtil::getSignKeyFromPfx ( $this->signCert, $this->signCertPwd);
            openssl_private_decrypt ( $data, $crypted, $private_key );
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException($e->getMessage(), Payment::PARAMS_ERR);
        }
        return $crypted;
    }

    /**
     * 加密
     * @param string $data
     * @return string|null
     * @throws GatewayException
     * @author: XiaoWei
     * @date 2020-05-15 16:09
     */
    public function encryptData(string $data) {
        $crypted = null;
        try {
            $public_key = CertUtil::getEncryptKey( $this->encryptCert );
            openssl_public_encrypt ( $data, $crypted, $public_key );
            $crypted = base64_encode ( $crypted );
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException($e->getMessage(), Payment::PARAMS_ERR);
        }
        return $crypted;

    }

    /**
     * 对数组进行签名
     * @param array $data
     * @return array
     * @throws GatewayException
     * @author: XiaoWei
     * @date 2020-05-15 16:05
     */
    public function DatasGetSign(array $data){
        return $this->getSign($data);
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
