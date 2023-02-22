<?php

namespace MobileNowGroup\LaravelAliyunSms\Console;

use Illuminate\Console\Command;
use MobileNowGroup\LaravelAliyunSms\Facades\AliyunSms;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsSign;
use MobileNowGroup\LaravelAliyunSms\Models\AliyunSmsTemplate;

class CreateTemplateCommand extends Command
{
    /**
     * Constants
     */
    const SIGN_TYPES = [
        0 => 'Verification code',
        1 => 'Notification',
        2 => 'Promotion',
        3 => 'Out of mainland',
    ];

    const SIGN_STATUS = [
        0 => 'Pending',
        1 => 'Approved',
        2 => 'Rejected',
    ];

    /**
     * The name and templateature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:template:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The command is for developer to create a new sms template';

    /**
     * @var AliyunSmsTemplate|null
     */
    private $template = null;

    /** @var int */
    private $steps = 13;

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
        $this->template = new AliyunSmsTemplate;

        $this->progress = $this->output->createProgressBar($this->steps);
        $this->progress->start();

        $this->askTitle();
    }

    /**
     * Ask for template content
     */
    private function askTitle()
    {
        $this->template->title = $this->ask('Please input template title', '');

        if (empty($this->template->title)) {
            $this->error('Template title can NOT be empty string!');

            $this->askTitle();
        } elseif (\DB::table('aliyun_sms_templates')->where('title', $this->template->title)->first()) {
            $this->template->title = '';
            $this->error('This template title has been occupied!');
            $this->askTitle();
        } else {
            $this->progress->advance();
            $this->askContent();
        }
    }

    /**
     * Ask for template content
     */
    private function askContent()
    {
        $this->template->content = $this->ask('Please input template content', '');

        if (empty($this->template->content)) {
            $this->error('Template content can NOT be empty string!');

            $this->askContent();
        } else {
            $this->progress->advance();
            $this->askParams();
        }
    }

    /**
     * Ask for template parameters
     */
    private function askParams()
    {
        $this->template->params = $this->ask('Please input template parameters');

        $this->progress->advance();
        $this->askRemark();
    }

    /**
     * Ask for template remark
     */
    private function askRemark()
    {
        $this->template->remark = $this->ask('Please input template remark', '');

        $this->progress->advance();
        $this->askTemplateCode();
    }

    /**
     * Ask for template code
     */
    private function askTemplateCode()
    {
        $this->template->template_code = $this->ask('Please input template code');

        $this->progress->advance();
        $this->askMessage();
    }

    /**
     * Ask for template message
     */
    private function askMessage()
    {
        $this->template->message = $this->ask('Please input template message', '');

        $this->progress->advance();
        $this->askReason();
    }

    /**
     * Ask for template reason
     */
    private function askReason()
    {
        $this->template->reason = $this->ask('Please input template reason', '');

        $this->progress->advance();
        $this->askRequestId();
    }

    /**
     * Ask for template request_id
     */
    private function askRequestId()
    {
        $this->template->request_id = $this->ask('Please input template request id', '');

        $this->progress->advance();
        $this->askType();
    }

    /**
     * Ask for template type
     */
    private function askType()
    {
        $type = $this->choice('Please choose template type', static::SIGN_TYPES, 0);
        $this->template->type = $this->findTemplateType($type);

        $this->progress->advance();
        $this->askRecord();
    }

    /**
     * Ask for template record
     */
    private function askRecord()
    {
        $this->template->record = $this->confirm('SMS result should be stored in database?', false);

        $this->progress->advance();
        $this->askWeight();
    }

    /**
     * Ask for template weight
     */
    private function askWeight()
    {
        $this->template->weight = $this->choice('Please choose template weight', range(0, 20), 0);

        $this->progress->advance();
        $this->askStatus();
    }

    /**
     * Ask for template status
     */
    private function askStatus()
    {
        $status = $this->choice('Please choose template status', static::SIGN_STATUS, 1);
        $this->template->status = $this->findTemplateStatus($status);

        $this->progress->advance();
        $this->createTemplate();
    }

    /**
     * Create template.
     */
    private function createTemplate()
    {
        if ($this->confirm('Apply a new request to aliyun service?', false)) {
            AliyunSms::createTemplate($this->template->attributesToArray());
        } else {
            $this->template->save();
        }

        $this->progress->finish();

        $this->info(sprintf("\nNew sms template[%s] has created successfully", $this->template->title));
    }

    /**
     * @param string $type
     * @return false|int|string
     */
    private function findTemplateType(string $type = '')
    {
        return array_search($type, static::SIGN_TYPES);
    }

    /**
     * @param string $status
     * @return false|int|string
     */
    private function findTemplateStatus(string $status = 'Approved')
    {
        return array_search($status, static::SIGN_STATUS);
    }
}
