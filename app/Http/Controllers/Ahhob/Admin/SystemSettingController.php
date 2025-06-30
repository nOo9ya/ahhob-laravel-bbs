<?php

namespace App\Http\Controllers\Ahhob\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\System\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SystemSettingController extends Controller
{
    /**
     * 시스템 설정 관리 페이지
     */
    public function index(Request $request)
    {
        $group = $request->get('group', 'attachment');
        
        $settings = SystemSetting::byGroup($group)
            ->active()
            ->ordered()
            ->get();
        
        $groups = SystemSetting::select('group')
            ->distinct()
            ->pluck('group')
            ->mapWithKeys(function ($group) {
                return [$group => ucfirst($group)];
            });
        
        return view('ahhob.admin.settings.index', [
            'settings' => $settings,
            'groups' => $groups,
            'currentGroup' => $group,
        ]);
    }

    /**
     * 첨부파일 설정 페이지
     */
    public function attachment()
    {
        $settings = SystemSetting::byGroup('attachment')
            ->active()
            ->ordered()
            ->get()
            ->keyBy('key');
        
        return view('ahhob.admin.settings.attachment', [
            'settings' => $settings,
        ]);
    }

    /**
     * 설정 저장
     */
    public function store(Request $request)
    {
        $settings = $request->get('settings', []);
        $errors = [];
        
        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();
            
            if (!$setting) {
                continue;
            }
            
            // 유효성 검사
            if ($setting->validation_rules) {
                try {
                    $validator = Validator::make(
                        [$key => $value],
                        [$key => $setting->validation_rules]
                    );
                    
                    if ($validator->fails()) {
                        $errors[$key] = $validator->errors()->first($key);
                        continue;
                    }
                } catch (\Exception $e) {
                    $errors[$key] = '유효성 검사 오류: ' . $e->getMessage();
                    continue;
                }
            }
            
            // 값 저장
            SystemSetting::set($key, $value);
        }
        
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }
        
        return back()->with('success', '설정이 성공적으로 저장되었습니다.');
    }

    /**
     * 설정 초기화
     */
    public function reset(Request $request)
    {
        $group = $request->get('group', 'attachment');
        
        // 해당 그룹의 설정을 기본값으로 초기화
        $defaultSettings = $this->getDefaultSettings($group);
        
        foreach ($defaultSettings as $key => $value) {
            SystemSetting::set($key, $value);
        }
        
        return back()->with('success', "{$group} 설정이 기본값으로 초기화되었습니다.");
    }

    /**
     * 캐시 클리어
     */
    public function clearCache()
    {
        // 시스템 설정 캐시 클리어
        $keys = SystemSetting::pluck('key');
        foreach ($keys as $key) {
            \Cache::forget("system_setting:{$key}");
        }
        
        return back()->with('success', '설정 캐시가 클리어되었습니다.');
    }

    /**
     * 설정 내보내기
     */
    public function export(Request $request)
    {
        $group = $request->get('group');
        
        $query = SystemSetting::active();
        if ($group) {
            $query->byGroup($group);
        }
        
        $settings = $query->get();
        
        $exportData = [];
        foreach ($settings as $setting) {
            $exportData[$setting->key] = [
                'value' => $setting->value,
                'type' => $setting->type,
                'group' => $setting->group,
                'label' => $setting->label,
                'description' => $setting->description,
            ];
        }
        
        $filename = $group ? "settings_{$group}.json" : "settings_all.json";
        
        return response()->json($exportData)
            ->header('Content-Disposition', "attachment; filename={$filename}")
            ->header('Content-Type', 'application/json');
    }

    /**
     * 설정 가져오기
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);
        
        try {
            $content = file_get_contents($request->file('file')->getRealPath());
            $settings = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('유효하지 않은 JSON 파일입니다.');
            }
            
            $imported = 0;
            foreach ($settings as $key => $data) {
                if (SystemSetting::where('key', $key)->exists()) {
                    SystemSetting::set($key, $data['value']);
                    $imported++;
                }
            }
            
            return back()->with('success', "{$imported}개의 설정이 가져와졌습니다.");
            
        } catch (\Exception $e) {
            return back()->withErrors(['file' => '파일 가져오기 실패: ' . $e->getMessage()]);
        }
    }

    /**
     * 기본 설정값 반환
     */
    private function getDefaultSettings(string $group): array
    {
        $defaults = [
            'attachment' => [
                'attachment.default_max_file_size' => 5120,
                'attachment.webp.mode' => 'optional',
                'attachment.webp.quality' => 85,
                'attachment.webp.min_size_for_conversion' => 51200,
                'attachment.webp.convertible_types' => ['image/jpeg', 'image/png'],
                'attachment.webp.keep_original' => false,
                'attachment.thumbnail.width' => 300,
                'attachment.thumbnail.height' => 200,
                'attachment.thumbnail.quality' => 85,
            ],
        ];
        
        return $defaults[$group] ?? [];
    }
}
