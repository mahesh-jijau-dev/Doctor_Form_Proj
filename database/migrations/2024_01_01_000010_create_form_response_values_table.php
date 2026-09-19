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
        Schema::create('form_response_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('form_responses')->cascadeOnDelete();
            $table->foreignId('field_id')->nullable()->constrained('form_fields')->nullOnDelete();
            $table->string('field_label')->comment('Snapshot of label at submission time');
            $table->string('field_type')->comment('Snapshot of type at submission time');
            $table->longText('value')->nullable()->comment('For single values');
            $table->json('values')->nullable()->comment('For multi-value like checkboxes');
            $table->string('file_path')->nullable()->comment('For file uploads');
            $table->string('file_original_name')->nullable();
            $table->timestamps();

            $table->index('response_id');
            $table->index('field_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_response_values');
    }
};
