<?php

namespace MobileNowGroup\LaravelAliyunSms\Traits;

use AlibabaCloud\Client\AlibabaCloud;

trait AlibabaSDK
{
    /**
     * @param null|string $accessKey
     * @param null|string $accessSecret
     * @param null|string $regionId
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function initializeAlibabaSDKClient(?string $accessKey = null, ?string $accessSecret = null, ?string $regionId = null)
    {
        AlibabaCloud::accessKeyClient($accessKey ?? config('aliyun.access_key_id'), $accessSecret ?? config('aliyun.access_key_secret'))
            ->regionId($regionId ?? config('sms.region_id'))
            ->asDefaultClient();
    }

    /**
     * @param string $action
     * @param array $options
     * @param null|string $method
     * @param null|string $product
     * @param null|string $version
     * @param null|string $host
     * @return \AlibabaCloud\Client\Result\Result
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public function sendAliyunRequest(string $action, array $options, ?string $method = 'POST', ?string $product = null, ?string $version = null, ?string $host = null)
    {
        return AlibabaCloud::rpc()
            ->product($product ?? static::PRODUCT)
            // ->scheme('https') // https | http
            ->version($version ?? config('sms.sdk_version'))
            ->action($action)
            ->method($method)
            ->host($host ?? config('sms.host'))
            ->options($options)
            ->request();
    }
}
