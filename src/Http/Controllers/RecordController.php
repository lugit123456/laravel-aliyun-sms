<?php

namespace MobileNowGroup\LaravelAliyunSms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsRecord;
use MobileNowGroup\LaravelAliyunSms\Http\Requests\Record\SaveRequest;

class RecordController extends Controller
{
    /**
     * @return mixed
     */
    public function index(Request $request)
    {
        $isDirty = false;
        $paginator = AliyunSmsRecord::withTrashed()
            ->with(['sign', 'template'])
            ->orderBy('send_datetime', 'desc')
            ->paginate($request->get('limit', 15));

        collect($paginator->items())->each(function (AliyunSmsRecord $record) use (&$isDirty) {
            if ($record->needQuery()) {
                $record->fill(['results' => AliyunSms::queryRecord($record)]);

                if ($record->isDirty(['results'])) {
                    $isDirty = true;
                    $record->save();
                }
            }
        });

        return $isDirty ? AliyunSmsRecord::withTrashed()
            ->with(['sign', 'template'])
            ->orderBy('send_datetime', 'desc')
            ->paginate($request->get('limit', 30)) : $paginator;
    }

    /**
     * @param SaveRequest $request
     * @return \Illuminate\Http\JsonResponse|AliyunSmsRecord
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
     * @param AliyunSmsRecord $record
     * @return \Illuminate\Http\JsonResponse|AliyunSmsRecord
     */
    public function update(SaveRequest $request, AliyunSmsRecord $record)
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
     * @param AliyunSmsRecord $record
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy($record)
    {
        /** @var AliyunSmsRecord $recordModel */
        $recordModel = AliyunSmsRecord::withTrashed()->findOrFail($record);

        try {
            if ($recordModel->trashed()) {
                $recordModel->forceDelete();
            } else {
                $recordModel->delete();
            }

            return response()->json([
                'result' => 'success',
            ]);
        } catch (\Exception $exception) {
            // if sign has been delete from aliyun, we only need to delete it from our side
            if ($exception instanceof \AlibabaCloud\Client\Exception\ClientException &&
                $exception->getErrorCode() === 'isv.SMS_TEMPLATE_ILLEGAL') {
                if ($recordModel->trashed()) {
                    $recordModel->forceDelete();
                } else {
                    $recordModel->delete();
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
