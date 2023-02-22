<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAliyunSmsRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aliyun_sms_records', function (Blueprint $table) {
            $table->increments('id');

            $table->string('title');
            $table->text('phone_numbers');
            $table->unsignedInteger('aliyun_sms_sign_id');
            $table->unsignedInteger('aliyun_sms_template_id');
            $table->text('params')->nullable();
            $table->string('biz_id')->nullable();
            $table->text('message')->nullable()->comment('This value is coming from Aliyun');
            $table->string('request_id')->nullable()->comment('This value is coming from Aliyun');
            $table->longText('results')->nullable()->comment('This value is coming from Aliyun');
            $table->timestamp('send_datetime')->nullable();
            $table->string('serial_number')->nullable();

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
        Schema::dropIfExists('aliyun_sms_records');
    }
}
