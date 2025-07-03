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
        Schema::table('users', function (Blueprint $table) {
            $table->string('website')->nullable()->after('bio')->comment('개인 웹사이트');
            $table->string('location')->nullable()->after('website')->comment('거주지역');
            $table->date('birth_date')->nullable()->after('location')->comment('생년월일');
            $table->enum('gender', ['M', 'F', 'O'])->nullable()->after('birth_date')->comment('성별 (M:남성, F:여성, O:기타)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['website', 'location', 'birth_date', 'gender']);
        });
    }
};
