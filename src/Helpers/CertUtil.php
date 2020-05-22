<?php
namespace Payment\Helpers;

// 内存泄漏问题说明：
//     openssl_x509_parse疑似有内存泄漏，暂不清楚原因，可能和php、openssl版本有关，估计有bug。
//     windows下试过php5.4+openssl0.9.8，php7.0+openssl1.0.2都有这问题。mac下试过也有问题。
//     不过至今没人来反馈过这个问题，所以不一定真有泄漏？或者因为增长量不大所以一般都不会遇到问题？
//     也有别人汇报过bug：https://bugs.php.net/bug.php?id=71519
//
// 替代解决方案：
//     方案1. 所有调用openssl_x509_parse的地方都是为了获取证书序列号，可以尝试把证书序列号+证书/key以别的方式保存，
//            从其他地方（比如数据库）读序列号，而不直接从证书文件里读序列号。
//     方案2. 代码改成执行脚本的方式执行，这样执行完一次保证能释放掉所有内存。
//     方案3. 改用下面的CertSerialUtil取序列号，
//            此方法仅用了几个测试和生产的证书做过测试，不保证没bug，所以默认注释掉了。如发现有bug或者可优化的地方可自行修改代码。
//            注意用了bcmath的方法，*nix下编译时需要 --enable-bcmath。http://php.net/manual/zh/bc.installation.php


class CertUtil{

    const COMPANY = "中国银联股份有限公司";

    /**
     * 读取加密证书信息
     * @param $certPath
     * @param $certPwd
     * @return array
     * @author: XiaoWei
     * @date 2020-05-08 16:03
     */
    public static function initSignCert($certPath, $certPwd){
        if( empty($certPath) || empty($certPwd) ) {
            return [];
        }
        $pkcs12certdata = file_get_contents ( $certPath );
        if($pkcs12certdata === false ){
            return [];
        }

        if(openssl_pkcs12_read ( $pkcs12certdata, $certs, $certPwd ) == FALSE ){
            return [];
        }

        $x509data = $certs ['cert'];

        if(!openssl_x509_read ( $x509data )){
            return [];
        }
        $certdata = openssl_x509_parse ( $x509data );
        $cert['certId'] = $certdata ['serialNumber'];//证书id

        $cert['key'] = $certs ['pkey'];//key
        $cert['cert'] = $x509data;
        return $cert;
    }

    /**
     * 获取签名证书key
     * @param null $certPath
     * @param null $certPwd
     * @return mixed
     * @author: XiaoWei
     * @date 2020-05-08 15:41
     */
    public static function getSignKeyFromPfx($certPath=null, $certPwd=null)
    {
        $cert = self::initSignCert($certPath, $certPwd);
        return empty($cert) ? null : $cert['key'];
    }

    /**
     * 获取签名证书id
     * @param null $certPath
     * @param null $certPwd
     * @return mixed
     * @author: XiaoWei
     * @date 2020-05-08 16:08
     */
    public static function getSignCertIdFromPfx($certPath=null, $certPwd=null)
    {
        $cert = self::initSignCert($certPath, $certPwd);
        return empty($cert) ? null : $cert['certId'];
    }

    /**
     * 对签名字符串进行验证
     * @param $certBase64String
     * @param $rootCertPath
     * @param $middleCertPath
     * @param $ifValidateCNName
     * @return null
     * @author: XiaoWei
     * @date 2020-05-12 13:29
     */
    public static function verifyAndGetVerifyCert($certBase64String,$rootCertPath,$middleCertPath,$ifValidateCNName){

        if ($middleCertPath === null || $rootCertPath === null){
            return null;
        }
        openssl_x509_read($certBase64String);

        $certInfo = openssl_x509_parse($certBase64String);
        //获取证书cn
        $cn = CertUtil::getIdentitiesFromCertficate($certInfo);
        //是否要验证证书cn
        if(strtolower($ifValidateCNName) == "true"){
            if (self::COMPANY != $cn){
                return null;
            }
        } else if (self::COMPANY != $cn && "00040000:SIGN" != $cn){
            return null;
        }

        $from = date_create ( '@' . $certInfo ['validFrom_time_t'] );
        $to = date_create ( '@' . $certInfo ['validTo_time_t'] );
        $now = date_create ( date ( 'Ymd' ) );
        $interval1 = $from->diff ( $now );
        $interval2 = $now->diff ( $to );
        if ($interval1->invert || $interval2->invert) {
            return null;
        }
        $result = openssl_x509_checkpurpose($certBase64String, X509_PURPOSE_ANY, array($rootCertPath, $middleCertPath));
        if($result === TRUE){
            return $certBase64String;
        }else if($result === FALSE){
            return null;
        } else {
            return null;
        }
    }

    /**
     * 获取签证书的CN
     * @param $certInfo
     * @return |null
     * @author: XiaoWei
     * @date 2020-05-08 16:06
     */
    public static function getIdentitiesFromCertficate($certInfo){

        $cn = $certInfo['subject'];
        $cn = $cn['CN'];
        $company = explode('@',$cn);

        if(count($company) < 3) {
            return null;
        }
        return $company[2];
    }


    /**
     * 读取加密证书信息
     * @param $cert_path
     * @return array
     * @author: XiaoWei
     * @date 2020-05-08 16:16
     */
    private static function initEncryptCert($cert_path)
    {
        if( empty($cert_path) ) {
            return [];
        }
        $x509data = file_get_contents ( $cert_path );
        if($x509data === false ){
            return [];
        }

        if(!openssl_x509_read ( $x509data )){
            return [];
        }

        $certdata = openssl_x509_parse ( $x509data );

        $cert['certId'] = $certdata ['serialNumber'];

        $cert['key'] = $x509data;

        return $cert;
    }
    /**
     * 获取加密证书id
     * @param null $cert_path
     * @return bool|mixed
     * @author: XiaoWei
     * @date 2020-05-08 16:20
     */
    public static function getEncryptCertId($cert_path=null){

        $cert = self::initEncryptCert($cert_path);
        return empty($cert) ? false : $cert['certId'];
    }

    /**
     * 获取加密证书key
     * @param null $cert_path
     * @return bool|mixed
     * @author: XiaoWei
     * @date 2020-05-08 16:20
     */
    public static function getEncryptKey($cert_path=null){

        $cert = self::initEncryptCert($cert_path);
        return empty($cert) ? false : $cert['key'];
    }



}







    