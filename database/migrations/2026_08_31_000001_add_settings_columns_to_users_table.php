<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title')->nullable()->after('name');
            $table->string('phone')->nullable()->after('job_title');
            $table->boolean('dark_mode')->default(false);
            $table->string('currency')->default('BRL');
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->boolean('notify_critical_stock')->default(true);
            $table->boolean('notify_low_stock')->default(true);
            $table->boolean('notify_daily_financial_report')->default(false);
            $table->boolean('notify_report_generated')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'job_title', 'phone', 'dark_mode', 'currency', 'timezone',
                'notify_critical_stock', 'notify_low_stock',
                'notify_daily_financial_report', 'notify_report_generated',
            ]);
        });
    }
};
