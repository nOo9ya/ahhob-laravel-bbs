<?php

namespace App\Http\Controllers\Ahhob\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * 사용자 목록 조회
     */
    public function index(Request $request): View
    {
        try {
            $query = User::query();
            
            // 검색 기능
            if ($search = $request->get('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('nickname', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            }
            
            // 상태 필터
            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }
            
            // 정렬
            $sortBy = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);
            
            $users = $query->paginate(20)->withQueryString();
            
            return view('ahhob.admin.users.index', compact('users'));
            
        } catch (\Exception $e) {
            return response('Error: ' . $e->getMessage() . ' File: ' . $e->getFile() . ':' . $e->getLine(), 500)
                ->header('Content-Type', 'text/plain');
        }
    }
    
    /**
     * 사용자 상세 조회
     */
    public function show(User $user): View
    {
        $user->load(['posts', 'comments']);
        
        return view('ahhob.admin.users.show', compact('user'));
    }
    
    /**
     * 사용자 상태 변경
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:active,dormant,suspended,banned'
        ]);
        
        $user->update([
            'status' => $request->status
        ]);
        
        return back()->with('success', '사용자 상태가 변경되었습니다.');
    }
    
    /**
     * 사용자 삭제 (소프트 삭제)
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();
        
        return redirect()->route('admin.users.index')
                        ->with('success', '사용자가 삭제되었습니다.');
    }
}