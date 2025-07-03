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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general')->index(); // general, attachment, board, etc.
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable(); // Laravel validation rules
            $table->json('options')->nullable(); // For select/radio options
            $table->string('input_type')->default('text'); // text, textarea, select, radio, checkbox, number
            $table->integer('sort_order')->default(0);
            $table->boolean('is_public')->default(false); // 일반 사용자가 볼 수 있는지
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
