<?php

namespace MobileNowGroup\LaravelAliyunSms\Traits;

use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsTemplate;

trait TemplateOperation
{
    /**
     * @param array $variables
     * @return AliyunSmsTemplate
     * @throws ClientException
     * @throws ServerException
     * @throws \Exception
     */
    public function createTemplate(array $variables): AliyunSmsTemplate
    {
        $this->verifyTemplateExists($variables);

        $result = $this->sendAliyunRequest('AddSmsTemplate', $this->buildCreateTemplateOptions($variables))
            ->toArray();

        return $this->verifyAliyunResponse($result)
            ->storeTemplateRequest($variables, $result);
    }

    /**
     * @param AliyunSmsTemplate $template
     * @return AliyunSmsTemplate
     * @throws ClientException
     * @throws ServerException
     */
    public function queryTemplate(AliyunSmsTemplate $template): AliyunSmsTemplate
    {
        $result = $this->sendAliyunRequest('QuerySmsTemplate', $this->buildQueryTemplateOptions($template))
            ->toArray();

        return $this->verifyAliyunResponse($result)
            ->updateTemplateStatus($template, $result);
    }

    /**
     * @param array $variables
     * @param AliyunSmsTemplate $template
     * @return AliyunSmsTemplate
     * @throws ClientException
     * @throws ServerException
     */
    public function modifyTemplate(array $variables, AliyunSmsTemplate $template): AliyunSmsTemplate
    {
        $this->verifyTemplateModifiable($template);

        $result = $this->sendAliyunRequest('ModifySmsTemplate', $this->buildModifyTemplateOptions($variables, $template))
            ->toArray();

        return $this->verifyAliyunResponse($result)
            ->updateTemplateRequest($variables, $result, $template);
    }

    /**
     * @param AliyunSmsTemplate $template
     * @return bool
     * @throws ClientException
     * @throws ServerException
     */
    public function deleteTemplate(AliyunSmsTemplate $template): bool
    {
        $result = $this->sendAliyunRequest('DeleteSmsTemplate', $this->buildDeleteTemplateOptions($template))
            ->toArray();

        return $this->verifyAliyunResponse($result)
            ->destroyTemplate($result);
    }

    /**
     * @param array $variables
     * @throws \Exception
     *
     * @return bool
     */
    private function verifyTemplateExists(array $variables): bool
    {
        if (AliyunSmsTemplate::where('content', $variables['content'])->first()) {
            throw new \Exception('Content has existed');
        }

        return true;
    }

    /**
     * @param array $variables
     * @return array
     */
    private function buildCreateTemplateOptions(array $variables): array
    {
        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'TemplateType' => $variables['type'],
                'TemplateName' => $variables['title'],
                'TemplateContent' => $variables['content'],
                'Remark' => $variables['remark'],
            ],
        ];
    }

    /**
     * @param array $variables
     * @param array $result
     * @return AliyunSmsTemplate
     */
    private function storeTemplateRequest(array $variables, array $result): AliyunSmsTemplate
    {
        $data = array_merge($variables, array_combine([
                'request_id',
                'message',
                'template_code',
                'params',
            ], [
                $result['RequestId'],
                $result['Message'],
                $result['TemplateCode'],
                $this->getTemplateParameters($variables['content']),
            ])
        );

        return AliyunSmsTemplate::create($data);
    }

    /**
     * @param array $variables
     * @param array $result
     * @param AliyunSmsTemplate $template
     * @return AliyunSmsTemplate
     */
    private function updateTemplateRequest(array $variables, array $result, AliyunSmsTemplate $template): AliyunSmsTemplate
    {
        $data = array_merge($template->toArray(), $variables, [
            'request_id' => $result['RequestId'],
            'message' => $result['Message'],
            'template_code' => $result['TemplateCode'],
            $result['Message'],
        ]);

        $template->update($data);

        return $template->refresh();
    }

    /**
     * @param AliyunSmsTemplate $template
     * @return array
     */
    private function buildQueryTemplateOptions(AliyunSmsTemplate $template): array
    {
        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'TemplateCode' => $template->template_code,
            ],
        ];
    }

    /**
     * @param AliyunSmsTemplate $template
     * @param array $result
     * @return AliyunSmsTemplate
     */
    private function updateTemplateStatus(AliyunSmsTemplate $template, array $result): AliyunSmsTemplate
    {
        if ($template && $template->status !== $result['TemplateStatus']) {
            $template->update([
                'status' => $result['TemplateStatus'],
                'reason' => $result['Reason'],
                'message' => $result['Message'],
                'request_id' => $result['RequestId'],
            ]);
        }

        return $template->refresh();
    }

    /**
     * @param array $variables
     * @param AliyunSmsTemplate $template
     * @return array
     */
    private function buildModifyTemplateOptions(array $variables, AliyunSmsTemplate $template): array
    {
        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'TemplateCode' => $template->template_code,
                'TemplateType' => $variables['type'] ?: $template->type,
                'TemplateName' => $variables['title'] ?: $template->title,
                'TemplateContent' => $variables['content'] ?: $template->content,
                'Remark' => $variables['remark'] ?: $template->remark,
            ],
        ];
    }

    /**
     * @param AliyunSmsTemplate $template
     * @return array
     */
    private function buildDeleteTemplateOptions(AliyunSmsTemplate $template): array
    {
        return [
            'query' => [
                'RegionId' => config('sms.region_id'),
                'TemplateCode' => $template->template_code,
            ],
        ];
    }

    /**
     * @param array $result
     * @return bool
     */
    private function destroyTemplate(array $result): bool
    {
        return AliyunSmsTemplate::where('template_code', $result['TemplateCode'])->delete();
    }

    /**
     * @param AliyunSmsTemplate $template
     * @return bool
     * @throws \Exception
     */
    private function verifyTemplateModifiable(AliyunSmsTemplate $template): bool
    {
        if ($template->status !== AliyunSmsTemplate::STATUS_REJECTED) {
            throw new \Exception('You cannot modify template so far');
        }

        return true;
    }

    /**
     * @param string $content
     * @return array
     */
    private function getTemplateParameters(string $content): array
    {
        preg_match_all('/\$\{[^\}]+\}/', $content, $matches);

        return $matches[1] ?? [];
    }
}
