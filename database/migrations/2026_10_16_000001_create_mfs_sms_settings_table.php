<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('mfs-sms.settings_table_name', 'mfs_sms_settings');

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('provider', 30)->unique(); // bkash, nagad, rocket, upay, order_settings
                $table->boolean('enabled')->default(true);
                $table->string('title')->nullable();
                $table->string('number', 30)->nullable();
                $table->string('type', 30)->default('Personal'); // Personal, Merchant, Agent, Uddokta
                $table->string('webhook_token', 100)->nullable();
                $table->string('brand_color', 20)->nullable();
                $table->string('ussd_code', 20)->nullable();
                $table->string('sender_id', 30)->nullable();
                $table->json('extra')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('mfs-sms.settings_table_name', 'mfs_sms_settings');
        Schema::dropIfExists($tableName);
    }
};
