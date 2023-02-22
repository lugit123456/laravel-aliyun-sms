<?php

namespace MobileNowGroup\LaravelAliyunSms\Traits;

use Illuminate\Support\Str;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsSign;

trait SignOperation
{
    /**
     * @param array $variables
     *
     * @return AliyunSmsSign
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @throws \Exception
     */
    public function createSign(array $variables): AliyunSmsSign
    {
        $this->verifySignExists($variables);

        $result = $this->sendAliyunRequest('AddSmsSign', $this->buildCreateSignOptions($variables))
            ->toArray();

        $this->verifyAliyunResponse($result);

        return $this->storeSignRequest($variables, $result);
    }

    /**
     * @param AliyunSmsSign $sign
     * @return AliyunSmsSign
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public function querySign(AliyunSmsSign $sign): AliyunSmsSign
    {
        $result = $this->sendAliyunRequest('QuerySmsSign', $this->buildQuerySignOptions($sign))
            ->toArray();

        $this->verifyAliyunResponse($result);

        return $this->updateSignStatus($sign, $result);
    }

    /**
     * @param array $variables
     * @param AliyunSmsSign $sign
     * @return AliyunSmsSign
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @throws \Exception
     */
    public function modifySign(array $variables, AliyunSmsSign $sign): AliyunSmsSign
    {
        $this->verifySignModifiable($sign);

        $result = $this->sendAliyunRequest('ModifySmsSign', $this->buildModifySignOptions($variables, $sign))
            ->toArray();

        $this->verifyAliyunResponse($result);

        return $this->updateSignRequest($variables, $result, $sign);
    }

    /**
     * @param AliyunSmsSign $sign
     * @return bool
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public function deleteSign(AliyunSmsSign $sign): bool
    {
        $result = $this->sendAliyunRequest('DeleteSmsSign', $this->buildDeleteSignOptions($sign))
            ->toArray();

        $this->verifyAliyunResponse($result);

        return $this->destroySign($result);
    }

    /**
     * @param array $variables
     * @throws \Exception
     *
     * @return bool
     */
    private function verifySignExists(array $variables): bool
    {
        if (AliyunSmsSign::where('sign_name', $variables['sign_name'])->first()) {
            throw new \Exception('Sign name has existed');
        }

        return true;
    }

    /**
     * @param array $variables
     * @return array
     */
    private function buildCreateSignOptions(array $variables): array
    {
        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'SignName' => $variables['sign_name'],
                'SignSource' => $variables['sign_source'],
                'Remark' => $variables['remark'],
                'SignFileList.1.FileSuffix' => 'jpg',
                'SignFileList.1.FileContents' => Str::uuid(),
            ],
        ];
    }

    /**
     * @param array $variables
     * @param array $result
     * @return AliyunSmsSign
     */
    private function storeSignRequest(array $variables, array $result): AliyunSmsSign
    {
        $data = array_merge($variables, array_combine([
                'request_id',
                'message',
            ], [
                $result['RequestId'],
                $result['Message'],
            ])
        );

        return AliyunSmsSign::create($data);
    }

    /**
     * @param array $variables
     * @param array $result
     * @param AliyunSmsSign $sign
     * @return AliyunSmsSign
     */
    private function updateSignRequest(array $variables, array $result, AliyunSmsSign $sign): AliyunSmsSign
    {
        $data = array_merge($sign->toArray(), $variables, [
            'request_id' => $result['RequestId'],
            $result['Message'],
        ]);

        $sign->update($data);

        return $sign->refresh();
    }

    /**
     * @param AliyunSmsSign $sign
     * @return array
     */
    private function buildQuerySignOptions(AliyunSmsSign $sign): array
    {
        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'SignName' => $sign->sign_name,
            ],
        ];
    }

    /**
     * @param AliyunSmsSign $sign
     * @param array $result
     * @return AliyunSmsSign
     */
    private function updateSignStatus(AliyunSmsSign $sign, array $result): AliyunSmsSign
    {
        if ($sign && $sign->status !== $result['SignStatus']) {
            $sign->update([
                'status' => $result['SignStatus'],
                'reason' => $result['Reason'],
                'request_id' => $result['RequestId'],
            ]);
        }

        return $sign->refresh();
    }

    /**
     * @param array $variables
     * @param AliyunSmsSign $sign
     * @return array
     */
    private function buildModifySignOptions(array $variables, AliyunSmsSign $sign): array
    {
        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'SignName' => $sign->sign_name,
                'SignSource' => $variables['sign_source'] ?: $sign->sign_source,
                'Remark' => $variables['remark'] ?: $sign->remark,
                'SignFileList.1.FileSuffix' => 'jpg',
                'SignFileList.1.FileContents' => Str::uuid(),
            ],
        ];
    }

    /**
     * @param AliyunSmsSign $sign
     * @return array
     */
    private function buildDeleteSignOptions(AliyunSmsSign $sign): array
    {
        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'SignName' => $sign->sign_name,
            ],
        ];
    }

    /**
     * @param array $result
     * @return bool
     */
    private function destroySign(array $result): bool
    {
        return AliyunSmsSign::where('sign_name', $result['SignName'])->delete();
    }

    /**
     * @param AliyunSmsSign $sign
     * @return bool
     * @throws \Exception
     */
    private function verifySignModifiable(AliyunSmsSign $sign): bool
    {
        if ($sign->status !== AliyunSmsSign::STATUS_REJECTED) {
            throw new \Exception('You cannot modify sign so far');
        }

        return true;
    }
}
