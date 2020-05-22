<?php


namespace Payment\Gateways\Union;

use Payment\Exceptions\GatewayException;
use Payment\Helpers\ArrayUtil;
use Payment\Helpers\DataParser;
use Payment\Helpers\CertUtil;
use Payment\Payment;
use Payment\Supports\BaseObject;
use Payment\Supports\HttpRequest;

/**
 * @package Payment\Gateways\Union
 * @author  : xiaowei
 * @date    : 2020/4/30 8:55 AM
 * @version : 1.0.0
 * @desc    : 银联网络请求基类
 **/
abstract class UnionBaseObject extends BaseObject
{
    use HttpRequest;

    const REQ_SUC = '00';

    /**
     * @var string
     */
    protected $gatewayUrl = '';

    /**
     * 签名证书
     * @var string
     */
    protected $signCert = '';
    /**
     * 签名证书密码
     * @var string
     */
    protected $signCertPwd = '';
    /**
     * 加密证书
     * @var string
     */
    protected $encryptCert = '';
    /**
     * 验签中级证书
     * @var string
     */
    protected $middleCerty = '';
    /**
     * 验签根证书
     * @var string
     */
    protected $rootCert = '';
    /**
     * 是否验证验签证书的CN
     * @var bool
     */
    protected $ifValidateCNName = true;

    /**
     * @var string
     */
    private $sandboxKey = '';

    /**
     * @var bool
     */
    protected $isSandbox = false;

    /**
     * @var bool
     */
    protected $returnRaw = false;

    /**
     * @var string
     */
    protected $nonceStr = '';

    /**
     * @var bool
     */
    protected $useBackup = false;

    /**
     * 设置加密方式
     * @var string
     */
    protected $signType = '01';

    /**
     * 请求方法的名称
     * @var string
     */
    protected $methodName = '';

    /**
     * 证书信息
     * @var array
     */
    protected $certData = [];


    /**
     * UnionBaseObject constructor.
     * @throws GatewayException
     */
    public function __construct()
    {
        $this->isSandbox = self::$config->get('use_sandbox', false);

        $this->signCert = self::$config->get('sign_cert', '');
        $this->signCertPwd = self::$config->get('sign_cert_pwd', '');
        $this->encryptCert = self::$config->get('encrypt_cert', '');
        $this->middleCerty = self::$config->get('middle_cert', '');
        $this->rootCert    = self::$config->get('root_cert', '');
        $this->ifValidateCNName  = self::$config->get('ifValidate_CNName', true);

        // 初始 银联网关地址
        $this->gatewayUrl = 'https://gateway.95516.com/%s';
        if ($this->isSandbox) {
            $this->gatewayUrl = 'https://gateway.test.95516.com/%s';
        }
    }

    /**
     * 生成请求参数
     * @param array $requestParams
     * @return array|mixed
     * @throws GatewayException
     * @author: XiaoWei
     * @date 2020-05-08 18:39
     */
    protected function buildParams(array $requestParams = [])
    {
        $params = [
            'encoding' => 'utf-8',				  //编码方式
            'merId' => self::$config->get('app_id', ''),		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数

        ];

        if (!empty($requestParams)) {
            //加密方法
            $this->signType = $requestParams['signMethod'] ? $requestParams['signMethod'] : $this->signType;//加密方法
            $selfParams = $this->getSelfParams($requestParams);

            if (is_array($selfParams) && !empty($selfParams)) {
                $params = array_merge($params, $selfParams);
            }
        }

        try {
            //添加证书信息
            $params = $this->signByCertInfo($params);
            //移除空值的key
            $params = ArrayUtil::paraFilter($params);
            //对输入的数组进行字典排序
            $params = ArrayUtil::arraySort($params);
            //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
            $signStr        = ArrayUtil::createLinkstring($params);
            //签名
            $params['signature'] = $this->makeSign($signStr);
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException($e->getMessage(), Payment::PARAMS_ERR);
        }

        return $params;
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function getSelfParams(array $requestParams);

    /**
     * 签名算法实现  便于后期扩展不同的加密方式
     * @param string $signStr
     * @return string
     * @throws GatewayException
     */
    protected function makeSign(string $signStr)
    {
        try {
            switch ($this->signType) {
                case '01':
                    //sha256签名摘要
                    $params_sha256x16 = hash( 'sha256',$signStr);
                    // 签名
                    $result = openssl_sign ( $params_sha256x16, $signature, $this->certData['key'], 'sha256');
                    if ($result) {
                        $sign = base64_encode ( $signature );
                    } else {
                        $sign = null;
                    }
                    break;
                default:
                    throw new GatewayException(sprintf('[%s] sign type not support', $this->signType), Payment::PARAMS_ERR);
            }
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException(sprintf('sign error, sign type is [%s]. msg: [%s]', $this->signType, $e->getMessage()), Payment::SIGN_ERR);
        }

        return $sign;
    }

    /**
     * 获取证书信息
     * @param $params
     * @return mixed
     * @throws GatewayException
     * @author: XiaoWei
     * @date 2020-05-08 18:34
     */
    public function signByCertInfo($params) {
        try {
            //证书信息
            $cert = CertUtil::initSignCert($this->signCert, $this->signCertPwd);
            if(empty($cert)){
                throw new GatewayException(sprintf('[%s] sign: Certificate read failed', $this->signType), Payment::PARAMS_ERR);
            }
            $this->certData = $cert;
            $params ['certId'] = $cert['certId'];
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException(sprintf('sign error, sign type is [%s]. msg: [%s]', $this->signType, $e->getMessage()), Payment::SIGN_ERR);
        }
        return $params;

    }

    /**
     * 检查返回的数据是否被篡改过--验签
     * @param array $retData
     * @return boolean
     * @author xiaowei
     * @throws GatewayException
     */
    protected function verifySign(array $retData)
    {
        $isSuccess = false;
        try {
            //便于后期扩展不同的验签方式，目前只支持signMethod=01的方式
            switch ($retData['signMethod']) {
                case '01':
                    $isSuccess = $this->verifyData($retData);
                    break;
                default:
                    throw new GatewayException(sprintf('[%s] verifySign type not support', $this->signType), Payment::PARAMS_ERR);
            }
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException(sprintf('verifySign error, sign type is [%s]. msg: [%s]', $this->signType, $e->getMessage()), Payment::SIGN_ERR);
        }
        return $isSuccess;
    }

    /**
     * 对签名方式为01时的数据验签
     * @param array $retData
     * @return bool
     * @throws GatewayException
     * @author: XiaoWei
     * @date 2020-05-09 10:40
     */
    protected function verifyData(array $retData){
        $isSuccess = false;
        try {
            $retSign = $retData['signature'];
            //去除签名项
            $values  = ArrayUtil::removeKeys($retData, ['signature']);
            //对数组进行字典排序
            $values  = ArrayUtil::arraySort($values);
            //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
            $signStr = ArrayUtil::createLinkstring($values,true);
            //签名公钥证书验证
            $strCert = $retData['signPubKeyCert'];
            $strCert = CertUtil::verifyAndGetVerifyCert($strCert,$this->rootCert,$this->middleCerty,$this->ifValidateCNName);
            if($strCert != null){
                $params_sha256x16 = hash('sha256', $signStr);
                $signature = base64_decode ( $retSign );
                //如果签名正确，则返回1；如果签名不正确，则返回0；如果错误，则返回-1。
                $res = openssl_verify ( $params_sha256x16, $signature,$strCert, "sha256" );
                $isSuccess = $res == 1 ? true : false;
            }
        } catch (\Exception $e) {
            throw new GatewayException('union verify sign generate str get error', Payment::SIGN_ERR);
        }
        return $isSuccess;
    }


    /**
     * 请求银联的api
     * @param string $method
     * @param array $requestParams
     * @return array|false
     * @throws GatewayException
     */
    protected function requestApi(string $method, array $requestParams)
    {
        $this->methodName = $method;
        try {
            $xmlData = $this->buildParams($requestParams);
            $url     = sprintf($this->gatewayUrl, $method);


            $resXml = $this->postXML($url, $xmlData);
            if (in_array($method, ['pay/downloadbill', 'pay/downloadfundflow'])) {
                return $resXml;
            }

            $resArr = DataParser::toArray($resXml);
            if (!is_array($resArr) || $resArr['return_code'] !== self::REQ_SUC) {
                throw new GatewayException($this->getErrorMsg($resArr), Payment::GATEWAY_REFUSE, $resArr);
            } elseif (isset($resArr['result_code']) && $resArr['result_code'] !== self::REQ_SUC) {
                throw new GatewayException(sprintf('code:%d, desc:%s', $resArr['err_code'], $resArr['err_code_des']), Payment::GATEWAY_CHECK_FAILED, $resArr);
            }

            if (isset($resArr['sign']) && $this->verifySign($resArr) === false) {
                throw new GatewayException('check return data sign failed', Payment::SIGN_ERR, $resArr);
            }

            return $resArr;
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 获取签名
     * @param $params
     * @return array
     * @throws GatewayException
     * @author: XiaoWei
     * @date 2020-05-09 10:47
     */
    protected function getSign(array $params)
    {
        try {
            //添加证书信息
            $params = $this->signByCertInfo($params);
            //移除空值的key
            $params = ArrayUtil::paraFilter($params);
            //对输入的数组进行字典排序
            $params = ArrayUtil::arraySort($params);
            //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
            $signStr        = ArrayUtil::createLinkstring($params);
            //签名
            $params['signature'] = $this->makeSign($signStr);
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException($e->getMessage(), Payment::PARAMS_ERR);
        }

        return $params;

    }

    /**
     * @param string $gatewayUrl
     */
    protected function setGatewayUrl(string $gatewayUrl)
    {
        $this->gatewayUrl = $gatewayUrl;
    }

    /**
     * 设置验签方式
     * @param string $signType
     */
    protected function setSignType(string $signType)
    {
        $this->signType = $signType;
    }


}
