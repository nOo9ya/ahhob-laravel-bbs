<?php

namespace App\Http\Requests\Ahhob\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'username.required' => '아이디를 입력해주세요.',
            'username.string' => '아이디는 문자열이어야 합니다.',
            'username.max' => '아이디는 50자 이하여야 합니다.',
            'password.required' => '비밀번호를 입력해주세요.',
            'password.string' => '비밀번호는 문자열이어야 합니다.',
            'remember.boolean' => '로그인 상태 유지는 true/false 값이어야 합니다.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'username' => '아이디',
            'password' => '비밀번호',
            'remember' => '로그인 상태 유지',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'remember' => $this->boolean('remember'),
        ]);
    }

    /**
     * Get the login credentials from the request.
     */
    public function getCredentials(): array
    {
        return $this->only('username', 'password');
    }

    /**
     * Check if remember me is requested.
     */
    public function shouldRemember(): bool
    {
        return $this->boolean('remember');
    }
}