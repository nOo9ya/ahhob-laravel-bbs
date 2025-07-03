<?php

namespace App\Http\Requests\Ahhob\Auth;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Enum;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required', 
                'string', 
                'min:3', 
                'max:50', 
                'unique:users,username',
                'regex:/^[a-zA-Z0-9_]+$/', // 영문, 숫자, 언더스코어만 허용
            ],
            'nickname' => [
                'required', 
                'string', 
                'min:2', 
                'max:100', 
                'unique:users,nickname',
            ],
            'real_name' => [
                'required', 
                'string', 
                'min:2', 
                'max:100',
            ],
            'email' => [
                'required', 
                'string', 
                'email', 
                'max:100', 
                'unique:users,email',
            ],
            'password' => [
                'required', 
                'string', 
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'phone_number' => [
                'nullable', 
                'string', 
                'max:20', 
                'unique:users,phone_number',
                'regex:/^[0-9\-\+\(\)\s]+$/', // 숫자, 하이픈, 플러스, 괄호, 공백만 허용
            ],
            'terms_agree' => ['required', 'accepted'],
            'privacy_agree' => ['required', 'accepted'],
            'marketing_agree' => ['boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'username.required' => '아이디를 입력해주세요.',
            'username.min' => '아이디는 3자 이상이어야 합니다.',
            'username.max' => '아이디는 50자 이하여야 합니다.',
            'username.unique' => '이미 사용 중인 아이디입니다.',
            'username.regex' => '아이디는 영문, 숫자, 언더스코어(_)만 사용 가능합니다.',
            
            'nickname.required' => '닉네임을 입력해주세요.',
            'nickname.min' => '닉네임은 2자 이상이어야 합니다.',
            'nickname.max' => '닉네임은 100자 이하여야 합니다.',
            'nickname.unique' => '이미 사용 중인 닉네임입니다.',
            
            'real_name.required' => '실명을 입력해주세요.',
            'real_name.min' => '실명은 2자 이상이어야 합니다.',
            'real_name.max' => '실명은 100자 이하여야 합니다.',
            
            'email.required' => '이메일을 입력해주세요.',
            'email.email' => '올바른 이메일 형식이 아닙니다.',
            'email.unique' => '이미 사용 중인 이메일입니다.',
            
            'password.required' => '비밀번호를 입력해주세요.',
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
            
            'phone_number.unique' => '이미 사용 중인 휴대폰 번호입니다.',
            'phone_number.regex' => '올바른 휴대폰 번호 형식이 아닙니다.',
            
            'terms_agree.required' => '이용약관에 동의해주세요.',
            'terms_agree.accepted' => '이용약관에 동의해주세요.',
            'privacy_agree.required' => '개인정보 처리방침에 동의해주세요.',
            'privacy_agree.accepted' => '개인정보 처리방침에 동의해주세요.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'username' => '아이디',
            'nickname' => '닉네임',
            'real_name' => '실명',
            'email' => '이메일',
            'password' => '비밀번호',
            'phone_number' => '휴대폰 번호',
            'terms_agree' => '이용약관 동의',
            'privacy_agree' => '개인정보 처리방침 동의',
            'marketing_agree' => '마케팅 정보 수신 동의',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'terms_agree' => $this->boolean('terms_agree'),
            'privacy_agree' => $this->boolean('privacy_agree'),
            'marketing_agree' => $this->boolean('marketing_agree'),
        ]);
    }

    /**
     * Get the validated data for user creation.
     */
    public function getUserData(): array
    {
        $validated = $this->validated();
        
        return [
            'username' => $validated['username'],
            'nickname' => $validated['nickname'],
            'name' => $validated['real_name'], // real_name을 name으로 매핑
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone_number' => $validated['phone_number'] ?? null,
            'status' => UserStatus::ACTIVE,
            'points' => 100, // 가입 축하 포인트
            'level' => 1,
        ];
    }

    /**
     * Check if marketing consent is given.
     */
    public function hasMarketingConsent(): bool
    {
        return $this->boolean('marketing_agree');
    }
}