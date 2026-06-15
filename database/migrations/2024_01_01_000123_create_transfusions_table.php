<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfusions', function (Blueprint $table) {
            $table->id();
            $table->string('transfusion_id', 255);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('consultation_id')->nullable();
            $table->unsignedBigInteger('donation_id')->nullable();
            $table->enum('blood_group_patient', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->enum('blood_group_donor', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->enum('blood_component', ['whole_blood', 'packed_cells', 'plasma', 'platelets', 'cryoprecipitate']);
            $table->decimal('volume_ml', 8, 2);
            $table->string('blood_bag_number', 255)->nullable();
            $table->text('indication')->nullable();
            $table->enum('cross_match_result', ['compatible', 'incompatible', 'pending'])->default('pending');
            $table->timestamp('cross_match_at')->nullable();
            $table->unsignedBigInteger('cross_matched_by')->nullable();
            $table->timestamp('transfusion_started_at')->nullable();
            $table->timestamp('transfusion_completed_at')->nullable();
            $table->unsignedBigInteger('administered_by')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->enum('status', ['ordered', 'cross_matched', 'in_progress', 'completed', 'adverse_reaction', 'cancelled'])->default('ordered');
            $table->text('pre_transfusion_vitals')->nullable();
            $table->text('post_transfusion_vitals')->nullable();
            $table->text('adverse_reactions')->nullable();
            $table->enum('reaction_severity', ['none', 'mild', 'moderate', 'severe'])->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['administered_by'], 'transfusions_administered_by_foreign');
            $table->index(['branch_id'], 'transfusions_branch_id_foreign');
            $table->index(['consultation_id'], 'transfusions_consultation_id_foreign');
            $table->index(['cross_matched_by'], 'transfusions_cross_matched_by_foreign');
            $table->index(['doctor_id'], 'transfusions_doctor_id_foreign');
            $table->index(['donation_id'], 'transfusions_donation_id_foreign');
            $table->index(['patient_id']);
            $table->index(['status']);
            $table->unique(['transfusion_id']);
            $table->index(['transfusion_started_at']);
            $table->index(['visit_id'], 'transfusions_visit_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfusions');
    }
};
