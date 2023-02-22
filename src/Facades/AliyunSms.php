<?php

namespace MobileNowGroup\LaravelAliyunSms\Facades;

use Illuminate\Support\Facades\Facade;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsSign;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsRecord;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsTemplate;

/**
 * @method static mixed createSign(array $variables)
 * @method static mixed querySign(AliyunSmsSign $sign)
 * @method static mixed modifySign(array $variables, AliyunSmsSign $sign)
 * @method static mixed deleteSign(AliyunSmsSign $sign)
 * @method static mixed createTemplate(array $variables)
 * @method static mixed queryTemplate(AliyunSmsTemplate $template)
 * @method static mixed modifyTemplate(array $variables, AliyunSmsSign $sign)
 * @method static mixed deleteTemplate(AliyunSmsTemplate $template)
 * @method static mixed query(array $variables)
 * @method static mixed queryRecord(AliyunSmsRecord $record)
 * @method static mixed send(array $variables)
 */
class AliyunSms extends Facade
{
    /**
     * Return the facade accessor.
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'aliyun.sms';
    }
}
