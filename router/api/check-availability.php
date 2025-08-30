<?php
/**
 * Check Availability API (Email/Username)
 * Tro365 - Website thuê trọ
 */

header('Content-Type: application/json');

use Tro365\Models\User;
use Tro365\Helpers\ValidationHelper;
use Tro365\Helpers\LoggerHelper;
use Symfony\Component\Validator\Constraints as Assert;

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $excludeId = isset($input['exclude_id']) ? (int)$input['exclude_id'] : null;

    // Enhanced validation using rakit/validation
    $validation = \Tro365\Helpers\ValidationHelper::enhancedValidate($input, [
        'type' => 'required|in:email,username',
        'value' => 'required|min:1',
        'exclude_id' => 'nullable|integer|min:1'
    ], [
        'type.required' => 'Type là bắt buộc',
        'type.in' => 'Type phải là email hoặc username',
        'value.required' => 'Value là bắt buộc',
        'value.min' => 'Value không được để trống',
        'exclude_id.integer' => 'exclude_id không hợp lệ'
    ]);

    if (!$validation['valid']) {
        http_response_code(400);
        $errors = [];
        foreach ($validation['errors'] as $field => $fieldErrors) {
            $errors = array_merge($errors, $fieldErrors);
        }
        echo json_encode(['error' => implode(', ', $errors)]);
        exit;
    }

    $type = trim($input['type']);
    $value = trim($input['value']);
    
    $user = new User();
    $available = true;
    $message = '';
    
    switch ($type) {
        case 'email':
            // Enhanced email validation using rakit/validation
            $emailValidation = \Tro365\Helpers\ValidationHelper::enhancedValidate(['email' => $value], [
                'email' => 'required|email'
            ], [
                'email.required' => 'Email không được để trống',
                'email.email' => 'Định dạng email không hợp lệ'
            ]);

            if (!$emailValidation['valid']) {
                $available = false;
                $errors = [];
                foreach ($emailValidation['errors'] as $field => $fieldErrors) {
                    $errors = array_merge($errors, $fieldErrors);
                }
                $message = implode(', ', $errors);
                LoggerHelper::logAPI('check-availability', 'POST', ['type' => 'email', 'valid' => false]);
            } else {
                if ($excludeId) {
                    $existing = $user->emailExists($value, $excludeId) ? ['ID' => $excludeId] : null;
                } else {
                    $existing = $user->getByEmail($value);
                }
                $available = !$existing;
                $message = $existing ? 'Email đã được sử dụng' : 'Email có thể sử dụng';
                LoggerHelper::logAPI('check-availability', 'POST', ['type' => 'email', 'available' => $available, 'excludeId' => $excludeId]);
            }
            break;
            
        case 'username':
            // Validate username using Symfony Validator
            $validation = ValidationHelper::validateValue($value, [
                new Assert\NotBlank(['message' => 'Tên đăng nhập không được để trống']),
                new Assert\Length([
                    'min' => 3,
                    'max' => 30,
                    'minMessage' => 'Tên đăng nhập phải có ít nhất {{ limit }} ký tự',
                    'maxMessage' => 'Tên đăng nhập không được vượt quá {{ limit }} ký tự'
                ]),
                new Assert\Regex([
                    'pattern' => '/^[a-zA-Z0-9_]+$/',
                    'message' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới'
                ])
            ]);

            if (!$validation['valid']) {
                $available = false;
                $message = implode(', ', $validation['errors']);
                LoggerHelper::logAPI('check-availability', 'POST', ['type' => 'username', 'valid' => false]);
            } else {
                if ($excludeId) {
                    $existing = $user->usernameExists($value, $excludeId) ? ['ID' => $excludeId] : null;
                } else {
                    $existing = $user->getByUsername($value);
                }
                $available = !$existing;
                $message = $existing ? 'Tên đăng nhập đã tồn tại' : 'Tên đăng nhập có thể sử dụng';
                LoggerHelper::logAPI('check-availability', 'POST', ['type' => 'username', 'available' => $available, 'excludeId' => $excludeId]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid type. Use "email" or "username"']);
            exit;
    }
    
    echo json_encode([
        'available' => $available,
        'message' => $message,
        'type' => $type
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log("Check availability error: " . $e->getMessage());
}
