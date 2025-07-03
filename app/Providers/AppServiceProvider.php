<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 테마 서비스 등록
        $this->app->singleton(\App\Services\ThemeService::class);
        
        // Shop 서비스 등록
        $this->app->singleton(\App\Services\Shop\CartService::class);
        $this->app->singleton(\App\Services\Shop\OrderService::class);
        $this->app->singleton(\App\Services\Shop\ProductService::class);
        $this->app->singleton(\App\Services\Shop\CouponService::class);
        $this->app->singleton(\App\Services\Shop\InventoryService::class);
        $this->app->singleton(\App\Services\Shop\WishlistService::class);
        $this->app->singleton(\App\Services\Shop\ReviewService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Blade 컴포넌트 등록
        \Illuminate\Support\Facades\Blade::componentNamespace('App\\View\\Components\\Ahhob', 'ahhob');
        
        // 테마 서비스를 뷰에서 사용할 수 있도록 공유
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $themeService = app(\App\Services\ThemeService::class);
            $view->with('currentTheme', $themeService->getCurrentTheme());
            $view->with('themeService', $themeService);
        });
    }
}
