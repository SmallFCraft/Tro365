<?php
/**
 * Validation Rules - Canonical Source of Truth
 * Tro365 - Website thuê trọ
 * 
 * This file contains the canonical validation patterns and messages
 * used across both client-side (JavaScript) and server-side (PHP) validation.
 * 
 * DO NOT modify patterns here without updating both:
 * - assets/js/global/form-validator.js
 * - classes/helpers/ValidationHelper.php
 */

return [
    'patterns' => [
        // Vietnamese phone number pattern (supports both 84 and 0 prefix)
        // Covers major Vietnamese mobile operators: Viettel, Vinaphone, Mobifone, Vietnamobile, Gmobile
        'phone' => '/^(84|0)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-6|8|9]|9[0-4|6-9])[0-9]{7}$/',
        
        // Standard email pattern (RFC 5322 compliant)
        'email' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
        
        // Username pattern (alphanumeric + underscore, 3-30 chars)
        'username' => '/^[a-zA-Z0-9_]{3,30}$/',
        
        // Vietnamese CCCD/CMND pattern (9-12 digits)
        'cccd' => '/^[0-9]{9,12}$/',
        
        // URL pattern (basic validation)
        'url' => '/^https?:\/\/.+\..+/',
        
        // Numeric pattern (integers only)
        'numeric' => '/^[0-9]+$/',
        
        // Alphanumeric pattern
        'alphanumeric' => '/^[a-zA-Z0-9]+$/',
        
        // Date pattern (Y-m-d format)
        'date' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
        
        // Time pattern (H:i format)
        'time' => '/^[0-9]{2}:[0-9]{2}$/'
    ],
    
    'messages' => [
        'vi' => [
            // Required field messages
            'required' => 'Trường này là bắt buộc',
            'required_field' => 'Trường :field là bắt buộc',
            
            // Format validation messages
            'email' => 'Email không hợp lệ',
            'phone' => 'Số điện thoại không hợp lệ',
            'username' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới (3-30 ký tự)',
            'cccd' => 'CCCD/CMND phải có từ 9-12 chữ số',
            'url' => 'URL không hợp lệ',
            'numeric' => 'Trường này phải là số',
            'alphanumeric' => 'Trường này chỉ được chứa chữ cái và số',
            'date' => 'Ngày không hợp lệ (định dạng: YYYY-MM-DD)',
            'time' => 'Thời gian không hợp lệ (định dạng: HH:MM)',
            
            // Length validation messages
            'min_length' => 'Trường này phải có ít nhất :min ký tự',
            'max_length' => 'Trường này không được vượt quá :max ký tự',
            'length_between' => 'Trường này phải có từ :min đến :max ký tự',
            
            // Range validation messages
            'min_value' => 'Giá trị phải lớn hơn hoặc bằng :min',
            'max_value' => 'Giá trị phải nhỏ hơn hoặc bằng :max',
            'range_between' => 'Giá trị phải từ :min đến :max',
            
            // Confirmation validation
            'password_confirmation' => 'Xác nhận mật khẩu không khớp',
            'confirmation' => 'Xác nhận không khớp',
            
            // Unique validation
            'unique' => 'Giá trị này đã tồn tại',
            'email_exists' => 'Email này đã được sử dụng',
            'username_exists' => 'Tên đăng nhập này đã được sử dụng',
            
            // File validation messages
            'file_required' => 'Vui lòng chọn file',
            'file_size' => 'Kích thước file không được vượt quá :max MB',
            'file_type' => 'Định dạng file không được hỗ trợ. Chỉ chấp nhận: :types',
            'image_required' => 'Vui lòng chọn hình ảnh',
            'image_type' => 'File phải là hình ảnh hợp lệ (JPG, PNG, GIF, WebP)',
            
            // Specific field messages
            'password_min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password_strength' => 'Mật khẩu phải chứa ít nhất 1 chữ thường, 1 chữ hoa, 1 số và 1 ký tự đặc biệt',
            'fullname_min' => 'Họ tên phải có ít nhất 2 ký tự',
            'title_min' => 'Tiêu đề phải có ít nhất 10 ký tự',
            'description_min' => 'Mô tả phải có ít nhất 50 ký tự',
            'address_min' => 'Địa chỉ phải có ít nhất 10 ký tự',
            'price_min' => 'Giá thuê phải lớn hơn 0',
            'area_min' => 'Diện tích phải lớn hơn 0',
            'rooms_min' => 'Số phòng phải ít nhất là 1',
            'age_min' => 'Bạn phải ít nhất 13 tuổi',
            'birth_date_future' => 'Ngày sinh không thể là ngày trong tương lai',
            
            // Terms and conditions
            'terms_required' => 'Bạn phải đồng ý với điều khoản sử dụng'
        ]
    ],
    
    'constraints' => [
        // Length constraints
        'username' => ['min' => 3, 'max' => 30],
        'password' => ['min' => 8, 'max' => 100],
        'fullname' => ['min' => 2, 'max' => 100],
        'title' => ['min' => 10, 'max' => 255],
        'description' => ['min' => 50, 'max' => 2000],
        'address' => ['min' => 10, 'max' => 500],
        'subject' => ['min' => 5, 'max' => 200],
        'message' => ['min' => 20, 'max' => 2000],
        
        // Numeric constraints
        'price' => ['min' => 0, 'max' => 999999999],
        'area' => ['min' => 1, 'max' => 10000],
        'rooms' => ['min' => 1, 'max' => 50],
        'age' => ['min' => 13, 'max' => 120],
        
        // File constraints
        'image_max_size' => 5242880, // 5MB in bytes
        'image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'file_max_size' => 10485760, // 10MB in bytes
        
        // Other constraints
        'phone_length' => ['min' => 10, 'max' => 11],
        'cccd_length' => ['min' => 9, 'max' => 12]
    ],
    
    'field_names' => [
        'vi' => [
            'username' => 'tên đăng nhập',
            'email' => 'email',
            'fullname' => 'họ và tên',
            'phone' => 'số điện thoại',
            'password' => 'mật khẩu',
            'password_confirmation' => 'xác nhận mật khẩu',
            'password_confirm' => 'xác nhận mật khẩu',
            'address' => 'địa chỉ',
            'birth_date' => 'ngày sinh',
            'title' => 'tiêu đề',
            'description' => 'mô tả',
            'content' => 'nội dung',
            'subject' => 'chủ đề',
            'message' => 'tin nhắn',
            'name' => 'họ tên',
            'price' => 'giá thuê',
            'area' => 'diện tích',
            'rooms' => 'số phòng',
            'cccd' => 'CCCD/CMND'
        ]
    ]
];
