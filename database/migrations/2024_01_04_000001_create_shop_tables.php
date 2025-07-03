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
        // 쇼핑몰 카테고리
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->nestedSet(); // _lft, _rgt for nested set model
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });

        // 상품
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('shop_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->string('sku')->unique()->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('in_stock')->default(true);
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            $table->json('gallery')->nullable(); // 이미지 경로들
            $table->string('featured_image')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->json('dimensions')->nullable(); // length, width, height
            $table->json('attributes')->nullable(); // 색상, 크기 등 custom attributes
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('seo_keywords')->nullable();
            
            // 성능 필드 (add_performance_fields_to_shop_tables에서 추가된 내용)
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->integer('sales_count')->default(0);
            $table->integer('views_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['category_id', 'status']);
            $table->index(['status', 'in_stock']);
            $table->index('average_rating');
            $table->index('sales_count');
            $table->fullText(['name', 'description', 'content']);
        });

        // 상품 옵션
        Schema::create('shop_product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->string('name'); // 색상, 크기 등
            $table->json('values'); // ['빨강', '파랑', '노랑'] 또는 ['S', 'M', 'L']
            $table->boolean('is_required')->default(false);
            $table->enum('type', ['select', 'radio', 'checkbox'])->default('select');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['product_id', 'sort_order']);
        });

        // 재고 알림
        Schema::create('inventory_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('product_options')->nullable(); // 특정 옵션
            $table->boolean('is_notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'user_id']);
            $table->index(['is_notified', 'created_at']);
        });

        // 상품 추천
        Schema::create('product_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->foreignId('recommended_product_id')->constrained('shop_products')->onDelete('cascade');
            $table->enum('type', ['related', 'upsell', 'cross_sell', 'similar'])->default('related');
            $table->decimal('score', 5, 4)->default(0); // 추천 점수
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['product_id', 'recommended_product_id', 'type']);
            $table->index(['product_id', 'type', 'score']);
        });

        // 장바구니
        Schema::create('shop_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); // 비회원용
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->json('product_options')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2); // 담을 당시 가격
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('session_id');
            $table->index(['product_id', 'created_at']);
        });

        // 주문
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('KRW');
            
            // 배송 정보
            $table->json('shipping_address');
            $table->json('billing_address')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // 결제 정보
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->json('payment_details')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('payment_status');
        });

        // 주문 상품
        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('shop_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('cascade');
            $table->string('product_name'); // 주문 당시 상품명
            $table->string('product_sku')->nullable();
            $table->json('product_options')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // 주문 당시 가격
            $table->decimal('total', 10, 2); // quantity * price
            $table->timestamps();
            
            $table->index(['order_id', 'product_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_order_items');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('shop_carts');
        Schema::dropIfExists('product_recommendations');
        Schema::dropIfExists('inventory_notifications');
        Schema::dropIfExists('shop_product_options');
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('shop_categories');
    }
};