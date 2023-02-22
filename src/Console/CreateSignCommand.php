<?php

namespace MobileNowGroup\LaravelAliyunSms\Console;

use Illuminate\Console\Command;
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsSign;

class CreateSignCommand extends Command
{
    /**
     * Constants
     */
    const SIGN_SOURCES = [
        0 => '企事业单位的全称或简称',
        1 => '工信部备案网站的全称或简称',
        2 => 'APP应用的全称或简称',
        3 => '公众号或小程序的全称或简称',
        4 => '电商平台店铺名的全称或简称',
        5 => '商标名的全称或简称',
    ];

    const SIGN_STATUS = [
        0 => 'Pending',
        1 => 'Approved',
        2 => 'Rejected',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:sign:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The command is for developer to create a new sms sign';

    /**
     * @var AliyunSmsSign|null
     */
    private $sign = null;

    /** @var int */
    private $steps = 9;

    /** @var mixed */
    private $progress = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->sign = new AliyunSmsSign;

        $this->progress = $this->output->createProgressBar($this->steps);
        $this->progress->start();

        $this->askName();
    }

    /**
     * Ask for sign name
     */
    private function askName()
    {
        $this->sign->sign_name = $this->ask('Please input sign name', '');

        if (empty($this->sign->sign_name)) {
            $this->error('Sign name can NOT be empty string!');

            $this->askName();
        } elseif (\DB::table('aliyun_sms_signs')->where('sign_name', $this->sign->sign_name)->first()) {
            $this->sign->sign_name = '';
            $this->error('This sign name has been occupied!');
            $this->askName();
        } else {
            $this->progress->advance();
            $this->askSource();
        }
    }

    /**
     * Ask for source address
     */
    private function askSource()
    {
        $source = $this->choice("\nPlease input your sign source", static::SIGN_SOURCES, 0);
        $this->sign->sign_source = $this->findSignSource($source);

        $this->progress->advance();
        $this->askRemark();
    }

    /**
     * Ask for sign remark
     */
    private function askRemark()
    {
        $this->sign->remark = $this->ask('Please input sign remark', '');

        $this->progress->advance();
        $this->askMessage();
    }

    /**
     * Ask for sign message
     */
    private function askMessage()
    {
        $this->sign->message = $this->ask('Please input sign message', '');

        $this->progress->advance();
        $this->askReason();
    }

    /**
     * Ask for sign reason
     */
    private function askReason()
    {
        $this->sign->reason = $this->ask('Please input sign reason', '');

        $this->progress->advance();
        $this->askRequestId();
    }

    /**
     * Ask for sign request_id
     */
    private function askRequestId()
    {
        $this->sign->request_id = $this->ask('Please input sign request id', '');

        $this->progress->advance();
        $this->askWeight();
    }

    /**
     * Ask for sign weight
     */
    private function askWeight()
    {
        $this->sign->weight = $this->choice('Please choose sign weight', range(0, 20), 0);

        $this->progress->advance();
        $this->askStatus();
    }

    /**
     * Ask for sign status
     */
    private function askStatus()
    {
        $status = $this->choice('Please choose sign status', static::SIGN_STATUS, 1);
        $this->sign->status = $this->findSignStatus($status);

        $this->progress->advance();
        $this->createSign();
    }

    /**
     * Create sign.
     */
    private function createSign()
    {
        if ($this->confirm('Apply a new request to aliyun service?', false)) {
            AliyunSms::createSign($this->sign->attributesToArray());
        } else {
            $this->sign->save();
        }

        $this->progress->finish();

        $this->info(sprintf("\nNew sms sign[%s] has created successfully", $this->sign->name));
    }

    /**
     * @param string $source
     * @return false|int|string
     */
    private function findSignSource(string $source = '')
    {
        return array_search($source, static::SIGN_SOURCES);
    }

    /**
     * @param string $status
     * @return false|int|string
     */
    private function findSignStatus(string $status = 'Approved')
    {
        return array_search($status, static::SIGN_STATUS);
    }
}
