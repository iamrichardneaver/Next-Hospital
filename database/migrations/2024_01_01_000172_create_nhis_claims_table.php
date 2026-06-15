<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nhis_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_id', 255);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('insurance_policy_id')->nullable();
            $table->string('nhis_number', 255);
            $table->string('membership_id', 255)->nullable();
            $table->enum('scheme_type', ['nhis', 'premium', 'exemption', 'other'])->default('nhis');
            $table->string('scheme_code', 255)->nullable();
            $table->date('policy_start_date')->nullable();
            $table->date('policy_expiry_date')->nullable();
            $table->enum('member_status', ['active', 'inactive', 'expired', 'suspended'])->default('active');
            $table->string('facility_code', 255)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('visit_date');
            $table->enum('visit_type', ['OPD', 'IPD', 'emergency', 'maternity', 'specialist'])->default('OPD');
            $table->string('admission_number', 255)->nullable();
            $table->date('admission_date')->nullable();
            $table->date('discharge_date')->nullable();
            $table->integer('days_admitted')->default(0);
            $table->string('icd_code', 255)->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('procedures')->nullable();
            $table->text('medications')->nullable();
            $table->text('investigations')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->decimal('nhis_covered_amount', 10, 2)->default(0.00);
            $table->decimal('patient_copay', 10, 2)->default(0.00);
            $table->decimal('claimed_amount', 10, 2)->default(0.00);
            $table->decimal('approved_amount', 10, 2)->default(0.00);
            $table->decimal('rejected_amount', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('outstanding_amount', 10, 2)->default(0.00);
            $table->longText('claim_items')->nullable();
            $table->enum('status', ['draft', 'pending_submission', 'submitted', 'under_review', 'queried', 'approved', 'partially_approved', 'rejected', 'paid', 'archived'])->default('draft');
            $table->date('submission_date')->nullable();
            $table->string('submission_batch_number', 255)->nullable();
            $table->string('claim_reference_number', 255)->nullable();
            $table->date('vetting_date')->nullable();
            $table->unsignedBigInteger('vetted_by')->nullable();
            $table->date('approval_date')->nullable();
            $table->string('approval_reference', 255)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('query_details')->nullable();
            $table->date('query_response_deadline')->nullable();
            $table->text('query_response')->nullable();
            $table->date('query_resolved_at')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->string('payment_voucher_number', 255)->nullable();
            $table->longText('attached_documents')->nullable();
            $table->boolean('has_prescription')->default(0);
            $table->boolean('has_lab_results')->default(0);
            $table->boolean('has_imaging_results')->default(0);
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_updated_by_nhia')->nullable();
            $table->integer('resubmission_count')->default(0);
            $table->timestamp('resubmitted_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['claim_id']);
            $table->unique(['claim_id']);
            $table->index(['nhis_number']);
            $table->index(['payment_date']);
            $table->index(['status']);
            $table->index(['submission_date']);
            $table->index(['visit_date']);
$table->foreign('branch_id', 'nhis_claims_branch_id_foreign')->references('id')->on('branches')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('doctor_id', 'nhis_claims_doctor_id_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('insurance_policy_id', 'nhis_claims_insurance_policy_id_foreign')->references('id')->on('insurance_policies')->onDelete('set null')->onUpdate('restrict');
$table->foreign('invoice_id', 'nhis_claims_invoice_id_foreign')->references('id')->on('invoices')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('patient_id', 'nhis_claims_patient_id_foreign')->references('id')->on('patients')->onDelete('cascade')->onUpdate('restrict');
$table->foreign('prepared_by', 'nhis_claims_prepared_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('submitted_by', 'nhis_claims_submitted_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('vetted_by', 'nhis_claims_vetted_by_foreign')->references('id')->on('users')->onDelete('set null')->onUpdate('restrict');
$table->foreign('visit_id', 'nhis_claims_visit_id_foreign')->references('id')->on('visits')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhis_claims');
    }
};
