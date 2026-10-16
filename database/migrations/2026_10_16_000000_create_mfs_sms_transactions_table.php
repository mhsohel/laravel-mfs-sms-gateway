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
        $tableName = config('mfs-sms.table_name', 'mfs_sms_transactions');

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('provider', 30)->default('bkash')->index(); // bkash, nagad, rocket, upay
                $table->string('order_id', 64)->nullable()->index();
                $table->string('sender', 50)->nullable(); // e.g. bKash, NAGAD, 16247
                $table->string('sender_number', 30)->nullable()->index(); // customer mobile number
                $table->decimal('amount', 12, 2)->default(0)->index();
                $table->string('trx_id', 50)->nullable()->index(); // Unique transaction ID from SMS
                $table->string('reference', 100)->nullable()->index(); // Ref from SMS
                $table->text('raw_sms');
                $table->string('status', 30)->default('unmatched')->index(); // unmatched, matched, duplicate, manual_verified
                $table->timestamp('matched_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['provider', 'status']);
                $table->index(['provider', 'trx_id']);
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
        $tableName = config('mfs-sms.table_name', 'mfs_sms_transactions');
        Schema::dropIfExists($tableName);
    }
};
