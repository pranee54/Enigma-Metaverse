<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('status');
            $table->index('department');
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->index('state');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->index(['period_year', 'period_month', 'status'], 'attendance_summaries_period_status_index');
        });

        Schema::table('attendance_import_batches', function (Blueprint $table) {
            $table->index(['period_year', 'period_month', 'status'], 'attendance_batches_period_status_index');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('salary_revisions', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['department']);
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropIndex(['state']);
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->dropIndex('attendance_summaries_period_status_index');
        });

        Schema::table('attendance_import_batches', function (Blueprint $table) {
            $table->dropIndex('attendance_batches_period_status_index');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('salary_revisions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
