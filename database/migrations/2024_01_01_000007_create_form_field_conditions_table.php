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
        Schema::create('form_field_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained('form_fields')->cascadeOnDelete()->comment('The field that shows/hides');
            $table->foreignId('condition_field_id')->constrained('form_fields')->cascadeOnDelete()->comment('The field being checked');
            $table->string('operator')->comment('equals, not_equals, contains, greater_than, less_than, is_empty, is_not_empty');
            $table->string('condition_value')->nullable();
            $table->string('action')->default('show')->comment('show, hide');
            $table->timestamps();

            $table->index('field_id');
            $table->index('condition_field_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_field_conditions');
    }
};
