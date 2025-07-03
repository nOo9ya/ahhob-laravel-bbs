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
        // 재고 알림 신청 테이블
        Schema::create('inventory_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->string('email')->nullable(); // 비회원도 알림 신청 가능
            $table->string('phone')->nullable(); // SMS 알림
            $table->enum('notification_type', ['email', 'sms', 'both'])->default('email');
            $table->boolean('is_notified')->default(false);
            $table->datetime('notified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index(['product_id', 'is_active']);
            $table->index(['is_notified', 'is_active']);
        });

        // 재고 알림 발송 내역
        Schema::create('inventory_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('inventory_notifications')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->enum('channel', ['email', 'sms']);
            $table->string('recipient'); // 이메일 주소 또는 전화번호
            $table->text('content');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->datetime('sent_at')->nullable();
            $table->timestamps();

            $table->index(['notification_id', 'status']);
            $table->index(['product_id', 'sent_at']);
        });

        // 관리자 재고 알림 설정
        Schema::create('admin_inventory_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('shop_products')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('shop_categories')->onDelete('cascade');
            $table->enum('alert_type', ['low_stock', 'out_of_stock', 'overstock']);
            $table->integer('threshold_value'); // 임계값
            $table->json('notification_channels'); // ['email', 'slack', 'discord']
            $table->json('recipients'); // 알림 받을 관리자 목록
            $table->boolean('is_active')->default(true);
            $table->datetime('last_notified_at')->nullable();
            $table->timestamps();

            $table->index(['alert_type', 'is_active']);
        });

        // 재고 변동 이력 (로그)
        Schema::create('inventory_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->integer('change_amount'); // 변동량 (+ 증가, - 감소)
            $table->enum('change_type', ['purchase', 'return', 'adjustment', 'initial']);
            $table->string('reference_type')->nullable(); // 관련 모델 타입 (Order, Return 등)
            $table->unsignedBigInteger('reference_id')->nullable(); // 관련 모델 ID
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // 변경한 사용자
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['change_type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_changes');
        Schema::dropIfExists('admin_inventory_alerts');
        Schema::dropIfExists('inventory_notification_logs');
        Schema::dropIfExists('inventory_notifications');
    }
};