<?php

namespace MobileNowGroup\LaravelAliyunSms\Traits;

use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsRecord;

trait QueryOperation
{
    /**
     * @param array $variables
     * @return array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public function query(array $variables): array
    {
        $result = $this->sendAliyunRequest('QuerySendDetails', $this->buildQueryOptions($variables))
            ->toArray();

        return $this->transformQueryResult($result);
    }

    /**
     * @param AliyunSmsRecord $record
     * @param array|null $variables
     * @return array
     * @throws \Exception
     */
    public function queryRecord(AliyunSmsRecord $record, ?array $variables = null): array
    {
        if (!$record->biz_id) {
            throw new \Exception('System has NOT sent the sms yet');
        }

        $results = $record->results ?? [];
        $phoneNumbers = $record->phone_numbers;
        $sendDate = str_replace('-', '', substr($record->send_datetime, 0, 10));
        $bizId = $record->biz_id;

        collect(explode(',', $phoneNumbers))->each(function (string $phoneNumber) use ($record, &$results, $sendDate, $variables, $bizId) {
            if ($this->phoneNumberShouldQuery($phoneNumber, $results)) {
                $result = $this->query(array_merge($variables ?? [], [
                    'phone_number' => $phoneNumber,
                    'send_date' => $sendDate,
                    'biz_id' => $bizId,
                ]));

                if (isset($result[0])) {
                    if (!empty($results['missing']) && array_search($phoneNumber, $results['missing']) !== false) {
                        array_splice($results['missing'], array_search($phoneNumber, $results['missing']), 1);
                    }

                    switch ($result[0]['SendStatus']) {
                        case AliyunSmsRecord::STATUS_ALIYUN_DELIVERED:
                            $results['delivered'][$phoneNumber] = $result[0];

                            if ($this->isPhoneNumberPending($phoneNumber, $results)) {
                                array_splice($results['pending'], array_search($phoneNumber, $results['pending']), 1);
                            }

                            $record->update([
                                'results' => $results,
                            ]);
                            break;
                        case AliyunSmsRecord::STATUS_ALIYUN_FAILED:
                            $results['failed'][$phoneNumber] = $result[0];

                            if ($this->isPhoneNumberPending($phoneNumber, $results)) {
                                array_splice($results, array_search($phoneNumber, $results['pending']), 1);
                            }

                            $record->update([
                                'results' => $results,
                            ]);
                            break;
                        case AliyunSmsRecord::STATUS_ALIYUN_PENDING:
                            if (!$this->isPhoneNumberPending($phoneNumber, $results)) {
                                $results['pending'][] = $phoneNumber;
                                $record->update([
                                    'results' => $results,
                                ]);
                            }
                            break;
                    }
                } else {
                    if (!$this->isPhoneNumberMissing($phoneNumber, $results)) {
                        $results['missing'][] = $phoneNumber;
                        $record->update([
                            'results' => $results,
                        ]);
                    }
                }
            }
        });

        return $results;
    }

    /**
     * @param array $variables
     * @return array
     */
    private function buildQueryOptions(array $variables): array
    {
        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'PhoneNumber' => $variables['phone_number'],
                'SendDate' => $variables['send_date'],
                'PageSize' => $variables['page_size'] ?? 100,
                'CurrentPage' => $variables['current_page'] ?? 1,
                'BizId' => $variables['biz_id'] ?? null,
            ],
        ];
    }

    /**
     * @param array $result
     * @return array
     */
    private function transformQueryResult(array $result): array
    {
        return $result['SmsSendDetailDTOs']['SmsSendDetailDTO'] ?? [];
    }

    /**
     * @param string $group
     * @param string $phoneNumber
     * @param array $results
     * @return bool
     */
    private function isPhoneNumberInGroup(string $group, string $phoneNumber, array $results): bool
    {
        return isset($results[$group]) && in_array($phoneNumber, $results[$group]);
    }

    /**
     * @param string $phoneNumber
     * @param array $results
     * @return bool
     */
    private function isPhoneNumberDelivered(string $phoneNumber, array $results): bool
    {
        return $this->isPhoneNumberInGroup('delivered', $phoneNumber, $results);
    }

    /**
     * @param string $phoneNumber
     * @param array $results
     * @return bool
     */
    private function isPhoneNumberPending(string $phoneNumber, array $results): bool
    {
        return $this->isPhoneNumberInGroup('pending', $phoneNumber, $results);
    }

    /**
     * @param string $phoneNumber
     * @param array $results
     * @return bool
     */
    private function isPhoneNumberFailed(string $phoneNumber, array $results): bool
    {
        return $this->isPhoneNumberInGroup('failed', $phoneNumber, $results);
    }

    /**
     * @param string $phoneNumber
     * @param array $results
     * @return bool
     */
    private function isPhoneNumberMissing(string $phoneNumber, array $results): bool
    {
        return $this->isPhoneNumberInGroup('missing', $phoneNumber, $results);
    }

    /**
     * @param string $phoneNumber
     * @param array $results
     * @return bool
     */
    private function phoneNumberShouldQuery(string $phoneNumber, array $results): bool
    {
        return $this->isPhoneNumberPending($phoneNumber, $results) ||
            (!$this->isPhoneNumberDelivered($phoneNumber, $results) &&
                !$this->isPhoneNumberFailed($phoneNumber, $results)
            );
    }
}
