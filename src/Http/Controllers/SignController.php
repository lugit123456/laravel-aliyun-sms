<?php

namespace MobileNowGroup\LaravelAliyunSms\Http\Controllers;

use Illuminate\Routing\Controller;
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsSign;
use MobileNowGroup\LaravelAliyunSms\Http\Requests\Sign\SaveRequest;

class SignController extends Controller
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|AliyunSmsSign[]
     */
    public function index()
    {
        return AliyunSmsSign::withTrashed()->orderBy('weight')->latest()->get()->map(function (AliyunSmsSign $sign) {
            if ($sign->status === AliyunSmsSign::STATUS_PENDING) {
                return AliyunSms::querySign($sign);
            }

            return $sign;
        });
    }

    /**
     * @param SaveRequest $request
     * @return \Illuminate\Http\JsonResponse|AliyunSmsSign
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
     * @param AliyunSmsSign $sign
     * @return \Illuminate\Http\JsonResponse|AliyunSmsSign
     */
    public function update(SaveRequest $request, AliyunSmsSign $sign)
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
     * @param AliyunSmsSign $sign
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy($sign)
    {
        /** @var AliyunSmsSign $signModel */
        $signModel = AliyunSmsSign::withTrashed()->findOrFail($sign);

        try {
            AliyunSms::deleteSign($signModel);

            if ($signModel->trashed()) {
                $signModel->forceDelete();
            } else {
                $signModel->delete();
            }

            return response()->json([
                'result' => 'success',
            ]);
        } catch (\Exception $exception) {
            // if sign has been delete from aliyun, we only need to delete it from our side
            if ($exception instanceof \AlibabaCloud\Client\Exception\ClientException &&
                $exception->getErrorCode() === 'isv.SMS_SIGNATURE_ILLEGAL') {
                if ($signModel->trashed()) {
                    $signModel->forceDelete();
                } else {
                    $signModel->delete();
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
