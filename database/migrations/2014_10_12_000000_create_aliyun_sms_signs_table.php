<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAliyunSmsSignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aliyun_sms_signs', function (Blueprint $table) {
            $table->increments('id');

            $table->string('sign_name', 50)->unique();
            $table->unsignedTinyInteger('sign_source')->default(0)->comment('0: 企事业单位的全称或简称, 1: 工信部备案网站的全称或简称, 2: APP应用的全称或简称, 3: 公众号或小程序的全称或简称, 4: 电商平台店铺名的全称或简称, 5: 商标名的全称或简称');
            $table->text('remark');
            $table->text('message')->nullable()->comment('This value is coming from Aliyun');
            $table->text('reason')->nullable()->comment('This value is coming from Aliyun');
            $table->string('request_id')->nullable()->comment('This value is coming from Aliyun');
            $table->unsignedSmallInteger('weight')->default(0);
            $table->unsignedTinyInteger('status')->default(0)->comment('0: pending, 1: approved, 2: rejected');

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
        Schema::dropIfExists('aliyun_sms_signs');
    }
}
