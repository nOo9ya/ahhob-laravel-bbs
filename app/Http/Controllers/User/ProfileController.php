<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends BaseController
{
    /**
     * 프로필 조회
     */
    public function show(): View
    {
        $user = auth()->user();
        $stats = $this->getUserStats($user);
        $recentPosts = $this->getUserRecentPosts($user, 5);
        
        return $this->themeView('user.profile.show', compact('user', 'stats', 'recentPosts'));
    }

    /**
     * 프로필 수정 폼
     */
    public function edit(): View
    {
        $user = auth()->user();
        return $this->themeView('user.profile.edit', compact('user'));
    }

    /**
     * 프로필 업데이트
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nickname' => 'required|string|max:100|unique:users,nickname,' . $user->id,
            'bio' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'location' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:M,F,O',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // 아바타 업로드 처리
        if ($request->hasFile('avatar')) {
            // 기존 아바타 삭제
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }
            
            // 새 아바타 저장
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['profile_image_path'] = $path;
        }

        $user->update($validated);

        return redirect()->route('profile.show')
            ->with('success', '프로필이 성공적으로 업데이트되었습니다.');
    }

    /**
     * 비밀번호 변경 폼
     */
    public function editPassword(): View
    {
        return $this->themeView('user.profile.password');
    }

    /**
     * 비밀번호 업데이트
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.show')
            ->with('success', '비밀번호가 성공적으로 변경되었습니다.');
    }

    /**
     * 아바타 삭제
     */
    public function deleteAvatar(): RedirectResponse
    {
        $user = auth()->user();
        
        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
            $user->update(['profile_image_path' => null]);
        }

        return redirect()->route('profile.edit')
            ->with('success', '아바타가 삭제되었습니다.');
    }

    /**
     * 사용자 통계 정보 조회
     */
    private function getUserStats(User $user): array
    {
        // 기본 통계
        $stats = [
            'total_posts' => 0,
            'total_comments' => 0,
            'total_views' => 0,
            'join_days' => $user->created_at->diffInDays(now()),
        ];

        // 동적 게시판들의 게시글 수 계산
        try {
            $boards = \App\Models\Ahhob\Board\Board::all();
            
            foreach ($boards as $board) {
                $postModelClass = 'App\\Models\\Ahhob\\Board\\Dynamic\\Board' . \Illuminate\Support\Str::studly($board->slug);
                $commentModelClass = $postModelClass . 'Comment';
                
                if (class_exists($postModelClass)) {
                    $stats['total_posts'] += $postModelClass::where('user_id', $user->id)->count();
                }
                
                if (class_exists($commentModelClass)) {
                    $stats['total_comments'] += $commentModelClass::where('user_id', $user->id)->count();
                }
            }
        } catch (\Exception $e) {
            // 오류 발생 시 기본값 유지
        }

        return $stats;
    }

    /**
     * 사용자 최근 게시글 조회
     */
    private function getUserRecentPosts(User $user, int $limit = 5): array
    {
        $recentPosts = [];
        
        try {
            $boards = \App\Models\Ahhob\Board\Board::all();
            
            foreach ($boards as $board) {
                $postModelClass = 'App\\Models\\Ahhob\\Board\\Dynamic\\Board' . \Illuminate\Support\Str::studly($board->slug);
                
                if (class_exists($postModelClass)) {
                    $posts = $postModelClass::where('user_id', $user->id)
                        ->latest()
                        ->limit($limit)
                        ->get()
                        ->map(function ($post) use ($board) {
                            return [
                                'id' => $post->id,
                                'title' => $post->title,
                                'created_at' => $post->created_at,
                                'board_name' => $board->name,
                                'board_slug' => $board->slug,
                            ];
                        });
                    
                    $recentPosts = array_merge($recentPosts, $posts->toArray());
                }
            }
            
            // 날짜순으로 정렬하고 제한
            usort($recentPosts, function ($a, $b) {
                return $b['created_at'] <=> $a['created_at'];
            });
            
            $recentPosts = array_slice($recentPosts, 0, $limit);
            
        } catch (\Exception $e) {
            // 오류 발생 시 빈 배열 반환
        }

        return $recentPosts;
    }
}
