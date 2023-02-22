<p align="center"><img src="http://res.cloudinary.com/guorenjun/image/upload/v1531809978/MN_LOGO_3.png" width="300"></p>

### About This Package
This package is an Aliyun SMS toolkit.

### Installation

Add repository into composer.json
```
"repositories": [
    {
        "type": "vcs",
        "url":  "git@github.com:MobileNowGroup/laravel-aliyun-sms.git"
    }
]
```

And run commands below:
```bash
$ composer require mobilenowgroup/laravel-aliyun-sms
$ php artisan migrate

```

### Usage
#### Publish config file
```bash
$ php artisan vendor:publish --provider="MobileNowGroup\LaravelAliyunSms\SmsServiceProvider" --tag=sms
// Optional
$ php artisan vendor:publish --provider="MobileNowGroup\LaravelAliyunSms\SmsServiceProvider" --tag=aliyun

```

##### Publish resources
```bash
$ php artisan vendor:publish --provider="MobileNowGroup\LaravelAliyunSms\SmsServiceProvider" --tag=resources
```

### How to use

#### Create sign
```php
<?php
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;

AliyunSms::createSign([
    'sign_name' => '魔博脑',
    'sign_source' => 1,
    'remark' => '测试用，www.mobilenowgroup.com',
]);
```

#### Query sign status
```php
<?php
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsSign;

$sign = AliyunSmsSign::first();
AliyunSms::querySign($sign);
```

#### Delete sign
```php
<?php
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsSign;

$sign = AliyunSmsSign::first();
AliyunSms::deleteSign($sign);
```

#### Create template
```php
<?php
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;

AliyunSms::createTemplate([
    'title' => '测试模板',
    'content' => '此处填写模板内容',
    'remark' => '此处填写申请模板的理由',
    'type' => 1,
]);
```

#### Query template status
```php
<?php
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsTemplate;

$template = AliyunSmsTemplate::first();
AliyunSms::queryTemplate($template);
```

#### Delete template
```php
<?php
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsTemplate;

$template = AliyunSmsTemplate::first();
AliyunSms::deleteTemplate($template);
```

#### Send SMS
```php
<?php
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;

AliyunSms::send([
    'phone_numbers' => '13600000000,13700000000', // Separated by comma
    'aliyun_sms_sign_id' => 1,
    'aliyun_sms_template_id' => 1,
    'params' => '',
    'send_datetime' => '2020-06-01 00:00:00'
]);
```

#### Query sms result
```php
<?php
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;

AliyunSms::query([
    'phone_number' => '13600000000',
    'send_date' => '20200601',
]);
```

***

> More information: [Documentation](https://github.com/MobileNowGroup/laravel-aliyun-sms/wiki)
