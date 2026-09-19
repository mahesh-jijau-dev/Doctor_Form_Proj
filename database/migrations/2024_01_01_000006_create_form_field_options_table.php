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
        Schema::create('form_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained('form_fields')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('is_other')->default(false)->comment("For 'Other' option");
            $table->timestamps();

            $table->index(['field_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_field_options');
    }
};
