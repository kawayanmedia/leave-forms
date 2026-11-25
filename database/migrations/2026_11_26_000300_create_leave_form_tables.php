<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables written by kawayanmedia/leave-forms and the leave card side of
 * kawayanmedia/dtr-pdf.
 *
 * `leave_card` (singular) is the CSC leave *ledger*; the `leave_cards` table
 * created earlier is the per-employee card *header* (name, code, account
 * number). They are deliberately different tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_card')) {
            Schema::create('leave_card', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('employee_id');
                $table->string('particulars', 128)->nullable();
                $table->string('action_take', 32)->nullable();
                $table->date('date')->nullable();
                $table->string('period', 32)->nullable();
                $table->decimal('v_earned', 8, 3)->default(0);
                $table->decimal('s_earned', 8, 3)->default(0);
                $table->decimal('v_abs', 8, 3)->default(0);
                $table->decimal('s_abs', 8, 3)->default(0);
                $table->decimal('v_abs_wop', 8, 3)->default(0);
                $table->decimal('v_balance', 8, 3)->default(0);
                $table->decimal('s_balance', 8, 3)->default(0);
                $table->boolean('enabled')->default(true);
                $table->unsignedBigInteger('leave_type_id')->nullable();
                $table->unsignedBigInteger('special_priv_id')->nullable();
                $table->date('max_leave_date')->nullable();
                $table->unsignedBigInteger('manual_log_id')->nullable();
                $table->date('pass_slip_date')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'employee_id', 'date'], 'leave_card_company_employee_date_index');
                $table->index(['company_id', 'employee_id', 'period'], 'leave_card_company_employee_period_index');
            });
        }

        if (! Schema::hasTable('compensatory_timeoffs')) {
            Schema::create('compensatory_timeoffs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('office_id')->nullable();
                $table->unsignedTinyInteger('month')->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->decimal('days', 6, 2)->nullable();
                $table->text('dates')->nullable();
                $table->date('date_file')->nullable();
                $table->string('type', 16)->default('spent');
                $table->string('status', 16)->default('inactive');
                $table->string('approved_by', 32)->nullable();
                $table->timestamps();

                $table->index(['company_id', 'employee_id', 'status'], 'cto_company_employee_status_index');
            });
        }

        if (! Schema::hasTable('manual_log')) {
            Schema::create('manual_log', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('office_id')->nullable();
                $table->string('cover_if_ob_or_leave', 128)->nullable();
                $table->string('cover_if_ob_or_leave2', 128)->nullable();
                $table->string('multiple', 64)->nullable();
                $table->string('log_type', 16)->nullable();
                $table->string('notes', 255)->nullable();
                $table->string('username', 32)->nullable();
                $table->timestamps();

                $table->index(['company_id', 'employee_id'], 'manual_log_company_employee_index');
            });
        }

        if (Schema::hasTable('leave_applications') && ! Schema::hasColumn('leave_applications', 'approved_by')) {
            Schema::table('leave_applications', function (Blueprint $table): void {
                $table->string('approved_by', 32)->nullable()->after('approved');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leave_applications') && Schema::hasColumn('leave_applications', 'approved_by')) {
            Schema::table('leave_applications', function (Blueprint $table): void {
                $table->dropColumn('approved_by');
            });
        }

        Schema::dropIfExists('manual_log');
        Schema::dropIfExists('compensatory_timeoffs');
        Schema::dropIfExists('leave_card');
    }
};
