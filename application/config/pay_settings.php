<?php
/**
 * Created by PhpStorm.支付配置
 * User: Administrator
 * Date: 2017/9/5
 * Time: 16:54
 */
defined('BASEPATH') OR exit('No direct script access allowed');

//支付宝配置
$config['alipay_config'] = [
    'partner' => '2088521292269473',
    'private_key_path' => '+Zd4+06xhscKHxddJH47fqZvmOdP3DvVoxNNFvLQRfphKOXz5uXDvg7yAzwwHM09E5fNWFFosIdy8pAgMBAAECgYEAnjoINZHY29c53do20a6VKUkS0UF0ursxYMpbzlkvJbAO8/InF6KqU1KDEQO0lcvkbQDxXh8sdFIbIug+fUVRj3Cnz5YjmYJPDtPtZyfogCqqpYi+x94SWZf4FzZlipmUmABCJk/AMtIws1FZ7xMTi+yF4Cj0fjpPQo7HsyEz5GECQQD8BOAQeRyVMi5dvch8jqELJB0Omn+lkYFBGIwG2Ld04saLhNGzmJQVGFWNlV666h7vfkS4eb9CZMJuPtjTIH8TAkEA3IOKrD8akM7/1E2fZZLQpksasCb11MrhwnDQU2XaLSBB6dHAGlUUZBQTGQrGGS+recP2lGQmYS1xSy3yuo2UUwJBAKMANDvzWX1WG48d9NI7HgYqsXCElRLtbYBA9DBpcx7yniAXI9rZUM3kE1GjzsVuL9wO+zul4wJ6URclJvBHEGkCQGT2PSm8ArfGbs+PcqmY3Lsmq+N3ExsIgPD7ogZtHcWHfWZGyMPFrH5dypiunCCv+LzZgi5S5Fed7L9VHEtZw00CQHAXeT6sA+We4qOSUOsj4dqMGFTk+veE/C11ojodnzaoW/RTey8k01FfqFOW5jZmTK4x7xHj4i5c9Jg74Cao8Ts=',
    'ali_public_key_path' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB',
    'sign_type' => 'RSA',
    'input_charset' => 'utf-8',
    'cacert' => '1',
    'transport' => 'http',
    'notify_url' => 'http://pinquduo.cn/Store/Alipayapi/notify_url'
];

//微信JS支付配置
$config['wx_js_pay'] = [
    'app_id' => 'wxdbc22996638a2c73',
    'secret' => 'd259ccee138067613a26971092c6e48d',
    'merchant_id' => '1405319302',
    'key' => 'ACABF43504C39F7BCB9ACB0EF70074AC',
    'cert_path' => realpath(APPPATH . 'storage' . DIRECTORY_SEPARATOR . 'jscert' . DIRECTORY_SEPARATOR . 'apiclient_cert.pem'),
    'key_path' => realpath(APPPATH . 'storage' . DIRECTORY_SEPARATOR . 'jscert' . DIRECTORY_SEPARATOR . 'apiclient_key.pem'),
    'notify_url' => ''
];

//微信APP支付配置
$config['wx_app_pay'] = [
    'app_id' => 'wx4a5c41f0607887ba',
    'secret' => '6bc685871cbb86149879c962b3cf6c34',
    'merchant_id' => '1402068602',
    'key' => 'b4aJJEXennMpyulRdvkkZUthgzhBlum2',
    'cert_path' => realpath(APPPATH . 'storage' . DIRECTORY_SEPARATOR . 'cacert' . DIRECTORY_SEPARATOR . 'apiclient_cert.pem'),
    'key_path' => realpath(APPPATH . 'storage' . DIRECTORY_SEPARATOR . 'cacert' . DIRECTORY_SEPARATOR . 'apiclient_key.pem')
];

//QQ支付配置
$config['qq_pay'] = [
    'appId' => '1105994087',
    'appKey' => 'xwmkB51fQDnvcnwR',
    'mchId' => '1447755601',
    'md5Key' => 'u6rAIksPMZVm4V6wc5Xh8STxvxJ3Vym1',
    'logPath' => realpath(APPPATH . 'logs') . DIRECTORY_SEPARATOR,
    'cert_dir' => realpath(APPPATH . 'storage' . DIRECTORY_SEPARATOR . 'qqcert') . DIRECTORY_SEPARATOR,
    'certFile' => 'apiclient_cert.pem',
    'keyFile' => 'apiclient_key.pem',
    'cacertFile' => 'rootca.pem',
    'opUserPassMd5' => '97b7917a023928a2fb7799589985f4a7',
    'notify_url' =>  'http://wx.pinqudou.cn/Api_2_0_2/QQPay/notify' //TODO C函数位置需要重写
];