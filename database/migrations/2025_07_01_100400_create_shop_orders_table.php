<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 주문 테이블 생성 (Shop Orders)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            
            // 주문 기본 정보
            $table->string('order_number', 50)->unique()->comment('주문번호');
            $table->unsignedBigInteger('user_id')->nullable()->comment('주문자 사용자 ID');
            
            // 주문 상태
            $table->enum('status', [
                'pending',      // 주문 접수
                'confirmed',    // 주문 확인
                'processing',   // 처리 중
                'shipped',      // 배송 중
                'delivered',    // 배송 완료
                'cancelled',    // 주문 취소
                'refunded'      // 환불
            ])->default('pending')->comment('주문 상태');
            
            // 결제 정보
            $table->enum('payment_status', [
                'pending',      // 결제 대기
                'paid',         // 결제 완료
                'failed',       // 결제 실패
                'refunded',     // 환불 완료
                'partially_refunded' // 부분 환불
            ])->default('pending')->comment('결제 상태');
            
            $table->enum('payment_method', [
                'card',         // 신용카드
                'bank_transfer', // 계좌이체
                'virtual_account', // 가상계좌
                'mobile',       // 휴대폰
                'point'         // 포인트
            ])->nullable()->comment('결제 방법');
            
            // 가격 정보
            $table->decimal('subtotal', 10, 2)->comment('상품 소계');
            $table->decimal('shipping_cost', 8, 2)->default(0)->comment('배송비');
            $table->decimal('tax_amount', 8, 2)->default(0)->comment('세금');
            $table->decimal('discount_amount', 8, 2)->default(0)->comment('할인 금액');
            $table->decimal('total_amount', 10, 2)->comment('총 주문 금액');
            
            // 주문자 정보 (스냅샷)
            $table->string('customer_name', 100)->comment('주문자명');
            $table->string('customer_email')->comment('주문자 이메일');
            $table->string('customer_phone', 20)->comment('주문자 전화번호');
            
            // 배송 정보
            $table->string('shipping_name', 100)->comment('수령인명');
            $table->string('shipping_phone', 20)->comment('수령인 전화번호');
            $table->string('shipping_address_line1')->comment('배송지 주소1');
            $table->string('shipping_address_line2')->nullable()->comment('배송지 주소2');
            $table->string('shipping_city', 50)->comment('배송지 시/군/구');
            $table->string('shipping_state', 50)->comment('배송지 시/도');
            $table->string('shipping_postal_code', 10)->comment('배송지 우편번호');
            $table->text('shipping_notes')->nullable()->comment('배송 메모');
            
            // 추적 정보
            $table->string('tracking_number')->nullable()->comment('송장번호');
            $table->string('shipping_company')->nullable()->comment('택배회사');
            
            // 결제 추적 정보
            $table->string('payment_transaction_id')->nullable()->comment('결제 거래 ID');
            $table->json('payment_response')->nullable()->comment('결제 응답 데이터');
            
            // 쿠폰 및 할인
            $table->string('coupon_code')->nullable()->comment('사용된 쿠폰 코드');
            $table->decimal('coupon_discount', 8, 2)->default(0)->comment('쿠폰 할인 금액');
            
            // 관리자 메모
            $table->text('admin_notes')->nullable()->comment('관리자 메모');
            
            // 타임스탬프
            $table->timestamps();
            $table->timestamp('confirmed_at')->nullable()->comment('주문 확인 시간');
            $table->timestamp('shipped_at')->nullable()->comment('배송 시작 시간');
            $table->timestamp('delivered_at')->nullable()->comment('배송 완료 시간');
            $table->timestamp('cancelled_at')->nullable()->comment('주문 취소 시간');
            
            // 인덱스
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index('order_number');
            $table->index('tracking_number');
            
            // 외래 키
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_orders');
    }
};