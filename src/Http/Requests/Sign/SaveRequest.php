<?php

namespace MobileNowGroup\LaravelAliyunSms\Http\Requests\Sign;

use Illuminate\Foundation\Http\FormRequest;
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsSign;

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
            'sign_name' => 'required',
            'sign_source' => 'required|integer',
            'remark' => 'required',
        ];
    }

    /**
     * @return AliyunSmsSign
     */
    public function handle(): AliyunSmsSign
    {
        if ($this->sign) {
            $this->sign->update($this->input());

            return AliyunSms::modifySign($this->except(['sign_name']), $this->sign);
        }

        return AliyunSms::createSign($this->input());
    }
}
