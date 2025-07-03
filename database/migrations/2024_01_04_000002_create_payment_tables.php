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
        // 결제 수단
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 신용카드, 계좌이체, 카카오페이 등
            $table->string('provider'); // inicis, stripe, kakao 등
            $table->string('code')->unique(); // 내부 코드
            $table->text('description')->nullable();
            $table->decimal('fee_rate', 5, 4)->default(0); // 수수료율 (%)
            $table->decimal('fixed_fee', 8, 2)->default(0); // 고정 수수료
            $table->decimal('min_amount', 10, 2)->default(0); // 최소 결제 금액
            $table->decimal('max_amount', 10, 2)->nullable(); // 최대 결제 금액
            $table->json('supported_currencies')->nullable(); // 지원 통화
            $table->json('settings')->nullable(); // 추가 설정
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
            $table->index('provider');
        });

        // 결제 트랜잭션
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // 고유 거래 ID
            $table->foreignId('order_id')->constrained('shop_orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            
            $table->enum('type', ['payment', 'refund', 'partial_refund', 'cancel'])->default('payment');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KRW');
            $table->decimal('fee_amount', 8, 2)->default(0);
            
            // 게이트웨이 정보
            $table->string('gateway_transaction_id')->nullable(); // PG사 거래 ID
            $table->string('gateway_status')->nullable(); // PG사 상태
            $table->json('gateway_response')->nullable(); // PG사 응답 전체
            $table->string('gateway_error_code')->nullable();
            $table->string('gateway_error_message')->nullable();
            
            // 결제 상세 정보
            $table->json('payment_details')->nullable(); // 카드번호 뒷자리, 은행명 등
            $table->string('approval_number')->nullable(); // 승인번호
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // 환불 관련
            $table->foreignId('refund_transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null');
            $table->decimal('refundable_amount', 10, 2)->nullable();
            $table->string('refund_reason')->nullable();
            
            $table->json('metadata')->nullable(); // 추가 메타데이터
            $table->timestamps();
            
            $table->index(['order_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index(['gateway_transaction_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_methods');
    }
};