<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghs_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_id', 255);
            $table->enum('report_type', ['monthly_disease_surveillance', 'weekly_disease_surveillance', 'idsr', 'maternal_health', 'child_health', 'immunization', 'malaria', 'tuberculosis', 'hiv_aids', 'covid19', 'births_deaths', 'quarterly_report', 'annual_report', 'other']);
            $table->string('report_period', 255);
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->integer('reporting_month')->nullable();
            $table->integer('reporting_quarter')->nullable();
            $table->integer('reporting_year');
            $table->longText('disease_data')->nullable();
            $table->integer('total_cases')->default(0);
            $table->integer('total_deaths')->default(0);
            $table->integer('total_recoveries')->default(0);
            $table->integer('anc_visits')->default(0);
            $table->integer('anc_first_trimester')->default(0);
            $table->integer('deliveries_total')->default(0);
            $table->integer('deliveries_facility')->default(0);
            $table->integer('deliveries_home')->default(0);
            $table->integer('cesarean_sections')->default(0);
            $table->integer('maternal_deaths')->default(0);
            $table->integer('stillbirths')->default(0);
            $table->integer('live_births')->default(0);
            $table->integer('neonatal_deaths')->default(0);
            $table->integer('infant_deaths')->default(0);
            $table->integer('under_five_deaths')->default(0);
            $table->integer('bcg_vaccinations')->default(0);
            $table->integer('opv_vaccinations')->default(0);
            $table->integer('pentavalent_vaccinations')->default(0);
            $table->integer('measles_vaccinations')->default(0);
            $table->integer('yellow_fever_vaccinations')->default(0);
            $table->integer('malaria_cases')->default(0);
            $table->integer('malaria_deaths')->default(0);
            $table->integer('tb_cases')->default(0);
            $table->integer('tb_deaths')->default(0);
            $table->integer('hiv_cases')->default(0);
            $table->integer('hiv_deaths')->default(0);
            $table->integer('cholera_cases')->default(0);
            $table->integer('meningitis_cases')->default(0);
            $table->integer('typhoid_cases')->default(0);
            $table->integer('covid_cases')->default(0);
            $table->integer('covid_deaths')->default(0);
            $table->integer('covid_recoveries')->default(0);
            $table->integer('covid_vaccinations')->default(0);
            $table->longText('additional_indicators')->nullable();
            $table->text('comments')->nullable();
            $table->text('challenges')->nullable();
            $table->text('recommendations')->nullable();
            $table->enum('status', ['draft', 'pending_review', 'reviewed', 'submitted', 'accepted', 'rejected'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->string('report_file_path', 255)->nullable();
            $table->string('supporting_documents', 255)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('facility_code', 255)->nullable();
            $table->string('district', 255)->nullable();
            $table->string('region', 255)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['reporting_year', 'reporting_month']);
            $table->unique(['report_id']);
            $table->index(['report_type']);
            $table->index(['status']);
            $table->index(['submitted_at']);
$table->foreign('branch_id', 'ghs_reports_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('prepared_by', 'ghs_reports_prepared_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('reviewed_by', 'ghs_reports_reviewed_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('submitted_by', 'ghs_reports_submitted_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghs_reports');
    }
};
