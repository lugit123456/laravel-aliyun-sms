<?php

namespace MobileNowGroup\LaravelAliyunSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class AliyunSmsRecord extends Model
{
    use SoftDeletes;

    /** Constants */
    const STATUS_ALIYUN_PENDING = 1;
    const STATUS_ALIYUN_FAILED = 2;
    const STATUS_ALIYUN_DELIVERED = 3;
    const STATUS_ALIYUN_SUCCESS_MESSAGE = 'OK';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'phone_numbers',
        'aliyun_sms_sign_id',
        'aliyun_sms_template_id',
        'params',
        'biz_id',
        'message',
        'request_id',
        'results',
        'send_datetime',
        'serial_number',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'results' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sign()
    {
        return $this->belongsTo(AliyunSmsSign::class, 'aliyun_sms_sign_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(AliyunSmsTemplate::class, 'aliyun_sms_template_id');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnfinished(Builder $query)
    {
        return $query->where('send_datetime', '<=', now())
            ->whereNull('message');
    }

    /**
     * @return bool
     */
    public function hasSent(): bool
    {
        return $this->message === static::STATUS_ALIYUN_SUCCESS_MESSAGE;
    }

    /**
     * @return bool
     */
    public function needQuery(): bool
    {
        return !$this->trashed() &&
            $this->hasSent() &&
            (!$this->results ||
                !empty($this->results['missing']) ||
                !empty($this->results['pending']));
    }
}
