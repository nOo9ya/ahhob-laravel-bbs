<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;

class ThemeService
{
    private string $defaultTheme = 'default';
    private string $themesPath = 'themes';
    
    /**
     * 현재 활성 테마 반환
     */
    public function getCurrentTheme(): string
    {
        // 1. 세션에서 테마 확인
        if (Session::has('theme')) {
            $theme = Session::get('theme');
            if ($this->themeExists($theme)) {
                return $theme;
            }
        }
        
        // 2. 사용자 설정에서 테마 확인 (로그인한 경우)
        if (auth()->check() && auth()->user()->theme) {
            $theme = auth()->user()->theme;
            if ($this->themeExists($theme)) {
                Session::put('theme', $theme);
                return $theme;
            }
        }
        
        // 3. 기본 테마 반환
        return $this->defaultTheme;
    }
    
    /**
     * 테마 설정
     */
    public function setTheme(string $theme): bool
    {
        if (!$this->themeExists($theme)) {
            return false;
        }
        
        // 세션에 저장
        Session::put('theme', $theme);
        
        // 로그인한 사용자의 경우 DB에도 저장
        if (auth()->check()) {
            auth()->user()->update(['theme' => $theme]);
        }
        
        return true;
    }
    
    /**
     * 테마 존재 여부 확인
     */
    public function themeExists(string $theme): bool
    {
        $themePath = resource_path("views/{$this->themesPath}/{$theme}");
        return File::isDirectory($themePath);
    }
    
    /**
     * 사용 가능한 테마 목록 반환
     */
    public function getAvailableThemes(): array
    {
        $themesPath = resource_path("views/{$this->themesPath}");
        
        if (!File::isDirectory($themesPath)) {
            return [$this->defaultTheme];
        }
        
        $themes = [];
        $directories = File::directories($themesPath);
        
        foreach ($directories as $directory) {
            $themeName = basename($directory);
            if ($this->isValidTheme($themeName)) {
                $themes[] = $themeName;
            }
        }
        
        return empty($themes) ? [$this->defaultTheme] : $themes;
    }
    
    /**
     * 테마 뷰 경로 반환
     */
    public function getThemeViewPath(string $view, ?string $theme = null): string
    {
        $theme = $theme ?? $this->getCurrentTheme();
        $themeView = "{$this->themesPath}.{$theme}.{$view}";
        
        // 테마 뷰가 존재하는지 확인
        if (View::exists($themeView)) {
            return $themeView;
        }
        
        // 기본 테마로 폴백
        if ($theme !== $this->defaultTheme) {
            $defaultView = "{$this->themesPath}.{$this->defaultTheme}.{$view}";
            if (View::exists($defaultView)) {
                return $defaultView;
            }
        }
        
        // 기존 ahhob 경로로 폴백 (마이그레이션 기간)
        $legacyView = "ahhob.{$view}";
        if (View::exists($legacyView)) {
            return $legacyView;
        }
        
        // 최종적으로 테마 뷰 경로 반환 (에러 발생 시 Laravel이 처리)
        return $themeView;
    }
    
    /**
     * 유효한 테마인지 확인
     */
    private function isValidTheme(string $theme): bool
    {
        $themePath = resource_path("views/{$this->themesPath}/{$theme}");
        
        // 필수 폴더들이 존재하는지 확인
        $requiredFolders = ['layouts'];
        foreach ($requiredFolders as $folder) {
            if (!File::isDirectory("{$themePath}/{$folder}")) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 테마 정보 반환
     */
    public function getThemeInfo(string $theme): array
    {
        $themeInfoPath = resource_path("views/{$this->themesPath}/{$theme}/theme.json");
        
        if (File::exists($themeInfoPath)) {
            $info = json_decode(File::get($themeInfoPath), true);
            return $info ?? [];
        }
        
        // 기본 정보 반환
        return [
            'name' => ucfirst($theme),
            'description' => ucfirst($theme) . ' Theme',
            'version' => '1.0.0',
            'author' => 'Ahhob'
        ];
    }
    
    /**
     * 테마별 CSS 파일 경로 반환
     */
    public function getThemeCssPath(string $theme): ?string
    {
        $cssPath = "themes/{$theme}/css/theme.css";
        $fullPath = public_path($cssPath);
        
        if (File::exists($fullPath)) {
            return asset($cssPath);
        }
        
        return null;
    }
}