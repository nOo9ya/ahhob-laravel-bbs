<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends BaseController
{
    /**
     * 홈페이지 표시
     */
    public function index(): View
    {
        return $this->themeView('home.index');
    }
    
    /**
     * 테마 변경
     */
    public function changeTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|string'
        ]);
        
        $theme = $request->input('theme');
        
        if ($this->themeService->setTheme($theme)) {
            return response()->json([
                'success' => true,
                'message' => '테마가 변경되었습니다.',
                'theme' => $theme
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => '유효하지 않은 테마입니다.'
        ], 400);
    }
}