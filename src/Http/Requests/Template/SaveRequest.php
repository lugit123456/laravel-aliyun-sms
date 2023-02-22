<?php

namespace MobileNowGroup\LaravelAliyunSms\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsTemplate;

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
            'content' => 'required',
            'type' => 'required|integer',
            'remark' => 'required',
        ];
    }

    /**
     * @return AliyunSmsTemplate
     */
    public function handle(): AliyunSmsTemplate
    {
        if ($this->template) {
            if ($this->template->status === AliyunSmsTemplate::STATUS_REJECTED) {
                $data = $this->only([
                    'title',
                    'content',
                    'type',
                    'remark',
                ]);
                $this->template->update($data);

                return AliyunSms::modifyTemplate($data, $this->template);
            } else {
                $this->template->update($this->only(['title']));

                return $this->template->refresh();
            }
        }

        return AliyunSms::createTemplate($this->input());
    }
}
