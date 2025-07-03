<?php

namespace App\Http\Controllers\Ahhob\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\Ahhob\Board\Board;

class AdminDashboardController extends BaseController
{

    /**
     * 사이트맵 관리 페이지
     */
    public function sitemap()
    {
        $sitemapExists = Storage::disk('public')->exists('sitemap.xml');
        $sitemapLastGenerated = null;
        
        if ($sitemapExists) {
            $sitemapLastGenerated = Storage::disk('public')->lastModified('sitemap.xml');
            $sitemapLastGenerated = date('Y-m-d H:i:s', $sitemapLastGenerated);
        }

        return $this->themeView('admin.dashboard.sitemap', compact('sitemapExists', 'sitemapLastGenerated'));
    }

    /**
     * 사이트맵 생성
     */
    public function generateSitemap()
    {
        try {
            $urls = collect();

            // 기본 페이지들
            $urls->push([
                'url' => route('home'),
                'lastmod' => now()->toISOString(),
                'changefreq' => 'daily',
                'priority' => '1.0'
            ]);

            $urls->push([
                'url' => route('boards.index'),
                'lastmod' => now()->toISOString(),
                'changefreq' => 'daily',
                'priority' => '0.9'
            ]);

            // 게시판 페이지들
            $boards = Board::where('is_active', true)->get();
            
            foreach ($boards as $board) {
                $urls->push([
                    'url' => route('boards.posts.index', $board->slug),
                    'lastmod' => $board->updated_at->toISOString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8'
                ]);

                // 게시글들 (최근 100개만)
                $postModelClass = 'App\\Models\\Ahhob\\Board\\Dynamic\\Board' . \Illuminate\Support\Str::studly($board->slug);
                
                if (class_exists($postModelClass)) {
                    $posts = $postModelClass::orderBy('created_at', 'desc')
                        ->limit(100)
                        ->get();
                    
                    foreach ($posts as $post) {
                        $urls->push([
                            'url' => route('boards.posts.show', [$board->slug, $post->id]),
                            'lastmod' => $post->updated_at->toISOString(),
                            'changefreq' => 'weekly',
                            'priority' => '0.6'
                        ]);
                    }
                }
            }

            // XML 생성
            $xml = $this->generateSitemapXml($urls);
            
            // 파일 저장
            Storage::disk('public')->put('sitemap.xml', $xml);
            
            // robots.txt도 생성
            $robotsTxt = $this->generateRobotsTxt();
            Storage::disk('public')->put('robots.txt', $robotsTxt);

            return redirect()->route('admin.sitemap')
                ->with('success', '사이트맵이 성공적으로 생성되었습니다. (' . $urls->count() . '개 URL)');

        } catch (\Exception $e) {
            return redirect()->route('admin.sitemap')
                ->with('error', '사이트맵 생성 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 사이트맵 XML 다운로드
     */
    public function downloadSitemap()
    {
        if (!Storage::disk('public')->exists('sitemap.xml')) {
            return redirect()->route('admin.sitemap')
                ->with('error', '사이트맵 파일이 존재하지 않습니다. 먼저 사이트맵을 생성해주세요.');
        }

        return Storage::disk('public')->download('sitemap.xml');
    }

    /**
     * 사이트맵 XML 내용 생성
     */
    private function generateSitemapXml($urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '    <url>' . "\n";
            $xml .= '        <loc>' . htmlspecialchars($url['url']) . '</loc>' . "\n";
            $xml .= '        <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            $xml .= '        <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '        <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '    </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * robots.txt 내용 생성
     */
    private function generateRobotsTxt(): string
    {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /profile/\n";
        $content .= "Disallow: /api/\n";
        $content .= "\n";
        $content .= "Sitemap: " . url('storage/sitemap.xml') . "\n";

        return $content;
    }

    /**
     * 사이트맵 삭제
     */
    public function deleteSitemap()
    {
        try {
            if (Storage::disk('public')->exists('sitemap.xml')) {
                Storage::disk('public')->delete('sitemap.xml');
            }
            if (Storage::disk('public')->exists('robots.txt')) {
                Storage::disk('public')->delete('robots.txt');
            }

            return redirect()->route('admin.sitemap')
                ->with('success', '사이트맵이 삭제되었습니다.');

        } catch (\Exception $e) {
            return redirect()->route('admin.sitemap')
                ->with('error', '사이트맵 삭제 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}
