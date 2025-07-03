<?php

namespace App\Services\Shop;

use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\InventoryHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /*
    |--------------------------------------------------------------------------
    | 재고 관리 (Inventory Management)
    |--------------------------------------------------------------------------
    */
    // region --- 재고 관리 (Inventory Management) ---

    /**
     * 재고 수량 업데이트
     */
    public function updateStock(int $productId, int $quantity, string $reason = '', string $type = 'adjustment'): Product
    {
        $product = Product::findOrFail($productId);

        if (!$product->track_stock) {
            throw new \InvalidArgumentException('이 상품은 재고를 추적하지 않습니다.');
        }

        DB::beginTransaction();
        try {
            $previousStock = $product->stock_quantity;
            $product->update(['stock_quantity' => $quantity]);

            // 재고 변경 이력 기록
            $this->logInventoryChange($product, $previousStock, $quantity, $reason, $type);

            DB::commit();
            return $product->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 재고 증가
     */
    public function increaseStock(int $productId, int $quantity, string $reason = ''): Product
    {
        $product = Product::findOrFail($productId);

        if (!$product->track_stock) {
            throw new \InvalidArgumentException('이 상품은 재고를 추적하지 않습니다.');
        }

        DB::beginTransaction();
        try {
            $previousStock = $product->stock_quantity;
            $newStock = $previousStock + $quantity;
            $product->update(['stock_quantity' => $newStock]);

            // 재고 변경 이력 기록
            $this->logInventoryChange($product, $previousStock, $newStock, $reason, 'increase');

            DB::commit();
            return $product->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 재고 감소
     */
    public function decreaseStock(int $productId, int $quantity, string $reason = ''): Product
    {
        $product = Product::findOrFail($productId);

        if (!$product->track_stock) {
            throw new \InvalidArgumentException('이 상품은 재고를 추적하지 않습니다.');
        }

        if ($product->stock_quantity < $quantity) {
            throw new \InvalidArgumentException('재고가 부족합니다.');
        }

        DB::beginTransaction();
        try {
            $previousStock = $product->stock_quantity;
            $newStock = $previousStock - $quantity;
            $product->update(['stock_quantity' => $newStock]);

            // 재고 변경 이력 기록
            $this->logInventoryChange($product, $previousStock, $newStock, $reason, 'decrease');

            DB::commit();
            return $product->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 대량 재고 업데이트
     */
    public function bulkUpdateStock(array $stockData, string $reason = ''): array
    {
        $results = [];

        DB::beginTransaction();
        try {
            foreach ($stockData as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                $itemReason = $item['reason'] ?? $reason;

                $product = $this->updateStock($productId, $quantity, $itemReason, 'bulk_update');
                $results[] = $product;
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 재고 조회 및 분석 (Inventory Retrieval & Analysis)
    |--------------------------------------------------------------------------
    */
    // region --- 재고 조회 및 분석 (Inventory Retrieval & Analysis) ---

    /**
     * 재고 현황 조회
     */
    public function getInventoryStatus(array $filters = []): array
    {
        $query = Product::where('track_stock', true);

        // 필터 적용
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['low_stock_threshold'])) {
            $threshold = $filters['low_stock_threshold'];
        } else {
            $threshold = 10;
        }

        $totalProducts = $query->count();
        $inStockProducts = $query->where('stock_quantity', '>', 0)->count();
        $outOfStockProducts = $query->where('stock_quantity', '<=', 0)->count();
        $lowStockProducts = $query->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', $threshold)
            ->count();

        $totalInventoryValue = $query->sum(DB::raw('stock_quantity * price'));
        $averageStockLevel = $query->avg('stock_quantity');

        return [
            'total_products' => $totalProducts,
            'in_stock_products' => $inStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'low_stock_products' => $lowStockProducts,
            'total_inventory_value' => $totalInventoryValue,
            'average_stock_level' => round($averageStockLevel, 2),
            'stock_turnover_rate' => $this->calculateStockTurnoverRate(),
        ];
    }

    /**
     * 품절 상품 목록
     */
    public function getOutOfStockProducts(): Collection
    {
        return Product::with(['category'])
            ->where('track_stock', true)
            ->where('stock_quantity', '<=', 0)
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * 재고 부족 상품 목록
     */
    public function getLowStockProducts(int $threshold = 10): Collection
    {
        return Product::with(['category'])
            ->where('track_stock', true)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', $threshold)
            ->where('is_active', true)
            ->orderBy('stock_quantity', 'asc')
            ->get();
    }

    /**
     * 재고 과다 상품 목록
     */
    public function getOverStockProducts(int $threshold = 1000): Collection
    {
        return Product::with(['category'])
            ->where('track_stock', true)
            ->where('stock_quantity', '>', $threshold)
            ->where('is_active', true)
            ->orderBy('stock_quantity', 'desc')
            ->get();
    }

    /**
     * 재고 변경 이력 조회
     */
    public function getInventoryHistory(int $productId = null, array $filters = [], int $perPage = 20)
    {
        $query = InventoryHistory::with(['product']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        // 필터 적용
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 재고 예측 및 분석 (Inventory Forecasting & Analysis)
    |--------------------------------------------------------------------------
    */
    // region --- 재고 예측 및 분석 (Inventory Forecasting & Analysis) ---

    /**
     * 재고 회전율 계산
     */
    public function calculateStockTurnoverRate(int $days = 30): float
    {
        $totalSales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', now()->subDays($days))
            ->where('orders.status', 'completed')
            ->sum('order_items.quantity');

        $averageInventory = Product::where('track_stock', true)
            ->avg('stock_quantity');

        return $averageInventory > 0 ? round($totalSales / $averageInventory, 2) : 0;
    }

    /**
     * 재고 소진 예상 일수
     */
    public function calculateStockRunoutDays(int $productId): ?int
    {
        $product = Product::findOrFail($productId);

        if (!$product->track_stock || $product->stock_quantity <= 0) {
            return null;
        }

        // 최근 30일 평균 판매량 계산
        $averageDailySales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->where('orders.status', 'completed')
            ->avg('order_items.quantity');

        if ($averageDailySales <= 0) {
            return null;
        }

        return ceil($product->stock_quantity / $averageDailySales);
    }

    /**
     * 재고 보충 추천
     */
    public function getRestockRecommendations(int $leadTimeDays = 7): Collection
    {
        $products = Product::where('track_stock', true)
            ->where('is_active', true)
            ->get();

        $recommendations = collect();

        foreach ($products as $product) {
            $runoutDays = $this->calculateStockRunoutDays($product->id);
            
            if ($runoutDays && $runoutDays <= $leadTimeDays) {
                $recommendations->push([
                    'product' => $product,
                    'current_stock' => $product->stock_quantity,
                    'estimated_runout_days' => $runoutDays,
                    'recommended_restock_quantity' => $this->calculateRestockQuantity($product->id),
                    'priority' => $runoutDays <= 3 ? 'high' : 'medium',
                ]);
            }
        }

        return $recommendations->sortBy('estimated_runout_days');
    }

    /**
     * 재고 보충 수량 계산
     */
    public function calculateRestockQuantity(int $productId, int $targetDays = 30): int
    {
        // 최근 30일 평균 판매량 계산
        $averageDailySales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->where('orders.status', 'completed')
            ->avg('order_items.quantity');

        if ($averageDailySales <= 0) {
            return 10; // 기본값
        }

        $targetStock = ceil($averageDailySales * $targetDays);
        $currentStock = Product::find($productId)->stock_quantity ?? 0;

        return max(0, $targetStock - $currentStock);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 비공개 헬퍼 메서드 (Private Helper Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 비공개 헬퍼 메서드 (Private Helper Methods) ---

    /**
     * 재고 변경 이력 기록
     */
    private function logInventoryChange(Product $product, int $previousStock, int $newStock, string $reason, string $type): void
    {
        InventoryHistory::create([
            'product_id' => $product->id,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'change_quantity' => $newStock - $previousStock,
            'reason' => $reason,
            'type' => $type,
            'changed_by' => auth()->id(),
        ]);
    }

    // endregion
}