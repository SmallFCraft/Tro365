<?php

namespace Tro365\Helpers;

/**
 * Status Helper Class
 * Tro365 - Website thuê trọ
 * 
 * Centralizes all status constants and provides unified status management
 */
class StatusHelper
{
    // User Status
    const USER_ACTIVE = 1;
    const USER_INACTIVE = 0;
    const USER_BANNED = 2;
    
    // Post Status
    const POST_PENDING = 0;
    const POST_APPROVED = 1;
    const POST_REJECTED = 2;
    const POST_RENTED = 3;
    const POST_HIDDEN = 4;
    
    // Contact Status
    const CONTACT_PENDING = 0;
    const CONTACT_VIEWED = 1;
    const CONTACT_CONTACTED = 2;
    const CONTACT_INTERESTED = 3;
    const CONTACT_DEAL = 4;
    const CONTACT_CANCELLED = 5;
    
    // Transaction Status
    const TRANSACTION_PENDING = 0;
    const TRANSACTION_CONFIRMED = 1;
    const TRANSACTION_PAID = 2;
    const TRANSACTION_COMPLETED = 3;
    const TRANSACTION_CANCELLED = 4;
    
    // Seller Status
    const SELLER_PENDING = 0;
    const SELLER_APPROVED = 1;
    const SELLER_REJECTED = 2;
    
    /**
     * Get user status options
     */
    public static function getUserStatuses()
    {
        return [
            self::USER_ACTIVE => 'Hoạt động',
            self::USER_INACTIVE => 'Không hoạt động',
            self::USER_BANNED => 'Bị cấm'
        ];
    }
    
    /**
     * Get post status options
     */
    public static function getPostStatuses()
    {
        return [
            self::POST_PENDING => 'Chờ duyệt',
            self::POST_APPROVED => 'Đã duyệt',
            self::POST_REJECTED => 'Từ chối',
            self::POST_RENTED => 'Đã thuê',
            self::POST_HIDDEN => 'Ẩn'
        ];
    }
    
    /**
     * Get contact status options
     */
    public static function getContactStatuses()
    {
        return [
            self::CONTACT_PENDING => 'Chờ xử lý',
            self::CONTACT_VIEWED => 'Đã xem',
            self::CONTACT_CONTACTED => 'Đã liên hệ',
            self::CONTACT_INTERESTED => 'Quan tâm',
            self::CONTACT_DEAL => 'Thành công',
            self::CONTACT_CANCELLED => 'Hủy bỏ'
        ];
    }
    
    /**
     * Get transaction status options
     */
    public static function getTransactionStatuses()
    {
        return [
            self::TRANSACTION_PENDING => 'Chờ xác nhận',
            self::TRANSACTION_CONFIRMED => 'Đã xác nhận',
            self::TRANSACTION_PAID => 'Đã thanh toán',
            self::TRANSACTION_COMPLETED => 'Hoàn thành',
            self::TRANSACTION_CANCELLED => 'Hủy bỏ'
        ];
    }
    
    /**
     * Get seller status options
     */
    public static function getSellerStatuses()
    {
        return [
            self::SELLER_PENDING => 'Chờ duyệt',
            self::SELLER_APPROVED => 'Đã duyệt',
            self::SELLER_REJECTED => 'Từ chối'
        ];
    }
    
    /**
     * Get status name by type and value
     */
    public static function getStatusName($type, $status)
    {
        $statuses = match($type) {
            'user' => self::getUserStatuses(),
            'post' => self::getPostStatuses(),
            'contact' => self::getContactStatuses(),
            'transaction' => self::getTransactionStatuses(),
            'seller' => self::getSellerStatuses(),
            default => []
        };
        
        return $statuses[$status] ?? 'Không xác định';
    }
    
    /**
     * Get status badge class for UI
     */
    public static function getStatusBadgeClass($type, $status)
    {
        return match($type) {
            'user' => match($status) {
                self::USER_ACTIVE => 'badge-success',
                self::USER_INACTIVE => 'badge-warning',
                self::USER_BANNED => 'badge-danger',
                default => 'badge-secondary'
            },
            'post' => match($status) {
                self::POST_APPROVED => 'badge-success',
                self::POST_PENDING => 'badge-warning',
                self::POST_REJECTED => 'badge-danger',
                self::POST_RENTED => 'badge-info',
                self::POST_HIDDEN => 'badge-secondary',
                default => 'badge-secondary'
            },
            'contact' => match($status) {
                self::CONTACT_DEAL => 'badge-success',
                self::CONTACT_INTERESTED => 'badge-info',
                self::CONTACT_CONTACTED => 'badge-primary',
                self::CONTACT_VIEWED => 'badge-warning',
                self::CONTACT_PENDING => 'badge-secondary',
                self::CONTACT_CANCELLED => 'badge-danger',
                default => 'badge-secondary'
            },
            'transaction' => match($status) {
                self::TRANSACTION_COMPLETED => 'badge-success',
                self::TRANSACTION_PAID => 'badge-info',
                self::TRANSACTION_CONFIRMED => 'badge-primary',
                self::TRANSACTION_PENDING => 'badge-warning',
                self::TRANSACTION_CANCELLED => 'badge-danger',
                default => 'badge-secondary'
            },
            default => 'badge-secondary'
        };
    }
}
