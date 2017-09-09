<?php

class SMS
{
    const APPKEY = "23754686";
    const SECRETKEY = "7373475ca36dbd69aa25d6abdfdf3ea6";

    /**
     * 发送短信
     * @param $mobile 手机号码
     * @param $ctype 内容类型
     * @param $content 短信内容
     * @param $TemplateCode 模版ID
     * @param $SmsType 短信类型
     * @param $SignName 短信签名
     * @param $product 产品
     * @return mixed
     */
    public function sendSMS($mobile, $ctype, $content, $TemplateCode, $SmsType, $SignName, $product)
    {
        require_once (dirname(dirname(__FILE__)) . '/libraries/taobao-sdk/TopSdk.php');
        $c = new TopClient();
        $c->appkey = self::APPKEY;
        $c->secretKey = self::SECRETKEY;
        $req = new AlibabaAliqinFcSmsNumSendRequest();
        $req->setExtend("");
        $req->setSmsType($SmsType);
        $req->setSmsFreeSignName($SignName);
        $req->setSmsParam("{" . $ctype . ":'" . $content . "',product:'" . $product . "'}");
        $req->setRecNum($mobile);
        $req->setSmsTemplateCode($TemplateCode);
        $resp = $c->execute($req);
        return $resp;

    }

    /**
     *
     */
    public function loginSMS()
    {

    }
}