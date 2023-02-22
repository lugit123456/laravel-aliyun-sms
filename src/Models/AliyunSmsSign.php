<?php

namespace MobileNowGroup\LaravelAliyunSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AliyunSmsSign extends Model
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
        'sign_name',
        'sign_source',
        'remark',
        'message',
        'reason',
        'request_id',
        'weight',
        'status',
    ];
}
