<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            // Add missing fields to match Drug model
            $table->string('drug_code')->unique()->after('generic_name');
            $table->string('unit')->nullable()->after('strength');
            $table->text('indications')->nullable()->after('description');
            $table->text('contraindications')->nullable()->after('indications');
            $table->text('side_effects')->nullable()->after('contraindications');
            $table->text('dosage_instructions')->nullable()->after('side_effects');
            $table->text('storage_conditions')->nullable()->after('dosage_instructions');
            $table->boolean('prescription_required')->default(true)->after('requires_prescription');
            $table->boolean('controlled_substance')->default(false)->after('prescription_required');
            $table->boolean('nhis_covered')->default(false)->after('controlled_substance');
            $table->decimal('cost_price', 10, 2)->default(0)->after('unit_price');
            $table->decimal('selling_price', 10, 2)->default(0)->after('cost_price');
            $table->decimal('nhis_price', 10, 2)->default(0)->after('selling_price');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            
            // Add foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            // Drop added columns
            $table->dropColumn([
                'drug_code', 'unit', 'indications', 'contraindications',
                'side_effects', 'dosage_instructions', 'storage_conditions',
                'prescription_required', 'controlled_substance', 'nhis_covered', 
                'cost_price', 'selling_price', 'nhis_price', 'created_by', 'updated_by'
            ]);
        });
    }
};