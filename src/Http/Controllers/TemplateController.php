<?php

namespace MobileNowGroup\LaravelAliyunSms\Http\Controllers;

use Illuminate\Routing\Controller;
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsTemplate;
use MobileNowGroup\LaravelAliyunSms\Http\Requests\Template\SaveRequest;

class TemplateController extends Controller
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|AliyunSmsTemplate[]
     */
    public function index()
    {
        return AliyunSmsTemplate::withTrashed()->orderBy('weight')->latest()->get()->map(function (AliyunSmsTemplate $template) {
            if ($template->status === AliyunSmsTemplate::STATUS_PENDING) {
                return AliyunSms::queryTemplate($template);
            }

            return $template;
        });
    }

    /**
     * @param SaveRequest $request
     * @return \Illuminate\Http\JsonResponse|AliyunSmsTemplate
     */
    public function store(SaveRequest $request)
    {
        try {
            return $request->handle();
        } catch (\Exception $exception) {
            logger()->error($exception->getTraceAsString());

            return response()->json([
                'result' => 'failed',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * @param SaveRequest $request
     * @param AliyunSmsTemplate $template
     * @return \Illuminate\Http\JsonResponse|AliyunSmsTemplate
     */
    public function update(SaveRequest $request, AliyunSmsTemplate $template)
    {
        try {
            return $request->handle();
        } catch (\Exception $exception) {
            return response()->json([
                'result' => 'failed',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * @param AliyunSmsTemplate $template
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy($template)
    {
        /** @var AliyunSmsTemplate $templateModel */
        $templateModel = AliyunSmsTemplate::withTrashed()->findOrFail($template);

        try {
            AliyunSms::deleteTemplate($templateModel);

            if ($templateModel->trashed()) {
                $templateModel->forceDelete();
            } else {
                $templateModel->delete();
            }

            return response()->json([
                'result' => 'success',
            ]);
        } catch (\Exception $exception) {
            // if sign has been delete from aliyun, we only need to delete it from our side
            if ($exception instanceof \AlibabaCloud\Client\Exception\ClientException &&
                $exception->getErrorCode() === 'isv.SMS_TEMPLATE_ILLEGAL') {
                if ($templateModel->trashed()) {
                    $templateModel->forceDelete();
                } else {
                    $templateModel->delete();
                }

                return response()->json([
                    'result' => 'success',
                ]);
            }

            return response()->json([
                'result' => 'failed',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
