<?php

namespace App\Enums;

enum ActivityType: string
{
    // 인증 관련
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case REGISTER = 'register';
    case PASSWORD_RESET = 'password_reset';
    case EMAIL_VERIFY = 'email_verify';
    
    // 게시판 관련 (추후 확장)
    case POST_CREATE = 'post_create';
    case POST_UPDATE = 'post_update';
    case POST_DELETE = 'post_delete';
    case COMMENT_CREATE = 'comment_create';
    case COMMENT_UPDATE = 'comment_update';
    case COMMENT_DELETE = 'comment_delete';
    
    // 쇼핑몰 관련 (추후 확장)
    case ORDER_CREATE = 'order_create';
    case ORDER_CANCEL = 'order_cancel';
    case PAYMENT_SUCCESS = 'payment_success';
    case PAYMENT_FAILED = 'payment_failed';
    
    // 관리자 관련
    case ADMIN_LOGIN = 'admin_login';
    case ADMIN_ACTION = 'admin_action';

    /**
     * 활동 타입 한글 이름 반환
     */
    public function label(): string
    {
        return match($this) {
            // 인증 관련
            self::LOGIN => '로그인',
            self::LOGOUT => '로그아웃',
            self::REGISTER => '회원가입',
            self::PASSWORD_RESET => '비밀번호 재설정',
            self::EMAIL_VERIFY => '이메일 인증',
            
            // 게시판 관련
            self::POST_CREATE => '게시글 작성',
            self::POST_UPDATE => '게시글 수정',
            self::POST_DELETE => '게시글 삭제',
            self::COMMENT_CREATE => '댓글 작성',
            self::COMMENT_UPDATE => '댓글 수정',
            self::COMMENT_DELETE => '댓글 삭제',
            
            // 쇼핑몰 관련
            self::ORDER_CREATE => '주문 생성',
            self::ORDER_CANCEL => '주문 취소',
            self::PAYMENT_SUCCESS => '결제 성공',
            self::PAYMENT_FAILED => '결제 실패',
            
            // 관리자 관련
            self::ADMIN_LOGIN => '관리자 로그인',
            self::ADMIN_ACTION => '관리자 작업',
        };
    }

    /**
     * 활동 타입별 카테고리 반환
     */
    public function category(): string
    {
        return match($this) {
            self::LOGIN, self::LOGOUT, self::REGISTER, 
            self::PASSWORD_RESET, self::EMAIL_VERIFY => 'auth',
            
            self::POST_CREATE, self::POST_UPDATE, self::POST_DELETE,
            self::COMMENT_CREATE, self::COMMENT_UPDATE, self::COMMENT_DELETE => 'community',
            
            self::ORDER_CREATE, self::ORDER_CANCEL,
            self::PAYMENT_SUCCESS, self::PAYMENT_FAILED => 'shop',
            
            self::ADMIN_LOGIN, self::ADMIN_ACTION => 'admin',
        };
    }

    /**
     * 중요한 활동인지 확인
     */
    public function isImportant(): bool
    {
        return in_array($this, [
            self::REGISTER,
            self::PASSWORD_RESET,
            self::PAYMENT_SUCCESS,
            self::PAYMENT_FAILED,
            self::ADMIN_LOGIN,
            self::ADMIN_ACTION,
        ]);
    }

    /**
     * 카테고리별 활동 타입 목록 반환
     */
    public static function getByCategory(string $category): array
    {
        return array_filter(self::cases(), function($type) use ($category) {
            return $type->category() === $category;
        });
    }
}