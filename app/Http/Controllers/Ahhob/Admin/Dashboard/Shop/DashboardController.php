<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 관리자 쇼핑몰 대시보드 컨트롤러 (Admin Shop Dashboard Controller)
    |--------------------------------------------------------------------------
    */
    
    /**
     * 쇼핑몰 대시보드 메인
     */
    public function index(): View
    {
        // 기본 통계
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'out_of_stock' => Product::where('stock_status', 'out_of_stock')->count(),
            'low_stock' => Product::whereColumn('stock_quantity', '<=', 'min_stock_quantity')
                ->where('track_stock', true)->count(),
            
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            
            'total_customers' => User::whereHas('orders')->count(),
            'new_customers_today' => User::whereDate('created_at', today())->count(),
            
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'today_revenue' => Order::where('payment_status', 'paid')
                ->whereDate('created_at', today())->sum('total_amount'),
            'month_revenue' => Order::where('payment_status', 'paid')
                ->whereMonth('created_at', now()->month)->sum('total_amount'),
        ];
        
        // 최근 주문
        $recentOrders = Order::with(['user', 'items'])
            ->latest()
            ->limit(10)
            ->get();
        
        // 인기 상품 (판매량 기준)
        $topProducts = Product::with('category')
            ->where('sales_count', '>', 0)
            ->orderBy('sales_count', 'desc')
            ->limit(10)
            ->get();
        
        // 재고 부족 상품
        $lowStockProducts = Product::with('category')
            ->whereColumn('stock_quantity', '<=', 'min_stock_quantity')
            ->where('track_stock', true)
            ->orderBy('stock_quantity', 'asc')
            ->limit(10)
            ->get();
        
        // 월별 매출 (최근 12개월)
        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths(12))
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_amount) as revenue')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                    'revenue' => $item->revenue,
                ];
            });
        
        return view('ahhob.admin.dashboard.shop.index', compact(
            'stats',
            'recentOrders',
            'topProducts',
            'lowStockProducts',
            'monthlyRevenue'
        ));
    }
    
    /**
     * 매출 분석
     */
    public function sales(Request $request): View
    {
        $dateRange = $request->get('range', '30');
        $startDate = match($dateRange) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subYear(),
            default => now()->subDays(30),
        };
        
        // 기간별 매출 통계
        $salesStats = [
            'total_revenue' => Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $startDate)
                ->sum('total_amount'),
            'total_orders' => Order::where('created_at', '>=', $startDate)->count(),
            'average_order_value' => Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $startDate)
                ->avg('total_amount'),
            'conversion_rate' => $this->calculateConversionRate($startDate),
        ];
        
        // 일별 매출 차트 데이터
        $dailySales = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // 상품별 매출
        $productSales = DB::table('shop_order_items')
            ->join('shop_orders', 'shop_order_items.order_id', '=', 'shop_orders.id')
            ->join('shop_products', 'shop_order_items.product_id', '=', 'shop_products.id')
            ->where('shop_orders.payment_status', 'paid')
            ->where('shop_orders.created_at', '>=', $startDate)
            ->selectRaw('shop_products.name, SUM(shop_order_items.quantity) as quantity, SUM(shop_order_items.total_price) as revenue')
            ->groupBy('shop_products.id', 'shop_products.name')
            ->orderBy('revenue', 'desc')
            ->limit(20)
            ->get();
        
        // 카테고리별 매출
        $categorySales = DB::table('shop_order_items')
            ->join('shop_orders', 'shop_order_items.order_id', '=', 'shop_orders.id')
            ->join('shop_products', 'shop_order_items.product_id', '=', 'shop_products.id')
            ->join('shop_categories', 'shop_products.category_id', '=', 'shop_categories.id')
            ->where('shop_orders.payment_status', 'paid')
            ->where('shop_orders.created_at', '>=', $startDate)
            ->selectRaw('shop_categories.name, SUM(shop_order_items.total_price) as revenue')
            ->groupBy('shop_categories.id', 'shop_categories.name')
            ->orderBy('revenue', 'desc')
            ->get();
        
        return view('ahhob.admin.dashboard.shop.sales', compact(
            'salesStats',
            'dailySales',
            'productSales',
            'categorySales',
            'dateRange'
        ));
    }
    
    /**
     * 고객 분석
     */
    public function customers(Request $request): View
    {
        $dateRange = $request->get('range', '30');
        $startDate = match($dateRange) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subYear(),
            default => now()->subDays(30),
        };
        
        // 고객 통계
        $customerStats = [
            'total_customers' => User::whereHas('orders')->count(),
            'new_customers' => User::where('created_at', '>=', $startDate)
                ->whereHas('orders')->count(),
            'repeat_customers' => User::whereHas('orders', '>=', 2)->count(),
            'average_order_per_customer' => User::whereHas('orders')
                ->withCount('orders')
                ->avg('orders_count'),
        ];
        
        // 상위 고객 (구매 금액 기준)
        $topCustomers = User::select('users.*')
            ->join('shop_orders', 'users.id', '=', 'shop_orders.user_id')
            ->where('shop_orders.payment_status', 'paid')
            ->where('shop_orders.created_at', '>=', $startDate)
            ->selectRaw('users.*, SUM(shop_orders.total_amount) as total_spent, COUNT(shop_orders.id) as order_count')
            ->groupBy('users.id')
            ->orderBy('total_spent', 'desc')
            ->limit(20)
            ->get();
        
        // 신규 고객 추이
        $newCustomersTrend = User::where('created_at', '>=', $startDate)
            ->whereHas('orders')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return view('ahhob.admin.dashboard.shop.customers', compact(
            'customerStats',
            'topCustomers',
            'newCustomersTrend',
            'dateRange'
        ));
    }
    
    /**
     * 상품 분석
     */
    public function products(Request $request): View
    {
        $dateRange = $request->get('range', '30');
        $startDate = match($dateRange) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subYear(),
            default => now()->subDays(30),
        };
        
        // 상품 통계
        $productStats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'out_of_stock' => Product::where('stock_status', 'out_of_stock')->count(),
            'low_stock' => Product::whereColumn('stock_quantity', '<=', 'min_stock_quantity')
                ->where('track_stock', true)->count(),
        ];
        
        // 베스트셀러 상품
        $bestSellers = DB::table('shop_order_items')
            ->join('shop_orders', 'shop_order_items.order_id', '=', 'shop_orders.id')
            ->join('shop_products', 'shop_order_items.product_id', '=', 'shop_products.id')
            ->where('shop_orders.payment_status', 'paid')
            ->where('shop_orders.created_at', '>=', $startDate)
            ->selectRaw('shop_products.*, SUM(shop_order_items.quantity) as total_sold, SUM(shop_order_items.total_price) as total_revenue')
            ->groupBy('shop_products.id')
            ->orderBy('total_sold', 'desc')
            ->limit(20)
            ->get();
        
        // 판매가 저조한 상품
        $slowMovers = Product::with('category')
            ->where('status', 'active')
            ->where('created_at', '<=', now()->subDays(30))
            ->where('sales_count', '<', 5)
            ->orderBy('sales_count', 'asc')
            ->limit(20)
            ->get();
        
        // 재고 부족 상품
        $stockAlerts = Product::with('category')
            ->where('track_stock', true)
            ->where(function ($query) {
                $query->where('stock_status', 'out_of_stock')
                      ->orWhereColumn('stock_quantity', '<=', 'min_stock_quantity');
            })
            ->orderBy('stock_quantity', 'asc')
            ->get();
        
        return view('ahhob.admin.dashboard.shop.products', compact(
            'productStats',
            'bestSellers',
            'slowMovers',
            'stockAlerts',
            'dateRange'
        ));
    }
    
    /**
     * 실시간 통계 (AJAX)
     */
    public function realTimeStats(): JsonResponse
    {
        $stats = [
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue_today' => Order::where('payment_status', 'paid')
                ->whereDate('created_at', today())->sum('total_amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'low_stock_alerts' => Product::whereColumn('stock_quantity', '<=', 'min_stock_quantity')
                ->where('track_stock', true)->count(),
            'new_customers_today' => User::whereDate('created_at', today())->count(),
        ];
        
        return response()->json($stats);
    }
    
    /**
     * 차트 데이터 (AJAX)
     */
    public function chartData(Request $request): JsonResponse
    {
        $type = $request->get('type', 'revenue');
        $period = $request->get('period', '7');
        
        $startDate = now()->subDays($period);
        
        switch ($type) {
            case 'revenue':
                $data = Order::where('payment_status', 'paid')
                    ->where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as date, SUM(total_amount) as value')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
                
            case 'orders':
                $data = Order::where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as value')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
                
            case 'customers':
                $data = User::where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as value')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
                
            default:
                $data = collect();
        }
        
        return response()->json($data);
    }
    
    /**
     * 전환율 계산
     */
    private function calculateConversionRate($startDate): float
    {
        $visitors = 1000; // 실제로는 방문자 추적 시스템에서 가져와야 함
        $orders = Order::where('created_at', '>=', $startDate)->count();
        
        return $visitors > 0 ? round(($orders / $visitors) * 100, 2) : 0;
    }
}