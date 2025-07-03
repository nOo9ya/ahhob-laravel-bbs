<?php

namespace App\Http\Controllers;

use App\Services\ThemeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;
    
    protected ThemeService $themeService;
    
    public function __construct()
    {
        $this->themeService = app(ThemeService::class);
    }
    
    /**
     * 테마를 적용한 뷰 반환
     */
    protected function themeView(string $view, array $data = [], array $mergeData = [])
    {
        $themeView = $this->themeService->getThemeViewPath($view);
        return view($themeView, $data, $mergeData);
    }
    
    /**
     * 현재 테마 반환
     */
    protected function getCurrentTheme(): string
    {
        return $this->themeService->getCurrentTheme();
    }
}