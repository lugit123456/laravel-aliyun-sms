<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAliyunSmsTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aliyun_sms_templates', function (Blueprint $table) {
            $table->increments('id');

            $table->string('title');
            $table->text('content');
            $table->text('remark');
            $table->text('params')->nullable();
            $table->string('template_code', 50)->nullable()->comment('This value is coming from Aliyun');
            $table->text('message')->nullable()->comment('This value is coming from Aliyun');
            $table->text('reason')->nullable()->comment('This value is coming from Aliyun');
            $table->string('request_id')->nullable()->comment('This value is coming from Aliyun');
            $table->unsignedTinyInteger('type')->default(0)->comment('0: verification code, 1: notification, 2: promotion, 3: out of mainland');
            $table->boolean('record')->default(true)->comment('Confirm sms record should be saved or not');
            $table->unsignedSmallInteger('weight')->default(0);
            $table->unsignedTinyInteger('status')->default(0)->comment('0: pending, 1: approved, 2: reject');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aliyun_sms_templates');
    }
}
