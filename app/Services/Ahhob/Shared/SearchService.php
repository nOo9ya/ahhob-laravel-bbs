<?php

namespace App\Services\Ahhob\Shared;

use App\Models\Ahhob\Shared\SearchLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 검색 시스템 관리 서비스
 * 
 * 이 서비스는 게시글 및 콘텐츠 검색 기능을 담당합니다.
 * 
 * 주요 기능:
 * - 키워드 기반 게시글 검색
 * - 필터링 및 정렬
 * - 검색 로그 관리
 * - 인기 검색어 분석
 * - 자동완성 기능
 * - 검색 결과 하이라이팅
 */
class SearchService
{
    /**
     * 기본 검색 설정
     */
    private array $defaultConfig = [
        'per_page' => 15,
        'max_suggestions' => 10,
        'highlight_tag' => 'mark',
        'min_keyword_length' => 2,
        'max_keyword_length' => 100,
    ];

    /**
     * 기본 게시글 검색
     * 
     * @param string $keyword 검색 키워드
     * @param array|null $fields 검색할 필드 (기본: title, content)
     * @param string $sort 정렬 방식 (latest, oldest, relevance)
     * @param int|null $perPage 페이지당 항목 수
     * @return LengthAwarePaginator
     */
    public function searchPosts(
        string $keyword, 
        ?array $fields = null, 
        string $sort = 'relevance',
        ?int $perPage = null
    ): LengthAwarePaginator {
        $startTime = microtime(true);
        
        if (!$this->isValidKeyword($keyword)) {
            return new LengthAwarePaginator([], 0, $perPage ?? $this->defaultConfig['per_page']);
        }

        $fields = $fields ?? ['title', 'content'];
        $perPage = $perPage ?? $this->defaultConfig['per_page'];

        // 동적 게시판 모델 사용 (BoardNotice)
        $modelClass = \App\Models\Ahhob\Board\Dynamic\BoardNotice::class;
        $query = $modelClass::query()->published(); // 공개된 게시글만
        
        // 키워드를 공백으로 분리해서 AND 검색
        $keywords = array_filter(explode(' ', $keyword));
        
        if (count($keywords) > 1) {
            // 다중 키워드 AND 검색
            foreach ($keywords as $singleKeyword) {
                $query->where(function ($q) use ($singleKeyword, $fields) {
                    foreach ($fields as $field) {
                        $q->orWhere($field, 'like', "%{$singleKeyword}%");
                    }
                });
            }
        } else {
            // 단일 키워드 검색
            $query->where(function ($q) use ($keyword, $fields) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%{$keyword}%");
                }
            });
        }

        // 정렬 적용
        $query = $this->applySorting($query, $sort);

        $results = $query->paginate($perPage);
        
        $executionTime = microtime(true) - $startTime;
        
        // 검색 로그 기록
        $this->logSearch($keyword, $results->total(), null, $executionTime);

        return $results;
    }

    /**
     * 특정 사용자의 게시글 검색
     * 
     * @param string $keyword 검색 키워드
     * @param User $user 작성자
     * @return LengthAwarePaginator
     */
    public function searchPostsByUser(string $keyword, User $user): LengthAwarePaginator
    {
        if (!$this->isValidKeyword($keyword)) {
            return new LengthAwarePaginator([], 0, $this->defaultConfig['per_page']);
        }

        $modelClass = \App\Models\Ahhob\Board\Dynamic\BoardNotice::class;
        $query = $modelClass::query()
            ->published()
            ->where('user_id', $user->id)
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });

        return $query->latest()->paginate($this->defaultConfig['per_page']);
    }

    /**
     * 날짜 범위로 게시글 검색
     * 
     * @param string $keyword 검색 키워드
     * @param \Carbon\Carbon $dateFrom 시작일
     * @param \Carbon\Carbon $dateTo 종료일
     * @return LengthAwarePaginator
     */
    public function searchPostsByDateRange(string $keyword, $dateFrom, $dateTo): LengthAwarePaginator
    {
        if (!$this->isValidKeyword($keyword)) {
            return new LengthAwarePaginator([], 0, $this->defaultConfig['per_page']);
        }

        $modelClass = \App\Models\Ahhob\Board\Dynamic\BoardNotice::class;
        $query = $modelClass::query()
            ->published()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });

        return $query->latest()->paginate($this->defaultConfig['per_page']);
    }

    /**
     * 로그인 사용자로 검색 (검색 로그 기록 포함)
     * 
     * @param string $keyword 검색 키워드
     * @param User $user 사용자
     * @return LengthAwarePaginator
     */
    public function searchPostsAsUser(string $keyword, User $user): LengthAwarePaginator
    {
        $startTime = microtime(true);
        
        if (!$this->isValidKeyword($keyword)) {
            return new LengthAwarePaginator([], 0, $this->defaultConfig['per_page']);
        }

        $modelClass = \App\Models\Ahhob\Board\Dynamic\BoardNotice::class;
        $query = $modelClass::query()
            ->published()
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });

        $results = $query->latest()->paginate($this->defaultConfig['per_page']);
        
        $executionTime = microtime(true) - $startTime;
        
        // 로그인 사용자 검색 로그 기록
        $this->logSearch($keyword, $results->total(), $user, $executionTime);

        return $results;
    }

    /**
     * 인기 검색어 조회
     * 
     * @param int $limit 조회할 키워드 수
     * @param int $days 기간 (일)
     * @return Collection
     */
    public function getPopularKeywords(int $limit = 10, int $days = 30): Collection
    {
        return SearchLog::getPopularKeywords($limit, $days);
    }

    /**
     * 자동완성 키워드 제안
     * 
     * @param string $partial 부분 키워드
     * @return array
     */
    public function getSuggestions(string $partial): array
    {
        if (mb_strlen(trim($partial)) < 2) {
            return [];
        }

        // 게시글 제목에서 키워드 추출
        $modelClass = \App\Models\Ahhob\Board\Dynamic\BoardNotice::class;
        $titles = $modelClass::published()
            ->where('title', 'like', "%{$partial}%")
            ->pluck('title')
            ->take($this->defaultConfig['max_suggestions']);

        // 키워드 추출 (간단한 구현)
        $keywords = [];
        foreach ($titles as $title) {
            $words = explode(' ', $title);
            foreach ($words as $word) {
                if (mb_stripos($word, $partial) !== false && mb_strlen($word) >= 2) {
                    $keywords[] = $word;
                }
            }
        }

        return array_unique(array_slice($keywords, 0, $this->defaultConfig['max_suggestions']));
    }

    /**
     * 검색 결과 하이라이팅과 함께 검색
     * 
     * @param string $keyword 검색 키워드
     * @return LengthAwarePaginator
     */
    public function searchPostsWithHighlight(string $keyword): LengthAwarePaginator
    {
        $results = $this->searchPosts($keyword);
        
        // 각 결과에 하이라이팅 적용
        $results->getCollection()->transform(function ($post) use ($keyword) {
            $post->highlighted_title = $this->highlightKeyword($post->title, $keyword);
            $post->highlighted_content = $this->highlightKeyword($post->content, $keyword);
            return $post;
        });

        return $results;
    }

    /**
     * 다중 키워드 OR 검색
     * 
     * @param string $keywords 키워드 (|로 구분)
     * @param string $mode 검색 모드 (and, or)
     * @return LengthAwarePaginator
     */
    public function searchPostsWithMode(string $keywords, string $mode = 'and'): LengthAwarePaginator
    {
        $keywordList = explode('|', $keywords);
        
        if (empty($keywordList)) {
            return new LengthAwarePaginator([], 0, $this->defaultConfig['per_page']);
        }

        $modelClass = \App\Models\Ahhob\Board\Dynamic\BoardNotice::class;
        $query = $modelClass::query()->published();

        if ($mode === 'or') {
            $query->where(function ($q) use ($keywordList) {
                foreach ($keywordList as $keyword) {
                    $keyword = trim($keyword);
                    if ($this->isValidKeyword($keyword)) {
                        $q->orWhere('title', 'like', "%{$keyword}%")
                          ->orWhere('content', 'like', "%{$keyword}%");
                    }
                }
            });
        } else { // and mode
            foreach ($keywordList as $keyword) {
                $keyword = trim($keyword);
                if ($this->isValidKeyword($keyword)) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('title', 'like', "%{$keyword}%")
                          ->orWhere('content', 'like', "%{$keyword}%");
                    });
                }
            }
        }

        return $query->latest()->paginate($this->defaultConfig['per_page']);
    }

    /**
     * 필터를 적용한 검색
     * 
     * @param string $keyword 검색 키워드
     * @param array $filters 필터 옵션
     * @return LengthAwarePaginator
     */
    public function searchPostsWithFilters(string $keyword, array $filters): LengthAwarePaginator
    {
        if (!$this->isValidKeyword($keyword)) {
            return new LengthAwarePaginator([], 0, $this->defaultConfig['per_page']);
        }

        $modelClass = \App\Models\Ahhob\Board\Dynamic\BoardNotice::class;
        $query = $modelClass::query()
            ->published()
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });

        // 날짜 필터
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
        }

        // 작성자 필터 (nickname 기준)
        if (isset($filters['author'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('nickname', 'like', "%{$filters['author']}%");
            });
        }

        return $query->latest()->paginate($this->defaultConfig['per_page']);
    }

    /**
     * 검색 로그 기록
     * 
     * @param string $keyword 검색 키워드
     * @param int $resultsCount 결과 수
     * @param User|null $user 사용자
     * @param float|null $executionTime 실행 시간
     */
    private function logSearch(string $keyword, int $resultsCount, ?User $user = null, ?float $executionTime = null): void
    {
        SearchLog::create([
            'keyword' => $keyword,
            'results_count' => $resultsCount,
            'user_id' => $user?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'execution_time' => $executionTime,
        ]);
    }

    /**
     * 정렬 적용
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sort
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applySorting($query, string $sort)
    {
        return match($sort) {
            'latest' => $query->latest('created_at'),
            'oldest' => $query->oldest('created_at'),
            'relevance' => $query->latest('created_at'), // 기본값으로 최신순
            default => $query->latest('created_at'),
        };
    }

    /**
     * 키워드 검증
     * 
     * @param string $keyword 검색 키워드
     * @return bool
     */
    private function isValidKeyword(string $keyword): bool
    {
        $length = mb_strlen(trim($keyword));
        return $length >= $this->defaultConfig['min_keyword_length'] 
            && $length <= $this->defaultConfig['max_keyword_length'];
    }

    /**
     * 검색 결과 하이라이팅
     * 
     * @param string $text 원본 텍스트
     * @param string $keyword 검색 키워드
     * @return string
     */
    private function highlightKeyword(string $text, string $keyword): string
    {
        $tag = $this->defaultConfig['highlight_tag'];
        return preg_replace(
            '/(' . preg_quote($keyword, '/') . ')/iu',
            "<{$tag}>$1</{$tag}>",
            $text
        );
    }
}