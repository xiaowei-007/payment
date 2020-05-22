<?php

/*
 * 银联支付配置信息
 */

return [
    'use_sandbox' => true, // 是否使用沙盒模式

    'app_id'    => '777290058110048',//商户号

    /*;;;;;;;;;;;;;;;;;;;;;;;;;入网测试环境签名证书配置 ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;*/
    // 多证书的情况证书路径为代码指定，可不对此块做配置。
    // 签名证书路径，必须使用绝对路径，如果不想使用绝对路径，可以自行实现相对路径获取证书的方法；测试证书所有商户共用开发包中的测试签名证书，生产环境请从cfca下载得到。
    // 测试环境证书位于assets/测试环境证书/文件夹下，请复制到/Users/xiaowei/Desktop/web/upacp_demo_b2c/certs文件夹。生产环境证书由业务部门邮件发送。
    // windows样例：
    'sign_cert'  => '/home/wwwroot/online_shnu/Application/Common/certs/union/acp_test_sign.pfx',
    // linux样例（注意：在linux下读取证书需要保证证书有被应用读的权限）（后续其他路径配置也同此条说明）

    // 签名证书密码，测试环境固定000000，生产环境请修改为从cfca下载的正式证书的密码，正式环境证书密码位数需小于等于6位，否则上传到商户服务网站会失败
    'sign_cert_pwd'   =>   '000000',

    /*;;;;;;;;;;;;;;;;;;;;;;;;;;加密证书配置;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;*/
    // 敏感信息加密证书路径(商户号开通了商户对敏感信息加密的权限，需要对 卡号accNo，pin和phoneNo，cvn2，expired加密（如果这些上送的话），对敏感信息加密使用)
    'encrypt_cert'   => '/home/wwwroot/online_shnu/Application/Common/certs/union/acp_test_enc.cer',

    /*;;;;;;;;;;;;;;;;;;;;;;;;;;验签证书配置;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;*/
    // 验签中级证书（证书位于assets/测试环境证书/文件夹下，请复制到/Users/xiaowei/Desktop/web/upacp_demo_b2c/certs文件夹）
    'middle_cert'  => '/home/wwwroot/online_shnu/Application/Common/certs/union/acp_test_middle.cer',
    // 验签根证书（证书位于assets/测试环境证书/文件夹下，请复制到/Users/xiaowei/Desktop/web/upacp_demo_b2c/certs文件夹）
    'root_cert'  =>  '/home/wwwroot/online_shnu/Application/Common/certs/union/acp_test_root.cer',


    // 是否验证验签证书的CN，测试环境请设置false，生产环境请设置true。非false的值默认都当true处理。
    'ifValidate_CNName'  =>  false,

    // 是否验证https证书，测试环境请设置false，生产环境建议优先尝试true，不行再false。非true的值默认都当false处理。
    //'ifValidate_remoteCert'  =>  false,

    'currency_code' => '156',				  //交易币种，156-人民币


    // 与业务相关参数
    'notify_url' => 'https://wxecardservice.shnu.edu.cn/Alipay/TestPay/Notify',//回调地址
    'return_url' => 'https://wxecardservice.shnu.edu.cn/Alipay/TestPay/Notify',//前台通知地址
    //'return_raw'                => false,// 在处理回调时，是否直接返回原始数据，默认为 true
    //代理配置
    'https_proxy' => 'https://172.20.40.43:8090',//https代理
    'http_proxy'  => '',//http代理
    'no_proxy'    => 'localhost,172.20.40.43,127.0.0.1,::1',//不作代理的地址

];
