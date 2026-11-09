<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('id');
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('dob');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('department')->nullable();
            $table->string('designation');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('work_location');
            $table->string('state_code', 10)->default('TG');
            $table->string('employment_category');
            $table->date('joining_date');
            $table->date('probation_end')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('standard_daily_hours', 4, 1)->default(8);
            $table->string('weekly_rest_day')->default('Sunday');
            $table->string('shift_name')->nullable();
            $table->string('pan')->nullable();
            $table->boolean('pan_not_available')->default(false);
            $table->string('uan')->nullable();
            $table->string('esic_number')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('tax_regime')->default('new');
            $table->timestamp('aadhaar_consent_at')->nullable();
            $table->string('appointment_letter_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::create('employee_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->date('effective_date');
            $table->timestamp('recorded_at');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('annual_ctc', 14, 2)->default(0);
            $table->decimal('monthly_ctc', 14, 2)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('salary_structures')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('class');
            $table->decimal('amount_monthly', 14, 2)->default(0);
            $table->boolean('is_fixed')->default(true);
            $table->boolean('include_in_code_wages')->default(true);
            $table->boolean('include_in_epf')->default(false);
            $table->boolean('include_in_esi')->default(false);
            $table->boolean('include_in_gratuity')->default(false);
            $table->boolean('include_in_bonus')->default(false);
            $table->boolean('include_in_pt')->default(true);
            $table->boolean('include_in_taxable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attendance_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->string('source')->default('csv');
            $table->foreignId('importer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('accepted_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);
            $table->string('status')->default('imported');
            $table->json('report_json')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained('attendance_import_batches')->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedInteger('version')->default(1);
            $table->decimal('payable_days', 5, 1);
            $table->decimal('lop_days', 5, 1)->default(0);
            $table->decimal('overtime_hours', 6, 1)->default(0);
            $table->boolean('overtime_consent')->default(false);
            $table->decimal('leave_encashment_days', 5, 1)->default(0);
            $table->decimal('maternity_benefit_days', 5, 1)->default(0);
            $table->text('remarks')->nullable();
            $table->string('status')->default('staged');
            $table->string('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'period_year', 'period_month']);
        });

        Schema::create('statutory_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->date('effective_from');
            $table->json('payload_json');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['code', 'effective_from']);
        });

        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value_json');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('state')->default('draft');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('finalised_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['period_year', 'period_month']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_summary_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('structure_id')->nullable()->constrained('salary_structures')->nullOnDelete();
            $table->foreignId('rule_version_id')->nullable()->constrained('statutory_rule_versions')->nullOnDelete();
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->decimal('employer_cost', 14, 2)->default(0);
            $table->decimal('carried_forward_deduction', 14, 2)->default(0);
            $table->string('status')->default('ok');
            $table->string('hold_reason')->nullable();
            $table->timestamps();
            $table->unique(['run_id', 'employee_id']);
        });

        Schema::create('payroll_item_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('payroll_items')->cascadeOnDelete();
            $table->string('line_type');
            $table->string('code');
            $table->string('label');
            $table->decimal('amount', 14, 2);
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('advance');
            $table->decimal('principal', 14, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->unsignedInteger('tenure_months')->default(1);
            $table->decimal('monthly_recovery', 14, 2);
            $table->decimal('outstanding', 14, 2);
            $table->string('status')->default('active');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('started_on');
            $table->timestamps();
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->timestamps();
        });

        Schema::create('salary_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('structure_id')->constrained('salary_structures')->cascadeOnDelete();
            $table->date('effective_from');
            $table->string('reason')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('arrears_json')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_exits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->date('resignation_date')->nullable();
            $table->date('last_working_date');
            $table->string('reason')->nullable();
            $table->unsignedInteger('notice_days')->default(0);
            $table->unsignedInteger('notice_shortfall_days')->default(0);
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exit_id')->constrained('employee_exits')->cascadeOnDelete();
            $table->unsignedTinyInteger('step')->default(1);
            $table->decimal('undisputed_wages', 14, 2)->default(0);
            $table->json('other_components_json')->nullable();
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('net_payable', 14, 2)->default(0);
            $table->date('due_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('statement_path')->nullable();
            $table->timestamps();
        });

        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key')->unique();
            $table->string('schema_version')->default('1.0');
            $table->string('payload_path');
            $table->json('meta_json')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->string('ip')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('exports');
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('employee_exits');
        Schema::dropIfExists('salary_revisions');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('payroll_item_lines');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('configurations');
        Schema::dropIfExists('statutory_rule_versions');
        Schema::dropIfExists('attendance_summaries');
        Schema::dropIfExists('attendance_import_batches');
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('employee_status_histories');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
        Schema::dropIfExists('employees');
    }
};
