<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique()->comment('결제 거래 ID');
            $table->foreignId('order_id')->constrained('shop_orders')->onDelete('cascade');
            $table->string('payment_gateway')->comment('결제 게이트웨이 (inicis, kg_inicis, stripe)');
            $table->string('payment_method')->comment('결제 수단 (card, bank, virtual_account, etc.)');
            $table->decimal('amount', 10, 2)->comment('결제 금액');
            $table->string('currency', 3)->default('KRW')->comment('통화');
            
            // 결제 상태
            $table->enum('status', [
                'pending',     // 대기중
                'processing',  // 처리중
                'completed',   // 완료
                'failed',      // 실패
                'cancelled',   // 취소
                'refunded',    // 환불
                'expired'      // 만료
            ])->default('pending');
            
            // 결제 게이트웨이 응답 데이터
            $table->json('gateway_request')->nullable()->comment('게이트웨이 요청 데이터');
            $table->json('gateway_response')->nullable()->comment('게이트웨이 응답 데이터');
            $table->string('gateway_transaction_id')->nullable()->comment('게이트웨이 거래 ID');
            
            // 결제 정보
            $table->string('card_number')->nullable()->comment('카드번호 (마스킹)');
            $table->string('card_company')->nullable()->comment('카드사');
            $table->string('approval_number')->nullable()->comment('승인번호');
            $table->timestamp('approval_at')->nullable()->comment('승인일시');
            
            // 실패/취소 정보
            $table->string('failure_reason')->nullable()->comment('실패 사유');
            $table->string('cancel_reason')->nullable()->comment('취소 사유');
            $table->timestamp('cancelled_at')->nullable()->comment('취소일시');
            
            // 환불 정보
            $table->decimal('refund_amount', 10, 2)->default(0)->comment('환불 금액');
            $table->string('refund_reason')->nullable()->comment('환불 사유');
            $table->timestamp('refunded_at')->nullable()->comment('환불일시');
            
            // 재시도 관련
            $table->integer('retry_count')->default(0)->comment('재시도 횟수');
            $table->timestamp('last_retry_at')->nullable()->comment('마지막 재시도 시간');
            
            // 웹훅/콜백 관련
            $table->json('webhook_data')->nullable()->comment('웹훅 데이터');
            $table->timestamp('webhook_received_at')->nullable()->comment('웹훅 수신 시간');
            
            $table->timestamps();
            
            // 인덱스
            $table->index(['order_id', 'status']);
            $table->index(['payment_gateway', 'status']);
            $table->index(['gateway_transaction_id']);
            $table->index(['approval_at']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
};