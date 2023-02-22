<?php

namespace MobileNowGroup\LaravelAliyunSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AliyunSmsTemplate extends Model
{
    use SoftDeletes;

    /** Constants */
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'content',
        'remark',
        'params',
        'template_code',
        'message',
        'reason',
        'request_id',
        'type',
        'weight',
        'status',
        'record',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'params' => 'array',
    ];
}
