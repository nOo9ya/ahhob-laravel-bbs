<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('결제 수단 코드');
            $table->string('name')->comment('결제 수단 이름');
            $table->string('gateway')->comment('지원하는 게이트웨이');
            $table->boolean('is_active')->default(true)->comment('활성화 여부');
            $table->integer('sort_order')->default(0)->comment('정렬 순서');
            
            // 결제 수단별 설정
            $table->json('config')->nullable()->comment('결제 수단 설정');
            
            // 수수료 정보
            $table->decimal('fee_rate', 5, 4)->default(0)->comment('수수료율 (%)');
            $table->integer('fee_fixed')->default(0)->comment('고정 수수료 (원)');
            $table->integer('min_amount')->default(0)->comment('최소 결제 금액');
            $table->integer('max_amount')->default(0)->comment('최대 결제 금액 (0=무제한)');
            
            // 제한 설정
            $table->json('allowed_cards')->nullable()->comment('허용 카드사 목록');
            $table->json('blocked_cards')->nullable()->comment('차단 카드사 목록');
            $table->boolean('require_auth')->default(false)->comment('본인인증 필요 여부');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['gateway', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
};