<?php

namespace MobileNowGroup\LaravelAliyunSms;

use Illuminate\Support\Arr;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use MobileNowGroup\LaravelAliyunSms\Traits\AlibabaSDK;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsSign;
use MobileNowGroup\LaravelAliyunSms\Traits\SignOperation;
use MobileNowGroup\LaravelAliyunSms\Traits\QueryOperation;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsRecord;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsTemplate;
use MobileNowGroup\LaravelAliyunSms\Traits\TemplateOperation;

class AliyunSms
{
    use AlibabaSDK, SignOperation, TemplateOperation, QueryOperation;

    /** Constants */
    const PRODUCT = 'Dysmsapi';
    const PHONE_NUMBER_SIZE_LIMITATION = 1000;

    /** @var static|null */
    static $instance = null;

    /**
     * AliyunSms constructor.
     * @throws ClientException
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * @param array $variables
     * @return AliyunSmsRecord
     * @throws ClientException
     * @throws ServerException
     * @throws \Exception
     */
    public function send(array $variables): AliyunSmsRecord
    {
        $this->verifyPhoneNumberSize($variables);

        if (empty($variables['send_datetime']) || $variables['send_datetime'] <= now()->toDateTimeString()) {
            $result = $this->sendAliyunRequest('SendSms', $this->buildSendSmsOptions($variables))
                ->toArray();
            
            $this->verifyAliyunResponse($result);
        }

        return $this->storeSendSmsRequest($variables, $result ?? []);
    }

    /**
     * @throws ClientException
     */
    private function initialize()
    {
        $this->initializeAlibabaSDKClient(config('aliyun.access_key_id'), config('aliyun.access_key_secret'), config('sms.region_id'));
    }

    /**
     * @param array $result
     * @return AliyunSms
     * @throws ClientException
     */
    private function verifyAliyunResponse(array $result): AliyunSms
    {
        if ($result['Code'] !== 'OK') {
            throw new ClientException($result['Message'], $result['Code']);
        }

        return $this;
    }

    /**
     * @param array $variables
     * @return array
     */
    private function buildSendSmsOptions(array $variables): array
    {
        $sign = AliyunSmsSign::findOrFail($variables['aliyun_sms_sign_id']);
        $template = AliyunSmsTemplate::findOrFail($variables['aliyun_sms_template_id']);

        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'PhoneNumbers' => $variables['phone_numbers'],
                'SignName' => $sign->sign_name,
                'TemplateCode' => $template->template_code,
                'TemplateParam' => $variables['params'] ?? '',
            ],
        ];
    }

    /**
     * @param array $variables
     * @param array $result
     * @return AliyunSmsRecord
     */
    private function storeSendSmsRequest(array $variables, array $result = []): AliyunSmsRecord
    {
        $data = array_merge(Arr::only($variables, [
            'title',
            'phone_numbers',
            'aliyun_sms_sign_id',
            'aliyun_sms_template_id',
            'params',
            'send_datetime',
        ]), array_combine([
                'biz_id',
                'request_id',
                'message',
                'send_datetime',
            ], [
                $result['BizId'] ?? null,
                $result['RequestId'] ?? null,
                $result['Message'] ?? null,
                $variables['send_datetime'] ?? now()->toDateTimeString(),
            ])
        );

        if ($variables['id'] ?? false) {
            /** @var AliyunSmsRecord $record */
            $record = AliyunSmsRecord::findOrFail($variables['id']);
            $record->fill($data);
            $record->save();

            return $record;
        } else {
            return ($variables['persistent'] ?? true) ? AliyunSmsRecord::create($data) : new AliyunSmsRecord($data);
        }
    }

    /**
     * @param array $variables
     * @return bool
     * @throws \Exception
     */
    private function verifyPhoneNumberSize(array $variables): bool
    {
        $phoneNumberString = $variables['phone_numbers'];

        if (count(explode(',', $phoneNumberString)) > static::PHONE_NUMBER_SIZE_LIMITATION) {
            throw new \Exception('You can only send to' . static::PHONE_NUMBER_SIZE_LIMITATION . ' phone numbers in single request');
        }

        return true;
    }

    /**
     * @return AliyunSms|null
     * @throws ClientException
     */
    public static function make(): AliyunSms
    {
        if (static::$instance) {
            return static::$instance;
        }

        return static::$instance = new static();
    }
}
