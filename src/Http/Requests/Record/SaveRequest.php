<?php

namespace MobileNowGroup\LaravelAliyunSms\Http\Requests\Record;

use Illuminate\Foundation\Http\FormRequest;
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsRecord;

class SaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required',
            'aliyun_sms_sign_id' => 'required|exists:aliyun_sms_signs,id',
            'aliyun_sms_template_id' => 'required|exists:aliyun_sms_templates,id',
            'send_datetime' => 'nullable|date_format:"Y-m-d H:i:s"',
            'phone_numbers' => 'required|regex:/^\d{11}(,\d{11}){0,999}$/'
        ];
    }

    /**
     * @return AliyunSmsRecord
     */
    public function handle(): AliyunSmsRecord
    {
        $data = $this->input();

        if (empty($data['send_datetime']) || $data['send_datetime'] < now()) {
            $data['send_datetime'] = now()->format('Y-m-d H:i:s');
        }

        if ($this->record) {
            $data['message'] = null;
            $this->record->update($data);

            return $this->record->refresh();
        }

        return AliyunSms::send($data);
    }
}
